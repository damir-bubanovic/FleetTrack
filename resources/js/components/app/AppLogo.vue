<script setup lang="ts">
withDefaults(
    defineProps<{
        showText?: boolean;
    }>(),
    {
        showText: true,
    },
);
</script>

<template>
    <div class="flex items-center">
        <img
            src="/images/branding/fleettrack-logo.svg"
            alt="FleetTrack"
            class="h-9 w-9 shrink-0 object-contain"
        />

        <div v-if="showText" class="ml-3">
            <div class="text-[17px] font-semibold tracking-tight text-white">
                FleetTrack
            </div>

            <div class="text-[11px] font-medium text-subtle">
                Fleet management
            </div>
        </div>
    </div>
</template>
