<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AppBottomNav from '@/components/AppBottomNav.vue';
import PwaInstallBanner from '@/components/PwaInstallBanner.vue';
import PwaNotificationToggle from '@/components/PwaNotificationToggle.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar class="hidden md:flex" />
        <AppContent variant="sidebar" class="overflow-x-hidden pb-28 md:pb-0">
            <AppSidebarHeader :breadcrumbs="breadcrumbs">
                <template #actions>
                    <PwaNotificationToggle />
                </template>
            </AppSidebarHeader>
            <slot />
        </AppContent>
        <AppBottomNav />
        <PwaInstallBanner />
        <Toaster />
    </AppShell>
</template>
