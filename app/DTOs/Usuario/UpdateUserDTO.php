<?php

namespace App\DTOs\Usuario;

use App\Enums\UserRole;

final readonly class UpdateUserDTO
{
    /**
     * @param  array<int, int>  $granja_ids
     */
    public function __construct(
        public UserRole $role,
        public string $name,
        public string $email,
        public ?string $num_doc,
        public ?string $password,
        public array $granja_ids = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function from(array $data): self
    {
        return new self(
            role: $data['role'] instanceof UserRole ? $data['role'] : UserRole::from($data['role']),
            name: $data['name'],
            email: $data['email'],
            num_doc: $data['num_doc'] ?? null,
            password: $data['password'] ?? null,
            granja_ids: $data['granja_ids'] ?? [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'name' => $this->name,
            'email' => $this->email,
            'num_doc' => $this->num_doc,
            'password' => $this->password,
            'granja_ids' => $this->granja_ids,
        ];
    }
}
