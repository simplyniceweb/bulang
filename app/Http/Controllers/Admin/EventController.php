<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Event;
use Inertia\Inertia;

class EventController extends Controller
{
    public function index()
    {
        $events = Event::latest()->paginate(10)->withQueryString();

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
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
            $update['started_at'] = now();
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
