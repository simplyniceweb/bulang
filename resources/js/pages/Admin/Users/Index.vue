<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { debounce } from 'lodash';
import { computed, ref, watch } from 'vue';
import { formatLabel } from '@/helpers/format';
import AppLayout from '@/layouts/AppLayout.vue';
import type { Paginated } from '@/types/pagination';
import { route } from 'ziggy-js';
import Pagination from '../../../components/Pagination.vue';

interface User {
    id: number
    name: string
    username: string
    role: string
}

const props = defineProps<{
    users: Paginated<User>
    filters?: {
        search?: string
    }
}>()

const users = computed(() => props.users.data)
const links = computed(() => props.users.links)

const search = ref(props.filters?.search || '')

watch(
  search,
  debounce((value: string) => {
    router.get(
      route('admin.users.index'),
      { search: value },
      {
        preserveState: true,
        replace: true,
      }
    )
  }, 300)
)

const deleteUser = (id: number) => {
  if (confirm('Are you sure you want to delete this user?')) {
    router.delete(route('admin.users.destroy', id), {
      onSuccess: () => {
        console.log('User deleted');
      },
      onError: (errors) => console.log(errors),
    });
  }
};
</script>

<template>
    <AppLayout>
        <Head title="Users" />
        <div
            class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4"
        >
        <input
            v-model="search"
            type="text"
            placeholder="Search users..."
            class="p-3 border border-gray-400 border-rounded focus:outline-none focus:ring-2 focus:ring-gray-700 focus:border-transparent transition"
        />
        <Link :href="route('admin.users.create')" class="self-end rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 transition">
            Create User
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
                        Username
                        </th>
                        <th
                        scope="col"
                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                        >
                        Role
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
                    <tr v-for="user in users" :key="user.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ user.id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ user.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ user.username }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">{{ formatLabel(user.role) }}</td>

                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 flex gap-2">
                        <!-- Edit Button -->
                        <Link
                            :href="route('admin.users.edit', user.id)"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-md text-sm"
                        >
                            Edit
                        </Link>

                        <!-- Delete Button -->
                        <button
                            @click="deleteUser(user.id)"
                            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm"
                        >
                            Delete
                        </button>
                        </td>
                    </tr>

                    <tr v-if="users.length === 0">
                        <td colspan="3" class="px-6 py-4 text-center text-gray-400 text-sm">
                        No users found.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
                
            <div class="mt-4 ms-2 flex space-x-1">
                <Pagination :links="links" />
            </div>
        </div>
    </AppLayout>
</template>