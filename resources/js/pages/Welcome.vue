<script setup lang="ts">
</script>

<template>
  <div class="min-h-screen bg-blue-900 text-white p-4 md:p-6">

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
      <div class="text-xl md:text-2xl font-bold tracking-wide text-center md:text-left">
        GALERA DE JAYLORD 1 COCK DERBY | FEBRUARY 19, 2026
      </div>

      <div class="flex flex-col items-center md:items-end">
        <div class="text-xl font-bold">05:35:23 AM</div>
        <div class="bg-green-600 px-4 py-1 rounded text-lg font-bold mt-1 animate-pulse">
          BETTING OPEN
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-4">

      <div class="order-1 lg:col-span-4 bg-red-600 rounded-lg p-4 md:p-6 flex flex-col justify-between min-h-[300px] lg:min-h-[450px]">
        <div>
          <div class="text-4xl font-black text-center">MERON</div>
          <div class="mt-6 text-center">
            <div class="text-3xl md:text-4xl font-black uppercase">Kap Ulok</div>
            <div class="text-2xl mt-2 font-bold opacity-90">1.85</div>
          </div>
        </div>

        <div class="text-center mt-8 space-y-2">
          <div class="text-2xl md:text-3xl bg-red-700/50 py-1 rounded">1-0-0-1</div>
          <div class="text-4xl md:text-5xl font-black">117,933</div>
          <div class="bg-red-800/40 p-4 rounded-b-lg border-t border-red-400/30">
            <div class="text-xl font-bold">PAYOUT</div>
            <div class="text-3xl md:text-4xl font-bold">183.29</div>
          </div>
        </div>
      </div>

      <div class="order-2 lg:col-span-2 bg-yellow-400 text-black rounded-lg p-6 flex flex-col justify-center items-center min-h-[150px] lg:min-h-full">
        <div class="text-3xl lg:text-4xl font-bold text-yellow-900">DRAW</div>
        <div class="text-4xl font-black mt-2">800</div>
        <div class="mt-4 text-2xl bg-yellow-500/30 py-2 w-full text-center font-bold rounded">1 - 7</div>
      </div>

      <div class="order-3 lg:col-span-4 bg-blue-600 rounded-lg p-4 md:p-6 flex flex-col justify-between min-h-[300px] lg:min-h-[450px]">
        <div>
          <div class="text-4xl font-black text-center">WALA</div>
          <div class="mt-6 text-center">
            <div class="text-3xl md:text-4xl font-black uppercase">Team Kala</div>
            <div class="text-2xl mt-2 font-bold opacity-90">1.95</div>
          </div>
        </div>

        <div class="text-center mt-8 space-y-2">
          <div class="text-2xl md:text-3xl bg-blue-700/50 py-1 rounded">0-0-1-1</div>
          <div class="text-4xl md:text-5xl font-black">110,094</div>
          <div class="bg-blue-800/40 p-4 rounded-b-lg border-t border-blue-400/30">
            <div class="text-xl font-bold">PAYOUT</div>
            <div class="text-3xl md:text-4xl font-bold">196.35</div>
          </div>
        </div>
      </div>

      <div class="order-4 lg:order-first lg:col-span-2 bg-blue-800/50 backdrop-blur-sm rounded-lg p-4 space-y-6">
        <div>
          <div class="text-lg font-bold mb-3 border-b border-blue-600 pb-1">SUMMARY</div>
          <div class="grid grid-cols-2 lg:grid-cols-1 gap-1 text-sm md:text-base">
            <div class="flex justify-between font-bold bg-red-600 p-2 rounded-s lg:rounded-none">
              <span>MERON</span><span>38</span>
            </div>
            <div class="flex justify-between font-bold bg-blue-600 p-2 rounded-e lg:rounded-none">
              <span>WALA</span><span>40</span>
            </div>
            <div class="flex justify-between font-bold bg-yellow-500 p-2 text-black rounded-s lg:rounded-none">
              <span>DRAW</span><span>6</span>
            </div>
            <div class="flex justify-between font-bold bg-gray-600 p-2 rounded-e lg:rounded-none">
              <span>CANCEL</span><span>3</span>
            </div>
          </div>
        </div>

        <div class="pt-2">
          <div class="text-lg font-bold mb-2 flex justify-between items-center">
            <span>HISTORY</span>
            <span class="text-xs opacity-60 uppercase">Winner</span>
          </div>
          <div class="space-y-2 max-h-48 lg:max-h-none overflow-y-auto pr-2">
            <div v-for="fight in [
              {id: 97, win: 'WALA', color: 'text-cyan-300'},
              {id: 96, win: 'MERON', color: 'text-pink-400'},
              {id: 95, win: 'WALA', color: 'text-cyan-300'},
              {id: 94, win: 'MERON', color: 'text-pink-400'}
            ]" :key="fight.id" class="flex justify-between items-center py-1 border-b border-blue-700/50 font-bold">
              <span class="text-blue-300">#{{ fight.id }}</span>
              <span :class="fight.color">{{ fight.win }}</span>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</template>