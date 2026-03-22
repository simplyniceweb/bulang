<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-100 p-6">
        <div class="max-w-md w-full bg-white shadow-2xl rounded-2xl p-8 text-center border-t-4 border-red-600">
            <div class="mb-4 text-red-500">
                <svg class="w-16 h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <h1 class="text-2xl font-black text-gray-800 mb-2">ACCESS DENIED</h1>
            <p class="text-gray-600 mb-6">
                Your account is not assigned to the currently active event. Please contact the Game Master or Admin to be assigned.
            </p>
            <Link 
                :href="route('logout')" 
                method="post" 
                as="button"
                class="w-full py-3 bg-gray-800 text-white rounded-xl font-bold hover:bg-black transition"
            >
                Logout
            </Link>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
</script>