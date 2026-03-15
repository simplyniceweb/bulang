<template>
  <div class="flex flex-col items-center justify-center min-h-[60vh] text-center p-6">
    <div class="bg-amber-50 border-4 border-amber-400 p-8 rounded-lg shadow-sm max-w-md">
      <div class="flex justify-center mb-4">
        <svg class="h-12 w-12 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
      </div>
      
      <h2 class="text-xl font-bold text-amber-800 mb-2">NO ACTIVE EVENT FOUND!</h2>
      <p class="text-amber-700 mb-6">
        The dashboard is empty because there is currently no active event. 
        Please contact your administrator to activate an event.
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
</script>