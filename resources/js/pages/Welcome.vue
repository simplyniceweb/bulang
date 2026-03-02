<script setup lang="ts">
</script>

<template>
<div class="min-h-screen bg-blue-900 text-white p-6">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-4">
        <div class="text-2xl font-bold tracking-wide">
            GALERA DE JAYLORD 1 COCK DERBY | FEBRUARY 19, 2026
        </div>

        <div class="text-right">
            <div class="text-xl font-bold">05:35:23 AM</div>
            <div class="bg-red-600 px-4 py-1 rounded text-lg font-bold mt-1 hidden">
                BETTING CLOSED
            </div>
            <div class="bg-green-600 px-4 py-1 rounded text-lg font-bold mt-1">
                BETTING OPEN
            </div>
        </div>
    </div>

    <!-- MAIN GRID -->
    <div class="grid grid-cols-12 gap-4">

        <!-- LEFT SIDEBAR (Fight History + Results) -->
        <div class="col-span-2 bg-blue-800 rounded-lg p-4 space-y-4">

            <div>
                <div class="text-lg font-bold mb-2">RESULTS</div>
                <div class="text-lg">
                    <div class="flex justify-between font-bold bg-red-600 p-2">
                        <span>MERON</span><span>38</span>
                    </div>
                    <div class="flex justify-between font-bold bg-blue-600 p-2">
                        <span>WALA</span><span>40</span>
                    </div>
                    <div class="flex justify-between font-bold bg-yellow-400 p-2 text-black">
                        <span>DRAW</span><span>6</span>
                    </div>
                    <div class="flex justify-between font-bold bg-gray-600 p-2">
                        <span>CANCELLED</span><span>3</span>
                    </div>
                </div>
            </div>

            <div class="border-t border-blue-600 pt-4">
                <div class="text-lg font-bold mb-2">FIGHT # <span class="float-end">WINNER</span></div>

                <div class="space-y-1 border-t border-blue-600 text-lg font-bold">
                    <div class="flex justify-between mt-2">
                        <span>#97</span>
                        <span class="text-cyan-300">WALA</span>
                    </div>
                    <div class="flex justify-between">
                        <span>#96</span>
                        <span class="text-pink-400">MERON</span>
                    </div>
                    <div class="flex justify-between">
                        <span>#95</span>
                        <span class="text-cyan-300">WALA</span>
                    </div>
                    <div class="flex justify-between">
                        <span>#94</span>
                        <span class="text-pink-400">MERON</span>
                    </div>
                </div>
            </div>

        </div>


        <!-- MERON SIDE -->
        <div class="col-span-4 bg-red-600 rounded-lg p-6 flex flex-col justify-between">

            <div>
                <div class="text-4xl font-black text-center">MERON</div>

                <div class="mt-6 text-center">
                    <div class="text-4xl font-black">KAP ULOK</div>
                    <div class="text-2xl mt-2 font-bold">1.85</div>
                </div>
            </div>

            <div class="text-center mt-8 space-y-2">
                <div class="text-3xl bg-red-300">1-0-0-1</div>
                <div class="text-5xl font-black">117,933</div>
                <div class="bg-red-300 p-4">
                    <div class="text-2xl font-bold">PAYOUT</div>
                    <div class="text-4xl font-bold">183.29</div>
                </div>
            </div>

        </div>


        <!-- DRAW CENTER -->
        <div class="col-span-2 bg-yellow-400 text-black rounded-lg p-6 flex flex-col justify-center items-center">

            <div class="text-4xl font-bold">DRAW</div>
            <div class="text-4xl font-black mt-4">800</div>
            <div class="mt-8 text-3xl bg-yellow-300 py-2 w-full text-center">1 - 7</div>

        </div>


        <!-- WALA SIDE -->
        <div class="col-span-4 bg-blue-600 rounded-lg p-6 flex flex-col justify-between">

            <div>
                <div class="text-4xl font-black text-center">WALA</div>

                <div class="mt-6 text-center">
                    <div class="text-4xl font-black">TEAM KALA</div>
                    <div class="text-2xl mt-2 font-bold">1.95</div>
                </div>
            </div>

            <div class="text-center mt-8 space-y-2">
                <div class="text-3xl bg-blue-300 py-2">0-0-1-1</div>
                <div class="text-5xl font-black">110,094</div>
                <div class="bg-blue-300 p-4">
                    <div class="text-2xl font-bold">PAYOUT</div>
                    <div class="text-4xl font-bold">196.35</div>
                </div>
            </div>

        </div>

    </div>

</div>


</template>
