<script setup lang="ts">
const currentYear = new Date().getFullYear();
</script>

<template>
    <footer
        class="border-t border-border-default bg-surface px-4 py-4 sm:px-5 lg:px-8"
    >
        <div
            class="mx-auto flex max-w-[1600px] items-center justify-center text-center"
        >
            <p class="text-xs text-muted">
                &copy; {{ currentYear }} Damir Bubanovic. All rights reserved.
            </p>
        </div>
    </footer>
</template>
