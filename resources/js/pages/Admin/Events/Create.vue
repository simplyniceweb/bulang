<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Create Event" />

    <div class="p-6 bg-gray-50 min-h-screen">
      <h1 class="text-2xl font-semibold mb-6 text-gray-800">Create Event</h1>

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

        <!-- House Percent -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">House Percent %</label>
          <input
            type="number"
            v-model="form.house_percent"
            step="0.01" min="0" max="100"
            class="w-full border border-gray-300 rounded-md p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
          />
          <p v-if="errors.house_percent" class="text-red-500 text-sm mt-1">{{ errors.house_percent }}</p>
        </div>

        <!-- Status -->
        <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
        <select
            v-model="form.status"
            class="w-full border border-gray-300 rounded-md p-2 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500"
        >
            <option value="inactive">Inactive</option>
            <option value="active">Active</option>
        </select>
        <p v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</p>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition"
        >
          Create Event
        </button>
      </form>
    </div>

    <AlertModal
        :show="show"
        :message="message"
        :type="type"
        @close="close"
    />
  </AppLayout>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import AlertModal from '@/components/AlertModal.vue'
import { useFlashAlert } from '@/composables/useFlashAlert'
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';

const {
  show,
  message,
  type,
  close
} = useFlashAlert()

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Events', href: route('admin.events.index') },
  { title: 'Create', href: route('admin.events.create') },
];

// Inertia form helper
const form = useForm({
    name: '',
    house_percent: 10,
    status: 'inactive',
});

// Submit function
const submit = () => {
  form.post(route('admin.events.store'), {
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
