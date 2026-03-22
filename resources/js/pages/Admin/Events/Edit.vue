<template>
  <AppLayout :breadcrumbs="breadcrumbs">
    <Head title="Edit Event" />

    <div class="p-6 bg-gray-50 min-h-screen">
      <h1 class="text-2xl font-semibold mb-6 text-gray-800">Edit Event</h1>

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
            <option v-if="event.status === 'inactive'" value="inactive">Inactive</option>
            <option value="active">Active</option>
            <option value="closed">Closed</option>
        </select>
        <p v-if="errors.status" class="text-red-500 text-sm mt-1">{{ errors.status }}</p>
        </div>

        <!-- Wallet -->
        <div class="mt-6 border-t pt-4">
            <h3 class="text-lg font-medium mb-2">Tellers &amp; Wallet amount</h3>
            
            <select @change="addTeller" class="w-full border p-2 rounded mb-4">
                <option value="">Select teller...</option>
                <option v-for="t in availableTellers" :key="t.id" :value="t.id">
                    {{ t.name }}
                </option>
            </select>

            <div v-for="(teller, index) in form.tellers" :key="teller.id" class="flex items-center gap-4 mb-2 bg-gray-100 p-3 rounded">
                <span class="flex-1 font-bold">{{ teller.name }}</span>
                <div class="flex items-center gap-2">
                    <label class="text-md">Initial Cash:</label>
                    <input 
                        type="number" 
                        v-model="teller.amount" 
                        :disabled="event.status === 'active' || event.status === 'closed'"
                        :class="{'bg-gray-100 cursor-not-allowed': event.status === 'active'}"
                        class="w-32 border-gray-300 rounded text-right font-mono" />
                </div>
                <button type="button" @click="removeTeller(Number(index))" class="text-red-500 cursor-pointer font-bold">×</button>
            </div>
        </div>

        <!-- Submit -->
        <button
          type="submit"
          class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 transition"
        >
          Update Event
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
import { route } from 'ziggy-js';

const {
  show,
  message,
  type,
  close
} = useFlashAlert()

const props = defineProps<{ 
    event: any,
    availableTellers: Array<{ id: number, name: string }>
 }>();

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Events', href: route('admin.events.index') },
  { title: 'Edit', href: route('admin.events.edit', props.event.id) },
];

const form = useForm({
    name: props.event.name,
    house_percent: props.event.house_percent,
    status: props.event.status,
    // Map existing tellers from props to the form
    tellers: props.event.tellers.map((t: any) => ({
        id: t.id,
        name: t.name,
        amount: t.pivot.initial_wallet // Notice the .pivot here
    }))
});

// Errors for template
const errors = form.errors;

const addTeller = (e: Event) => {
    const id = parseInt((e.target as HTMLSelectElement).value);
    const teller = props.availableTellers.find(t => t.id === id);
    
    if (teller && !form.tellers.find(t => t.id === id)) {
        form.tellers.push({ id: teller.id, name: teller.name, amount: 0 });
    }
    (e.target as HTMLSelectElement).value = "";
};

const removeTeller = (index: number) => {
    form.tellers.splice(index, 1);
};

const submit = () => {
    // Use put or patch for updates
    form.put(route('admin.events.update', props.event.id));
};
</script>
