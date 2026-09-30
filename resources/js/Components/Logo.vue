<script setup>
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    className: { type: String, default: 'h-10 w-auto' },
});

const page = usePage();
const branding = computed(() => page.props.branding || {});
</script>

<template>
    <img
        :src="branding.logo || '/images/logo.png'"
        :alt="branding.name || 'Logo'"
        :class="className"
    >
</template>
