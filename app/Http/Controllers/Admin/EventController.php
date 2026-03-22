<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\User;
use Illuminate\Http\Request;
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
            'tellers.*.amount' => 'required|numeric|min:0'
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

        $event = Event::create($request->only('name', 'house_percent', 'status'));

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
            'tellers.*.amount' => 'required|numeric'
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
}
