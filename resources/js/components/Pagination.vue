<script setup lang="ts">
import { router } from '@inertiajs/vue3'

interface Link {
    url: string | null
    label: string
    active: boolean
}

defineProps<{
    links: Link[]
}>()

function visit(url: string | null) {
    if (!url) return

    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    })
}
</script>

<template>
    <div
        v-if="links.length > 3"
        class="flex flex-wrap items-center gap-1"
    >
        <template v-for="(link, key) in links" :key="key">
            <!-- Disabled link -->
            <span
                v-if="!link.url"
                v-html="link.label"
                class="px-3 py-1 text-sm text-gray-400 border rounded"
            />

            <!-- Active link -->
            <button
                v-else
                @click="visit(link.url)"
                v-html="link.label"
                class="px-3 py-1 text-sm border rounded transition"
                :class="{
                    'bg-blue-600 text-white border-blue-600': link.active,
                    'bg-white text-gray-700 hover:bg-gray-100': !link.active
                }"
            />
        </template>
    </div>

    <div v-else class="text-sm text-gray-400">
        No pagination links available.
    </div>
</template>