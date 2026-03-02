<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
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
        return Inertia::render('Admin/Events/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'house_percent' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:inactive,active,closed',
        ]);

        $update = [
            'name' => $request->name,
            'house_percent' => $request->house_percent,
            'status' => $request->status
        ];

        if ($request->status === 'active') {

            if (Event::where('status','active')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Only one event can be active.'
                ]);
            } else {
                $update['started_at'] = now();
            }

        }

        Event::create($update);

        return redirect()->route('admin.events.index')->with('success', 'Event created successfully.');
    }

    public function edit(Event $event)
    {
        return Inertia::render('Admin/Events/Edit', compact('event'));
    }

    public function update(Request $request, Event $event)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'house_percent' => 'required|numeric|min:0|max:100',
            'status' => 'required|in:inactive,active,closed',
        ]);

        $update = [
            'name' => $request->name,
            'house_percent' => $request->house_percent,
            'status' => $request->status,
        ];

    
        if ($request->status === 'active') {
            if (Event::where('status','active')->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Only one event can be active.'
                ]);
            }
        }

        if ($event->status === 'active' && $request->status === 'closed') {
            $update['ended_at'] = now();
        } elseif ($event->status === 'inactive' && $request->status === 'active') {
            $update['started_at'] = now();
            $update['ended_at'] = null;
        }

        $event->update($update);

        return redirect()->route('admin.events.index')->with('success', 'Event updated successfully.');
    }

    public function destroy(Event $event)
    {
        $event->delete();
        return redirect()->route('admin.events.index')->with('success', 'Event deleted successfully.');
    }
}
