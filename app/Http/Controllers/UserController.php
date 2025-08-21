<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserFormRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index()
    {
        $users = $this->userService->index();
        return response()->json($users, 200);
    }

    public function store(UserFormRequest $request)
    {
        $user = $this->userService->store($request);
        return response()->json($user, 201);
    }

    public function show(string $id)
    {
        $user = $this->userService->show($id);
        return response()->json($user, 200);
    }

    public function update(UserFormRequest $request, string $id)
    {
        $user = $this->userService->update($request->validated(), $id);
        return response()->json($user, 200);
    }

    public function destroy(string $id)
    {
        $this->userService->destroy($id);
        return response('', 204);
    }

    public function employees()
    {
        $employees = $this->userService->getEmployees();
        return response()->json($employees, 200);
    }

    public function clients()
    {
        $clients = $this->userService->getClients();
        return response()->json($clients, 200);
    }

}
