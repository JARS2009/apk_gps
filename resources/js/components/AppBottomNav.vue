<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import {
    LayoutGrid,
    MapPin,
    PawPrint,
    Radio,
    Tractor,
    Users,
} from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';

const page = usePage();
const { isCurrentUrl } = useCurrentUrl();
const { getInitials } = useInitials();

const isMenuOpen = ref(false);

const user = computed(() => page.props.auth.user);
const isSuperAdmin = computed(() => user.value?.role === 'super_admin');
const sinGranjaAsignada = computed(
    () => !isSuperAdmin.value && page.props.auth.granjasCount === 0,
);

const navItems = computed(() => [
    {
        title: 'Inicio',
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
              {
                  title: 'Usuarios',
                  href: '/usuarios',
                  icon: Users,
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
]);

const showAvatar = computed(
    () => user.value?.avatar && user.value?.avatar !== '',
);
</script>

<template>
    <div
        class="safe-bottom fixed right-0 bottom-5 left-0 z-50 flex justify-center px-4 md:hidden"
    >
        <div
            class="flex h-16 w-full max-w-md items-center justify-around rounded-full border border-border/40 bg-background/80 px-2 shadow-[0_8px_30px_rgb(0,0,0,0.08)] backdrop-blur-lg dark:border-white/10 dark:bg-zinc-950/80 dark:shadow-[0_8px_30px_rgb(0,0,0,0.3)]"
        >
            <template v-for="item in navItems" :key="item.title">
                <Link
                    :href="item.href"
                    class="relative flex h-12 w-12 flex-col items-center justify-center rounded-full transition-all duration-300"
                    :class="
                        isCurrentUrl(item.href)
                            ? 'scale-105 font-semibold text-primary'
                            : 'text-muted-foreground hover:scale-105 hover:text-foreground'
                    "
                >
                    <!-- Active background pill -->
                    <div
                        v-if="isCurrentUrl(item.href)"
                        class="absolute inset-0 -z-10 scale-90 rounded-full bg-primary/10 transition-all duration-300"
                    />
                    <component
                        :is="item.icon"
                        class="h-5 w-5 transition-transform duration-300"
                        :class="isCurrentUrl(item.href) ? 'scale-110' : ''"
                    />
                    <span
                        class="mt-0.5 text-[9px] leading-none tracking-tight"
                        >{{ item.title }}</span
                    >
                </Link>
            </template>

            <!-- Profile/Menu Dropdown -->
            <div class="flex h-12 w-12 items-center justify-center">
                <DropdownMenu @update:open="(val) => (isMenuOpen = val)">
                    <DropdownMenuTrigger as-child>
                        <button
                            class="relative flex h-12 w-12 flex-col items-center justify-center rounded-full text-muted-foreground transition-all duration-300 hover:scale-105 hover:text-foreground focus:outline-none"
                            :class="isMenuOpen ? 'text-primary' : ''"
                        >
                            <!-- Active menu background pill -->
                            <div
                                v-if="isMenuOpen"
                                class="absolute inset-0 -z-10 scale-90 rounded-full bg-primary/10 transition-all duration-300"
                            />
                            <Avatar
                                class="h-5 w-5 overflow-hidden rounded-full border border-muted ring-offset-background transition-all duration-300"
                                :class="
                                    isMenuOpen
                                        ? 'ring-2 ring-primary ring-offset-1'
                                        : ''
                                "
                            >
                                <AvatarImage
                                    v-if="showAvatar"
                                    :src="user.avatar!"
                                    :alt="user.name"
                                />
                                <AvatarFallback
                                    class="bg-primary/10 text-[9px] font-bold text-primary"
                                >
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span class="mt-0.5 text-[9px] leading-none"
                                >Menú</span
                            >
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        class="min-w-56 rounded-2xl border-border/50 p-2 shadow-xl backdrop-blur-md"
                        align="end"
                        side="top"
                        :side-offset="12"
                    >
                        <UserMenuContent :user="user" />
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Adjust height to support iOS safe area, but keep dock-style floating look */
.safe-bottom {
    bottom: calc(1.25rem + env(safe-area-inset-bottom));
}
</style>
