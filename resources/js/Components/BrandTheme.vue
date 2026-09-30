<script setup>
import { computed, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

const page = usePage();
const branding = computed(() => page.props.branding || {});

function applyTheme(colors = {}) {
    const root = document.documentElement;
    const map = {
        '--brand-primary': colors.primary,
        '--brand-primary-dark': colors.primary_dark,
        '--brand-secondary': colors.secondary,
        '--brand-paper': colors.paper,
        '--brand-ink': colors.ink,
        '--brand-ok': colors.ok,
        '--brand-warn': colors.warn,
    };

    Object.entries(map).forEach(([key, value]) => {
        if (value) {
            root.style.setProperty(key, value);
        }
    });
}

onMounted(() => applyTheme(branding.value.colors));
watch(() => branding.value.colors, applyTheme, { deep: true });
</script>

<template>
    <slot />
</template>
