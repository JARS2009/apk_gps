# Reglas de Arquitectura Base — [Nombre del Proyecto]

> Estas reglas son **OBLIGATORIAS** para toda IA y desarrollador que trabaje en este proyecto.
> Se aplican a cada archivo creado o modificado sin excepción.

---

## 1. Principio Fundamental

> **El Controller es un puente, no una fábrica.**

Toda la lógica de negocio vive en **Services**. El controller solo:
1. Recibe el request (ya validado por FormRequest).
2. Llama al Service correspondiente.
3. Devuelve la response (Inertia::render, redirect, o JSON).

---

## 2. Base de Datos (MySQL) y Migraciones

> **Regla de Oro:** La base de datos oficial del proyecto es **MySQL**. 

- **Cero cambios manuales:** Está estrictamente prohibido alterar el esquema de la base de datos directamente en el gestor. 
- **Migraciones obligatorias:** Cualquier cambio en la estructura (creación de tablas, agregar/modificar columnas, índices, llaves foráneas) **DEBE** realizarse mediante un archivo de migración de Laravel (`php artisan make:migration`).
- **Nombres en plural:** Las tablas deben seguir la convención de Laravel (minúsculas, en inglés y en plural, ej: `users`, `products`, `orders`) a menos que el equipo defina un estándar de prefijos específico.

---

## 3. Estructura de Carpetas Backend
 
```text
app/
├── DTOs/                          # Data Transfer Objects
│   ├── User/
│   │   ├── CreateUserDTO.php
│   │   └── UpdateUserDTO.php
│   └── [Dominio]/
│
├── Enums/                         # Enums de dominio
│   ├── UserRole.php               # Ej: Admin, Customer
│   └── OrderStatus.php            # Ej: Pending, Paid, Shipped
│
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php         # Base
│   │   ├── Api/                   # Controllers de API JSON
│   │   ├── User/
│   │   │   └── UserController.php
│   │   └── [Dominio]/
│   │       └── DominioController.php
│   │
│   ├── Requests/                  # FormRequests agrupados por dominio
│   │   ├── User/
│   │   │   ├── StoreUserRequest.php
│   │   │   └── UpdateUserRequest.php
│   │   └── [Dominio]/
│   │
│   └── Middleware/
│
├── Models/                        # Modelos Eloquent
│   ├── User.php
│   └── [Dominio].php
│
├── Services/                      # TODA la lógica de negocio
│   ├── User/
│   │   └── UserService.php
│   └── [Dominio]/
│       └── DominioService.php
│
└── Providers/
```

---

## 4. Reglas del Backend

### 4.1 Controllers — El Puente

```php
// ✅ CORRECTO — Controller delgado
class UserController extends Controller
{
    public function __construct(
        private readonly UserService $userService,
    ) {}

    public function index(Request $request): Response
    {
        return Inertia::render('User/Index', [
            'users' => $this->userService->listarPaginado($request),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->userService->crear($request->toDTO());

        return redirect()->route('users.index')
            ->with('success', 'Registro creado correctamente.');
    }
}

// ❌ PROHIBIDO — Controller gordo
class UserController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        // NO validar aquí
        $request->validate([...]);

        // NO poner lógica de negocio aquí
        $user = User::create([...]);
        $user->profile()->create([...]);
    }
}
```

#### Reglas concretas:
- Máximo 5–8 líneas por método de controller.
- NUNCA usar `$request->validate()` en el controller.
- NUNCA interactuar con modelos directamente en el controller.
- SIEMPRE inyectar el Service por constructor.
- SIEMPRE tipar el retorno: `Response`, `RedirectResponse`, `JsonResponse`.
- Usar `Inertia::render()` para vistas, `redirect()->route()` para redirects.

### 4.2 FormRequests — Las Validaciones

```php
// app/Http/Requests/User/StoreUserRequest.php
class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; 
    }

    /** @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'role_id' => ['required', 'integer', 'exists:roles,id'], // Validación contra MySQL
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'name' => ['required', 'string', 'max:150'],
        ];
    }

    /** Convierte los datos validados a un DTO. */
    public function toDTO(): CreateUserDTO
    {
        return CreateUserDTO::from($this->validated());
    }
}
```

#### Reglas:
- TODA validación va en FormRequest, sin excepción.
- Usar `exists:` y `unique:` especificando correctamente las tablas de MySQL.
- Incluir método `toDTO()` para convertir datos validados a DTO.

### 4.3 Services — La Lógica

```php
// app/Services/User/UserService.php
class UserService
{
    /** @return \Illuminate\Pagination\LengthAwarePaginator<User> */
    public function listarPaginado(Request $request): LengthAwarePaginator
    {
        return User::query()
            ->with(['role']) // Eager loading
            ->when($request->search, fn ($q, $search) =>
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
            )
            ->latest()
            ->paginate(15);
    }

    public function crear(CreateUserDTO $dto): User
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create($dto->toArray());
            
            // Lógica adicional (ej: enviar email, crear perfil)
            
            return $user;
        });
    }
}
```

#### Reglas:
- Un service por entidad principal.
- Usar `DB::transaction()` cuando hay múltiples writes en la base de datos.
- SIEMPRE eager load relaciones con `with()` para evitar el problema N+1.
- Retornar modelos, paginators, o collections.

### 4.4 DTOs — Transferencia de Datos

Usamos clases de solo lectura (disponible desde PHP 8.2) para garantizar la inmutabilidad de los datos en tránsito.

```php
// app/DTOs/User/CreateUserDTO.php
final readonly class CreateUserDTO
{
    public function __construct(
        public int $role_id,
        public string $name,
        public string $email,
        public ?string $password = null,
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(...$data);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
```

### 4.5 Enums

Usar Enums nativos para estados, tipos y valores fijos.

```php
// app/Enums/Status.php
enum Status: string
{
    case Active = 'active';
    case Inactive = 'inactive';
    case Pending = 'pending';
}
```

---

## 5. Estructura de Carpetas Frontend

```plaintext
resources/js/
├── app.ts                         # Entry point
├── types/                         # Tipos TypeScript
│   ├── index.d.ts                 
│   └── models/                    # Interfaces de modelos devueltos por el backend
│       └── user.ts
│
├── composables/                   # Hooks reutilizables
│   ├── useDataTable.ts            
│   └── useFormModal.ts            
│
├── components/
│   ├── ui/                        # Componentes UI base (Ej: shadcn-vue)
│   ├── shared/                    # Componentes de negocio reutilizables
│   │   ├── DataTable.vue          
│   │   ├── FormModal.vue          
│   │   └── ConfirmDialog.vue      
│   └── [dominio]/                 # Componentes específicos
│       └── UserForm.vue
│
├── layouts/
│   └── AppLayout.vue              
│
└── pages/                         # Páginas Inertia (1 por ruta)
    ├── Dashboard.vue
    └── [dominio]/
        ├── Index.vue              # Listado (Tabla + Filtros)
        ├── Create.vue             
        ├── Show.vue               
        └── Edit.vue               
```

---

## 6. Reglas del Frontend

### 6.1 Pages — Solo Layout y Conexión

Las vistas de página (`pages/`) deben ser contenedores. La lógica compleja o de UI debe vivir en componentes.

```html
<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import DataTable from '@/components/shared/DataTable.vue';
import type { User, PaginatedResponse } from '@/types/models/user';

const props = defineProps<{
    users: PaginatedResponse<User>;
    filters: { search?: string };
}>();
</script>

<template>
    <Head title="Usuarios" />
    <DataTable
        :data="props.users"
        :filters="props.filters"
        :columns="columns"
        create-route="users.create"
    />
</template>
```

### 6.2 Tipos TypeScript

Mantener la paridad de tipos entre la base de datos MySQL / Modelos Eloquent y el frontend.

```typescript
// types/models/user.ts
export interface User {
    id: number;
    role_id: number;
    name: string;
    email: string;
    created_at: string;
    updated_at: string;
    // Relaciones
    role?: Role;
}

export interface PaginatedResponse<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}
```

---

## 7. Reglas Transversales

### 7.1 Nombrado

| Elemento | Convención | Ejemplo |
| --- | --- | --- |
| Tablas MySQL | plural, snake_case | `users`, `order_items` |
| Migrations | `create_[table]_table` | `create_users_table.php` |
| Controllers | PascalCase + Controller | `UserController` |
| Services | PascalCase + Service | `UserService` |
| FormRequests | Action + PascalCase + Request | `StoreUserRequest` |
| DTOs | Action + PascalCase + DTO | `CreateUserDTO` |
| Models | PascalCase, singular | `User`, `OrderItem` |
| Pages Vue | PascalCase.vue | `Index.vue`, `Create.vue` |
| Componentes | PascalCase.vue | `DataTable.vue` |
| Composables | camelCase (prefijo use) | `useDataTable.ts` |

### 7.2 Borrado Lógico (Soft Deletes)

Dado que Laravel maneja muy bien el ciclo de vida de los datos, para registros que no deben ser eliminados permanentemente de MySQL, utilizar el trait `SoftDeletes` nativo de Laravel en lugar de booleanos manuales, asegurándose de añadir `$table->softDeletes()` en la migración.

---

## 8. Flujo de Desarrollo de un Módulo (Checklist)

Para crear un nuevo módulo (ej. Product), sigue este orden estricto:

1. `php artisan make:model Product -m` (Crea Modelo y Migración).
2. Definir esquema en la migración de MySQL y ejecutar `php artisan migrate`.
3. Crear DTOs (`CreateProductDTO.php`, `UpdateProductDTO.php`).
4. Crear Service (`ProductService.php`).
5. Crear FormRequests (`StoreProductRequest.php`, `UpdateProductRequest.php`).
6. Crear Controller (`ProductController.php`).
7. Registrar recursos en `routes/web.php` (`Route::resource(...)`).
8. Definir interfaces TypeScript (`types/models/product.ts`).
9. Crear formulario Vue (`components/product/ProductForm.vue`).
10. Crear vistas Inertia (`pages/product/Index.vue`, `Create.vue`, `Edit.vue`).