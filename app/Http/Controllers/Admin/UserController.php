<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private array $statusMap = ['inactive' => 0, 'active' => 1, 'banned' => 2];

    public function index()
    {
        $users = DB::table('users')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($user) => $this->formatUser($user));

        return view('admin.user', compact('users'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['user', 'staff', 'admin'])],
            'status' => ['required', Rule::in(array_keys($this->statusMap))],
        ]);

        $now = now();
        $id = DB::table('users')->insertGetId([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'role' => $validated['role'],
            'status' => $this->statusMap[$validated['status']],
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return response()->json([
            'message' => 'Đã thêm người dùng',
            'user' => $this->formatUser(DB::table('users')->find($id)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Không tìm thấy người dùng',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'role' => ['required', Rule::in(['user', 'staff', 'admin'])],
            'status' => ['required', Rule::in(array_keys($this->statusMap))],
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'role' => $validated['role'],
            'status' => $this->statusMap[$validated['status']],
            'updated_at' => now(),
        ];
        if (!empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        DB::table('users')->where('id', $id)->update($data);

        return response()->json([
            'message' => 'Cập nhật người dùng thành công',
            'user' => $this->formatUser(DB::table('users')->find($id)),
        ]);
    }

    public function delete($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Không tìm thấy người dùng',
            ], 404);
        }

        if ((int) $id === (int) auth()->id()) {
            return response()->json([
                'message' => 'Không thể xóa tài khoản đang đăng nhập',
            ], 422);
        }

        DB::table('users')->where('id', $id)->delete();

        return response()->json([
            'message' => 'Đã xóa người dùng',
            'id' => $id,
        ]);
    }

    private function formatUser($user): ?array
    {
        if (!$user) {
            return null;
        }

        $statusStr = array_search((int) $user->status, $this->statusMap, true);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'address' => $user->address,
            'role' => $user->role,
            'status' => $statusStr ?: 'active',
            'created_at' => $user->created_at,
        ];
    }
}
