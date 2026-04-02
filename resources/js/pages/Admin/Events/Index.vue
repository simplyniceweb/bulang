<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import AlertModal from '@/components/AlertModal.vue'
import { useFlashAlert } from '@/composables/useFlashAlert'
import { formatDateTime } from '@/helpers/format';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Paginated } from '@/types/pagination';
import { route } from 'ziggy-js';
import Pagination from '../../../components/Pagination.vue';

interface Event {
    id: number
    name: string
    status: string
    started_at: string | null
    ended_at: string | null
}

const {
  show,
  message,
  type,
  close
} = useFlashAlert()

const props = defineProps<{
    events: Paginated<Event>
    filters?: {
        search?: string
    }
}>()

const events = computed(() => props.events.data)
const links = computed(() => props.events.links)

const search = ref(props.filters?.search || '')

watch(
  search,
  debounce((value: string) => {
    router.get(
      route('admin.events.index'),
      { search: value },
      {
        preserveState: true,
        replace: true,
      }
    )
  }, 300)
)

const deleteEvent = (id: number) => {
  if (confirm('Are you sure you want to delete this event?')) {
    router.delete(route('admin.events.destroy', id), {
      onSuccess: () => {
        console.log('Event deleted');
      },
      onError: (errors) => console.log(errors),
    });
  }
};
</script>

<template>
    <AppLayout>
        <Head title="Events" />
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
        <input
            v-model="search"
            type="text"
            placeholder="Search users..."
            class="p-3 border border-gray-400 border-rounded focus:outline-none focus:ring-2 focus:ring-gray-700 focus:border-transparent transition"
        />
        <Link :href="route('admin.events.create')" class="self-end rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
            Create Event
        </Link>
            <div
                class="relative min-h-screen flex-1 rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border"
            >
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                    <tr>
                        <th
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                        ID
                        </th>
                        <th
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                        Name
                        </th>
                        <th
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                        Status
                        </th>
                        <th
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                        Started At
                        </th>
                        <th
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                        Ended At
                        </th>
                        <th
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                        Actions
                        </th>
                    </tr>
                    </thead>

                    <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="event in events" :key="event.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ event.id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ event.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ event.status }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatDateTime(event.started_at ?? '') }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatDateTime(event.ended_at ?? '') }}</td>

                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 flex gap-2">
                        <Link
                            :href="route('admin.events.ledger', event.id)"
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm"
                        >
                            View Ledger
                        </Link>
                        <Link
                            :href="route('admin.events.teller.audit', event.id)"
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm"
                        >
                            Audit Teller
                        </Link>
                        <Link
                            :href="route('admin.events.show', event.id)"
                            class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded-md text-sm"
                        >
                            Show Revenue
                        </Link>
                        <!-- Edit Button -->
                        <Link
                            v-if="event.status === 'inactive' || event.status === 'active'"
                            :href="route('admin.events.edit', event.id)"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm"
                        >
                            Edit
                        </Link>

                        <!-- Delete Button -->
                        <button
                            v-if="event.status === 'inactive'"
                            @click="deleteEvent(event.id)"
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm"
                        >
                            Delete
                        </button>
                        </td>
                        <td
                        v-if="event.status === 'active'">
                        </td>
                    </tr>

                    <tr v-if="events.length === 0">
                        <td colspan="6" class="px-6 py-4 text-center text-gray-400 text-sm">
                        No events found.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
                
            <div class="mt-2 ms-2 flex space-x-1">
                <Pagination :links="links" />
            </div>
        </div>

        <AlertModal
            :show="show"
            :message="message"
            :type="type"
            @close="close"
        />
    </AppLayout>
</template>