<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $users = User::query();
            
            return DataTables::of($users)
                ->addColumn('actions', function ($user) {
                    $editBtn = '<a href="' . route('admin.users.edit', $user) . '" class="btn btn-sm btn-outline-primary me-1">
                        <i class="bi bi-pencil"></i> Edit
                    </a>';
                    
                    $deleteBtn = '';
                    if ($user->id !== auth()->id()) {
                        $deleteBtn = '<button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteUser(' . $user->id . ', \'' . addslashes($user->name) . '\')">
                            <i class="bi bi-trash"></i> Delete
                        </button>';
                    }
                    
                    return $editBtn . $deleteBtn;
                })
                ->addColumn('badge', function ($user) {
                    return $user->id === auth()->id() 
                        ? '<span class="badge bg-primary">You</span>' 
                        : '';
                })
                ->editColumn('created_at', function ($user) {
                    return $user->created_at->format('d M Y H:i');
                })
                ->rawColumns(['actions', 'badge'])
                ->make(true);
        }
        
        return view('admin.users.index');
    }

    public function create(): View
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        User::create($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil ditambahkan!');
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil diupdate!');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Tidak bisa menghapus user sendiri!');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User berhasil dihapus!');
    }
}
