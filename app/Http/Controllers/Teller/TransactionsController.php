<?php

namespace App\Http\Controllers\Teller;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TransactionsController extends Controller
{
    public function adjustWallet(Request $request)
    {
        $request->validate([
            'supervisor_key' => 'required|string',
            'teller_id' => 'required|integer',
            'event_id' => 'required|integer',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|string|in:cash_in,cash_out'
        ]);

        $supervisor = User::where('qr_code_key', $request->supervisor_key)
            ->where('role', 'supervisor')
            ->first();

        if (!$supervisor) {
            return response()->json(['message' => 'Invalid supervisor key'], 403);
        }

        return DB::transaction(function () use ($request, $supervisor) {
            // Find the specific wallet for this event
            $wallet = DB::table('event_teller')
                ->where('user_id', $request->teller_id)
                ->where('event_id', $request->event_id)
                ->first();

            if (!$wallet) {
                return response()->json(['message' => 'Teller is not assigned to this event'], 404);
            }

            $oldBalance = $wallet->current_wallet;
            $amount = $request->amount;
            $newBalance = ($request->type === 'cash_in') 
                ? $oldBalance + $amount 
                : $oldBalance - $amount;

            if ($newBalance < 0) {
                return response()->json(['message' => 'Insufficient teller balance'], 422);
            }

            // 3. Update the Event Wallet
            DB::table('event_teller')
                ->where('id', $wallet->id)
                ->update([
                    'current_wallet' => $newBalance,
                    'updated_at' => now()
                ]);

            // 4. Record the Transaction history
            DB::table('transactions')->insert([
                'teller_id' => $request->teller_id,
                'event_id' => $request->event_id,
                'direction' => ($request->type === 'cash_in') ? 'in' : 'out',
                'type' => $request->type, // 'cash_in' or 'cash_out'
                'amount' => $amount,
                'created_at' => now(),
                'authorized_by' => $supervisor->id,
                'balance_before' => $oldBalance,
                'balance_after' => $newBalance
            ]);

            return response()->json([
                'message' => 'Wallet adjusted successfully',
                'new_balance' => $newBalance
            ]);
        });
    }
}
