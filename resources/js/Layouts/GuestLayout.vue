<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import BrandTheme from '../Components/BrandTheme.vue';
import Logo from '../Components/Logo.vue';

const page = usePage();
const branding = computed(() => page.props.branding || {});
</script>

<template>
    <BrandTheme>
        <div class="flex min-h-screen items-center justify-center px-4">
            <div class="w-full max-w-md rounded-xl border border-stone-200 bg-white/95 p-8 shadow-sm">
                <div class="mb-6 flex flex-col items-center text-center">
                    <Logo class-name="mb-4 h-20 w-auto" />
                    <p class="text-lg font-semibold text-brand">{{ branding.name }}</p>
                    <p v-if="branding.tagline" class="mt-1 text-sm text-muted">{{ branding.tagline }}</p>
                </div>
                <slot />
            </div>
        </div>
    </BrandTheme>
</template>
