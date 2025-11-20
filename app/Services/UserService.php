<?php

namespace App\Services;

use App\Http\Requests\UserFormRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function index()
    {
        return User::latest()->get();
    }

    public function store(UserFormRequest $request)
    {
        $data = $request->validated();
        $data['password'] = Hash::make($data['password']);

        return User::create($data);
    }

    public function show(string $id)
    {
        return User::findOrFail($id);
    }

    public function update(array $request, string $id)
    {
        $user = User::findOrFail($id);

        if (isset($request['password']) && !empty($request['password'])) {
            $request['password'] = Hash::make($request['password']);
        } else {
            unset($request['password']);
        }

        $user->update($request);
        return $user;
    }

    public function destroy(string $id)
    {
        User::destroy($id);
    }

    public function getEmployees()
    {
        return User::where('role', 'employee')->where('is_active', true)->get();
    }

    public function getClients()
    {
        return User::where('role', 'client')->where('is_active', true)->get();
    }
    public function getLivreurs()
    {
        return User::where('role', 'livreur')->where('is_active', true)->get();
    }
}
