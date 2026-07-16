<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutGrid,
    MapPin,
    PawPrint,
    Radio,
    Tractor,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const page = usePage();
const isSuperAdmin = computed(
    () => page.props.auth.user.role === 'super_admin',
);
// Un admin sin granjas asignadas quedará bloqueado por el middleware
// `granja.acceso`; ocultamos los enlaces para evitar navegar a una página
// que de todas formas redirigirá a `sin-acceso`. Esto es solo UX.
const sinGranjaAsignada = computed(
    () => !isSuperAdmin.value && page.props.auth.granjasCount === 0,
);

const mainNavItems = computed<NavItem[]>(() => [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    ...(isSuperAdmin.value
        ? [
              {
                  title: 'Granjas',
                  href: '/granjas',
                  icon: Tractor,
              },
          ]
        : []),
    ...(sinGranjaAsignada.value
        ? []
        : [
              {
                  title: 'Animales',
                  href: '/animales',
                  icon: PawPrint,
              },
              {
                  title: 'Terrenos',
                  href: '/terrenos',
                  icon: MapPin,
              },
              {
                  title: 'Collares',
                  href: '/collares',
                  icon: Radio,
              },
          ]),
    ...(isSuperAdmin.value
        ? [
              {
                  title: 'Usuarios',
                  href: '/usuarios',
                  icon: Users,
              },
          ]
        : []),
]);

const footerNavItems: NavItem[] = [];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
</template>
