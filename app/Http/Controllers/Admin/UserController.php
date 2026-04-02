<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Linkxtr\QrCode\Facades\QrCode;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            })
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only('search'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|unique:users,username',
            'name' => 'nullable|string|max:255',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,game_master,supervisor,teller',
        ]);

        User::create([
            'username' => $request->username,
            'name' => $request->name,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'qr_code_key' => (string) 'teller_' . Str::ulid()
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'username' => 'required|unique:users,username,' . $user->id,
            'name' => 'nullable|string|max:255',
            'password' => 'nullable|string|min:6',
            'role' => 'required|in:admin,game_master,supervisor,teller',
        ]);

        $data = $request->only('username', 'name', 'role');
        if ($request->password) {
            $data['password'] = Hash::make($request->password);
        }

        if ($user->qr_code_key === null) {
            $data['qr_code_key'] = (string) 'teller_' . Str::ulid();
        }

        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function getQrCode($userId) 
    {
        $user = User::findOrFail($userId);
    
        $qrCode = QrCode::format('png')
                        ->size(500)
                        ->margin(1)
                        ->generate($user->qr_code_key);

        return response($qrCode)->header('Content-Type', 'image/png');
    }
}
