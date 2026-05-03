<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Round;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $events = Event::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        $tellers = User::where('role', 'teller')->where('status', 'active')->get();
        return Inertia::render('Admin/Events/Create', [
            'availableTellers' => $tellers
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'house_percent' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:inactive,active,closed',
            'tellers' => 'array',
            'tellers.*.id' => 'exists:users,id',
            'tellers.*.amount' => 'required|numeric|min:0',
            'supervisor_wallet' => 'required|numeric|min:0'
        ]);

        if ($request->status === 'active') {

            if (Event::where('status','active')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Only one event can be active.'
                ]);
            } else {
                $update['started_at'] = now();
            }

        }

        $event = Event::create($request->only('name', 'house_percent', 'status', 'supervisor_wallet'));

        // Attach tellers with their starting wallets
        if ($request->has('tellers')) {
            $syncData = [];
            foreach ($request->tellers as $teller) {
                $syncData[$teller['id']] = [
                    'initial_wallet' => $teller['amount'],
                    'current_wallet' => $teller['amount'],
                ];
            }
            $event->tellers()->sync($syncData);
        }

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully.');
    }

    public function show(Event $event, Request $request)
    {
        $eventId = $event->id;
        $event = Event::findOrFail($eventId);

        $rounds = Round::where('event_id', $eventId)
            ->where('status', 'closed') // ✅ correct enum
            ->selectRaw("
                round_number,
                total_meron,
                total_wala,
                house_cut as plasada,

                CASE 
                    WHEN winner = 'draw' THEN total_draw - (total_draw * 7)
                    ELSE total_draw
                END as draw_profit,

                house_cut + 
                CASE 
                    WHEN winner = 'draw' THEN total_draw - (total_draw * 7)
                    ELSE total_draw
                END as total_round_income
            ")
            ->orderBy('round_number')
            ->get();

        return Inertia::render('Admin/Events/Revenue', [
            'event' => $event,
            'breakdown' => $rounds,

            // ✅ Totals computed efficiently from collection
            'total_revenue' => $rounds->sum('total_round_income'),
            'total_plasada' => $rounds->sum('plasada'),
            'total_draw_income' => $rounds->sum('draw_profit'),
        ]);
    }

    public function edit(Event $event)
    {
        // Load the assigned tellers with their pivot data
        $event->load(['tellers' => function($query) {
            $query->select('users.id', 'users.name')
                ->withPivot('initial_wallet');
        }]);

        $availableTellers = User::where('role', 'teller')
            ->where('status', 'active')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Events/Edit', compact('event'), [
            'event' => $event,
            'availableTellers' => $availableTellers
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'house_percent' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:inactive,active,closed',
            'tellers' => 'array',
            'tellers.*.id' => 'exists:users,id',
            'tellers.*.amount' => 'required|numeric',
            'supervisor_wallet' => 'required|numeric|min:0'
        ]);

        if ($request->status === 'active') {
            // Check if ANOTHER event is already active
            $activeExists = Event::where('status', 'active')
                ->where('id', '!=', $event->id) // Exclude the current record
                ->exists();

            if ($activeExists) {
                throw ValidationException::withMessages([
                    'status' => 'Only one event can be active at a time.'
                ]);
            }
        }

        if ($event->status === 'active' && $request->status === 'closed') {
            $update['ended_at'] = now();
        } elseif ($event->status === 'inactive' && $request->status === 'active') {
            $update['started_at'] = now();
            $update['ended_at'] = null;
        }

        $event->update($request->only('name', 'house_percent', 'status'));

        // Prepare sync data
        $syncData = [];
        foreach ($request->tellers as $teller) {
            $syncData[$teller['id']] = [
                'initial_wallet' => $teller['amount'],
                // Don't overwrite current_wallet if the event is already active!
                // Only set it if it's a new assignment.
            ];
        }

        $event->tellers()->sync($syncData);

        return redirect()->route('admin.events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('admin.events.index')->with('success', 'Event deleted successfully.');
    }

    public function eventTellerAudit(Event $event)
    {
        // 🧾 Round revenue (same as before)
        $rounds = Round::where('event_id', $event->id)
            ->where('status', 'closed')
            ->selectRaw("
                id,
                round_number,
                total_meron,
                total_wala,
                total_draw,
                house_cut as plasada,

                CASE 
                    WHEN winner = 'draw' THEN total_draw - (total_draw * 7)
                    ELSE total_draw
                END as draw_profit,

                house_cut + 
                CASE 
                    WHEN winner = 'draw' THEN total_draw - (total_draw * 7)
                    ELSE total_draw
                END as total_round_income
            ")
            ->orderBy('round_number')
            ->get();

        return Inertia::render('Admin/Events/TellerAudit', [
            'event' => $event,
            'rounds' => $rounds,
            // 'tellerBreakdown' => $tellerBreakdown,
        ]);
    }

    public function breakdown($roundId)
    {
        $tellers = DB::table('tickets')
            ->selectRaw("
                teller_id,
                COUNT(id) as ticket_count,
                SUM(CASE WHEN side = 'meron' THEN amount ELSE 0 END) as meron_total,
                SUM(CASE WHEN side = 'wala' THEN amount ELSE 0 END) as wala_total,
                SUM(CASE WHEN side = 'draw' THEN amount ELSE 0 END) as draw_total,
                SUM(amount) as total_bet
            ")
            ->where('round_id', $roundId)
            ->groupBy('teller_id')
            ->get();

        return response()->json($tellers);
    }

    public function supervisorLedger($eventId)
    {
        $event = Event::findOrFail($eventId);

        $transactions = Transaction::where('event_id', $eventId)
            ->whereIn('type', ['cash_in', 'cash_out'])
            ->with(['teller:id,name', 'authorizedBy:id,name'])
            ->orderBy('created_at', 'asc')
            ->get();

        $totalIn = $transactions->where('type', 'cash_in')->sum('amount');
        $totalOut = $transactions->where('type', 'cash_out')->sum('amount');
        $startingHand = $event->supervisor_wallet;

        return Inertia::render('Admin/Events/SupervisorLedger', [
            'event' => $event,
            'transactions' => $transactions,
            'summary' => [
                'supervisor_hand' => $startingHand - $totalIn + $totalOut,
                'circulated' => $totalIn - $totalOut,
                'total_in' => $totalIn,
                'total_out' => $totalOut,
            ]
        ]);
    }

    public function tellerLedger($eventId)
    {
        $event = Event::with('tellers:id,name')->findOrFail($eventId);

        return Inertia::render('Admin/Events/TellerLedger', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'tellers' => $event->tellers->map(fn ($teller) => [
                'id' => $teller->id,
                'name' => $teller->name,
                'current_wallet' => $teller->pivot->current_wallet,
                'initial_wallet' => $teller->pivot->initial_wallet,
            ]),
        ]);
    }

    public function tellerLedgerDetails($eventId, $tellerId)
    {
        $tickets = Ticket::with('round')
            ->where('event_id', $eventId)
            ->where('teller_id', $tellerId)
            ->orderBy('created_at')
            ->get();

        $balance = 0;

        // =========================
        // LEDGER BUILD
        // =========================
        $ledger = $tickets->map(function ($ticket) use (&$balance) {

            $isPaid = $ticket->status === 'paid';
            $isWonUnpaid = $ticket->status === 'won'
                && $ticket->claimed_at === null
                && $ticket->refunded_at === null;

            $isLost = $ticket->status === 'lost';
            $isRefunded = $ticket->status === 'refunded';

            $result = match (true) {
                $isPaid => 'WIN (PAID)',
                $isWonUnpaid => 'WIN (UNPAID)',
                $isLost => 'LOSE',
                $isRefunded => 'REFUND',
                default => 'PENDING',
            };

            // payout exposure (liability only, not cash movement unless paid)
            $payout = ($isPaid || $isWonUnpaid)
                ? $ticket->potential_payout
                : 0;

            // ledger net movement (cash flow perspective)
            $net = match (true) {
                $isPaid => -$payout,        // cash out
                $isLost => $ticket->amount, // house gain
                $isRefunded => 0,           // neutral (already reversed elsewhere)
                default => 0,               // pending/won not yet realized
            };

            $balance += $net;

            return [
                'time' => $ticket->created_at->format('h:i:s A'),
                'round' => $ticket->round?->round_number,
                'winner' => $ticket->round?->winner,
                'ticket_number' => $ticket->ticket_number,
                'side' => $ticket->side,
                'amount' => $ticket->amount,
                'status' => $ticket->status,
                'result' => $result,
                'payout' => $payout,
                'net' => $net,
                'running_balance' => $balance,
            ];
        });

        // =========================
        // SUMMARY CALCULATION
        // =========================
        $totalBet = $tickets->sum('amount');

        $totalPaid = $tickets
            ->where('status', 'paid')
            ->sum('potential_payout');

        $totalRefund = $tickets
            ->where('status', 'refunded')
            ->sum('amount');

        $unpaidLiability = $tickets
            ->filter(function ($t) {
                return $t->status === 'won'
                    && $t->claimed_at === null
                    && $t->refunded_at === null;
            })
            ->sum('potential_payout');

        // =========================
        // EXPECTED CASH MODEL
        // =========================
        $expectedCash = $totalBet
            - $totalPaid
            - $totalRefund
            - $unpaidLiability;

        // =========================
        // ACTUAL WALLET
        // =========================
        $event = Event::with('tellers')->findOrFail($eventId);

        $pivot = $event->tellers()
            ->where('user_id', $tellerId)
            ->first();

        $actualWallet = $pivot?->pivot->current_wallet ?? 0;

        // =========================
        // VARIANCE
        // =========================
        $variance = $actualWallet - $expectedCash;

        $status = match (true) {
            $variance > 0 => 'OVER',
            $variance < 0 => 'SHORT',
            default => 'BALANCED',
        };

        // =========================
        // RESPONSE
        // =========================
        return response()->json([
            'ledger' => $ledger,

            'summary' => [
                'total_bet' => $totalBet,
                'total_paid' => $totalPaid,
                'unpaid_liability' => $unpaidLiability,
                'total_refund' => $totalRefund,

                'expected_cash' => $expectedCash,
                'actual_wallet' => $actualWallet,

                'variance' => $variance,
                'status' => $status,
            ],
        ]);
    }
}
