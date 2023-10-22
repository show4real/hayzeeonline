<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;


class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::searchAll($request->search)
            ->paginate($request->rows, ['*'], 'page', $request->page);

        return response()->json(compact('users'));
    }



    public function show(User $user)
    {

        return response()->json(compact('user'));
    }



    public function delete($id, Request $request)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return response()->json(true);
    }
}