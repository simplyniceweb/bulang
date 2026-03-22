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

        <!-- Wallet -->
        <div class="mt-6 border-t pt-4">
            <h3 class="text-lg font-medium mb-2">Tellers &amp; Wallet amount</h3>
            
            <select @change="addTeller" class="w-full border p-2 rounded mb-4">
                <option value="">Select teller...</option>
                <option v-for="t in availableTellers" :key="t.id" :value="t.id">
                    {{ t.name }}
                </option>
            </select>

            <button 
                v-if="form.tellers.length > 0"
                type="button"
                @click="removeAllTellers"
                class="fs-lg mb-3 text-white bg-red-600 hover:bg-red-700 font-bold px-4 py-1.5 rounded-lg uppercase tracking-tighter shadow-sm transition mt-1"
                >
                Clear All Tellers
            </button>

            <div v-if="availableTellers.length > form.tellers.length" class="flex items-center gap-2 mb-3">
                <button 
                    type="button"
                    @click="addAllTellers"
                    class="text-xs bg-indigo-600 text-white px-4 py-1.5 rounded-lg hover:bg-indigo-700 transition shadow-sm font-medium"
                >
                    + Add All Tellers
                </button>

                <div class="relative">
                    <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-400 text-xs">₱</span>
                    <input 
                        type="number" 
                        v-model="defaultAmount"
                        placeholder="Default Cash"
                        class="pl-5 pr-2 py-1 text-xs border border-gray-300 rounded-lg w-28 focus:ring-indigo-500 focus:border-indigo-500"
                    />
                </div>
            </div>

            <div v-for="(teller, index) in form.tellers" :key="teller.id" class="flex items-center gap-4 mb-2 bg-gray-100 p-3 rounded">
                <span class="flex-1 font-bold">{{ teller.name }}</span>
                <div class="flex items-center gap-2">
                    <label class="text-md">Initial Cash:</label>
                    <input type="number" v-model="teller.amount" class="border border-gray-700 p-1 w-32 rounded bg-white" />
                </div>
                <button type="button" @click="removeTeller(index)" class="text-red-500 cursor-pointer font-bold">×</button>
            </div>
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
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
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

const breadcrumbs: BreadcrumbItem[] = [
  { title: 'Events', href: route('admin.events.index') },
  { title: 'Create', href: route('admin.events.create') },
];

// Inertia form helper
const form = useForm({
    name: '',
    house_percent: 6,
    status: 'inactive',
    tellers: [] as Array<{ id: number, name: string, amount: number }>
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
const defaultAmount = ref(0);

// Add 'availableTellers' to your props
const props = defineProps<{
    availableTellers: Array<{ id: number, name: string }>
}>();

const addAllTellers = () => {
    props.availableTellers.forEach(teller => {
        const exists = form.tellers.find(t => t.id === teller.id);
        
        if (!exists) {
            form.tellers.push({ 
                id: teller.id, 
                name: teller.name, 
                // Use the value from our new input box
                amount: defaultAmount.value 
            });
        }
    });
};

const addTeller = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const tellerId = parseInt(target.value);
    const teller = props.availableTellers.find(t => t.id === tellerId);
    
    if (teller && !form.tellers.find(t => t.id === tellerId)) {
        form.tellers.push({ id: teller.id, name: teller.name, amount: 0 });
    }
    target.value = ""; // Reset select
};

const removeAllTellers = () => {
    if (confirm('Are you sure you want to remove all assigned tellers?')) {
        form.tellers = [];
    }
};

const removeTeller = (index: number) => {
    form.tellers.splice(index, 1);
};
</script>
