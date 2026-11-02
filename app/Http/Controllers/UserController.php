<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private const ROLES = ['owner', 'manager', 'cashier'];

    public function page()
    {
        return view('pages.users', [
            'users' => User::whereNull('archived_at')->orderBy('id')->get(),
            'archivedUsers' => User::whereNotNull('archived_at')->orderByDesc('archived_at')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->normalize($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(self::ROLES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user = User::create([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'status' => $data['status'],
            'password' => $data['password'],
        ]);

        return response()->json(['success' => true, 'user' => $user]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->normalize($request);
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'role' => ['required', Rule::in(self::ROLES)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        if ($user->id === $request->user()->id && ($data['role'] !== $user->role || $data['status'] !== 'active')) {
            return response()->json(['success' => false, 'message' => 'You cannot change your own role or status.'], 422);
        }

        $user->fill([
            'name' => trim($data['first_name'] . ' ' . $data['last_name']),
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        if (!empty($data['password'])) {
            $user->password = $data['password'];
        }

        $user->save();

        return response()->json(['success' => true, 'user' => $user]);
    }

    /** The form sends labels like "Owner"/"Active"; the database stores lowercase values. */
    private function normalize(Request $request): void
    {
        $request->merge([
            'role' => strtolower((string) $request->input('role')),
            'status' => strtolower((string) $request->input('status')),
        ]);
    }

    public function archive(Request $request, User $user): JsonResponse
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'You cannot archive your own account.'], 422);
        }

        $user->update(['archived_at' => now()]);

        return response()->json(['success' => true]);
    }

    public function restore(User $user): JsonResponse
    {
        $user->update(['archived_at' => null]);

        return response()->json(['success' => true]);
    }
}
