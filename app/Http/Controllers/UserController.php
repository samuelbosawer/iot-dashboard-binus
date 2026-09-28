<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Display a searchable, paginated list of users.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));

        $users = User::query()
            ->when($search !== '', fn ($query) => $query->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        return view('users.form', [
            'user' => new User(['role' => UserRole::Support]),
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('users.index')->with('status', 'User berhasil ditambahkan.');
    }

    /**
     * Show the form for editing the given user.
     */
    public function edit(User $user): View
    {
        return view('users.form', [
            'user' => $user,
            'roles' => UserRole::cases(),
        ]);
    }

    /**
     * Update the given user; the password is only changed when a new one is filled in.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $user->update(array_filter(
            $request->validated(),
            fn (mixed $value, string $key): bool => $key !== 'password' || filled($value),
            ARRAY_FILTER_USE_BOTH,
        ));

        return redirect()->route('users.index')->with('status', 'User berhasil diperbarui.');
    }

    /**
     * Delete the given user; admins cannot delete their own account.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'User berhasil dihapus.');
    }
}
