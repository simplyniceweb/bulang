<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Create User" />

    <div class="p-6 bg-gray-50 min-h-screen">
      <h1 class="text-2xl font-semibold mb-6 text-gray-800">Create User</h1>

      <form @submit.prevent="submit" class="bg-white shadow rounded-lg p-6 space-y-4">
        <!-- Name -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input
            type="text"
            v-model="form.name"
            class="w-full border border-gray-300 rounded-md p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
          />
          <p v-if="errors.name" class="text-red-500 text-sm mt-1">{{ errors.name }}</p>
        </div>

        <!-- Username -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
          <input
            type="text"
            v-model="form.username"
            class="w-full border border-gray-300 rounded-md p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
          />
          <p v-if="errors.username" class="text-red-500 text-sm mt-1">{{ errors.username }}</p>
        </div>

        <!-- Password -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input
            type="password"
            v-model="form.password"
            class="w-full border border-gray-300 rounded-md p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
          />
          <p v-if="errors.password" class="text-red-500 text-sm mt-1">{{ errors.password }}</p>
        </div>

        <!-- Role -->
        <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
        <select
            v-model="form.role"
            class="w-full border border-gray-300 rounded-md p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
        >
            <option value="teller">Teller</option>
            <option value="game_master">Game Master</option>
            <option value="admin">Administrator</option>
        </select>
        <p v-if="errors.role" class="text-red-500 text-sm mt-1">{{ errors.role }}</p>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition"
        >
          Create User
        </button>
      </form>
    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Users', href: route('admin.users.index') },
  { title: 'Create', href: route('admin.users.create') },
];

// Inertia form helper
const form = useForm({
  name: '',
  username: '',
  password: '',
  role: 'teller',
});

// Submit function
const submit = () => {
  form.post(route('admin.users.store'), {
    onError: (errors) => {
      console.log(errors);
    },
    onSuccess: () => {
      form.reset();
    },
  });
};

// Errors for template
const errors = form.errors;
</script>
