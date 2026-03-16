composer install
npm run build; npm run dev;
php artisan migrate:fresh --seed

php artisan serve
php artisan serve --host=192.168.254.200 --port=80
## change .env and vite.config.ts if you want to change ip address

php artisan reverb:start
php artisan route:list
php artisan config:clear; php artisan cache:clear; php artisan route:clear


## LAMP server setup
https://gemini.google.com/app/50beccd009083a04

## Firefox silent print
To enable silent, automatic printing in Firefox (bypassing the print dialog), type about:config in the address bar, search for print.always_print_silent, and set it to true. Additionally, set print.show_print_progress to false to hide the print status bar. Use this with a single default printer.

TODO:
1. Can change percentage per round, default to 6%
2. Can redeclare winner

REVENUE:

public function eventRevenue($eventId)
{
    $event = Event::findOrFail($eventId);
    $commissionMultiplier = ($event->house_percent ?? 6.00) / 100;

    // Get all rounds for this event with their ticket sums
    $revenueData = Round::where('event_id', $eventId)
        ->where('status', 'finished') // Only count settled rounds
        ->withSum(['tickets as meron_total' => fn($q) => $q->where('side', 'meron')], 'amount')
        ->withSum(['tickets as wala_total' => fn($q) => $q->where('side', 'wala')], 'amount')
        ->withSum(['tickets as draw_total' => fn($q) => $q->where('side', 'draw')], 'amount')
        ->get()
        ->map(function ($round) use ($commissionMultiplier) {
            $totalMainPool = $round->meron_total + $round->wala_total;
            $plasada = $totalMainPool * $commissionMultiplier;
            
            // If Draw happens, house pays out (7x). If not, house keeps Draw bets.
            $drawProfit = ($round->winner === 'draw') 
                ? ($round->draw_total - ($round->draw_total * 7)) 
                : $round->draw_total;

            return [
                'round_number' => $round->round_number,
                'plasada' => $plasada,
                'draw_profit' => $drawProfit,
                'total_round_income' => $plasada + $drawProfit
            ];
        });

    return Inertia::render('Admin/EventRevenue', [
        'event' => $event,
        'breakdown' => $revenueData,
        'total_revenue' => $revenueData->sum('total_round_income'),
        'total_plasada' => $revenueData->sum('plasada'),
        'total_draw_income' => $revenueData->sum('draw_profit'),
    ]);
}

dashboard:
<template>
  <div class="p-6 bg-gray-900 text-white">
    <h1 class="text-2xl font-bold mb-6">Revenue Report: {{ event.name }}</h1>

    <div class="grid grid-cols-3 gap-6 mb-8">
      <div class="p-4 bg-blue-800 rounded-lg">
        <p class="text-sm opacity-70">Total Plasada ({{ event.house_percent }}%)</p>
        <p class="text-3xl font-bold">₱{{ total_plasada.toLocaleString() }}</p>
      </div>
      <div class="p-4 bg-yellow-700 rounded-lg">
        <p class="text-sm opacity-70">Draw Net Profit</p>
        <p class="text-3xl font-bold">₱{{ total_draw_income.toLocaleString() }}</p>
      </div>
      <div class="p-4 bg-green-700 rounded-lg">
        <p class="text-sm opacity-70">Total Net Revenue</p>
        <p class="text-3xl font-bold">₱{{ total_revenue.toLocaleString() }}</p>
      </div>
    </div>

    <table class="w-full text-left border-collapse">
      <thead>
        <tr class="border-b border-gray-700 uppercase text-xs">
          <th class="p-3">Round #</th>
          <th class="p-3">Plasada</th>
          <th class="p-3">Draw Income</th>
          <th class="p-3 text-right">Total</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in breakdown" :key="item.round_number" class="border-b border-gray-800">
          <td class="p-3">Fight {{ item.round_number }}</td>
          <td class="p-3 text-blue-400">₱{{ item.plasada.toFixed(2) }}</td>
          <td class="p-3 text-yellow-400">₱{{ item.draw_profit.toFixed(2) }}</td>
          <td class="p-3 text-right font-bold">₱{{ item.total_round_income.toFixed(2) }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>