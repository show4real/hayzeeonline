<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Referrer;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::staffAndAdmins()
            ->searchAll($request->search)
            ->filterRole($request->role)
            ->paginate($request->rows, ['*'], 'page', $request->page);

        return response()->json(compact('users'));
    }



    public function show(User $user)
    {

        return response()->json(compact('user'));
    }



    public function create(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => 'required|string|min:6',
            'role' => 'required|in:customer,admin,staff,referrer',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'admin' => $this->resolveRole($request->role),
            'password' => bcrypt($request->password),
        ]);

        return response()->json(compact('user'), 201);
    }



    public function update($id, Request $request)
    {
        $user = User::findOrFail($id);

        $this->validate($request, [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $user->id,
            'phone' => 'sometimes|required|unique:users,phone,' . $user->id,
            'password' => 'sometimes|nullable|string|min:6',
            'role' => 'sometimes|required|in:customer,admin,staff,referrer',
        ]);

        $user->fill($request->only(['name', 'email', 'phone', 'address']));

        if ($request->filled('role')) {
            $user->admin = $this->resolveRole($request->role);
        }

        if ($request->filled('password')) {
            $user->password = bcrypt($request->password);
        }

        $user->save();

        return response()->json(compact('user'));
    }



    /**
     * Map a role name to its "admin" column value.
     * customer = 0, admin = 1, staff = 2, referrer = null.
     */
    private function resolveRole($role)
    {
        $map = [
            'customer' => 0,
            'admin' => 1,
            'staff' => 2,
            'referrer' => null,
        ];

        return $map[$role] ?? 0;
    }



    public function delete($id, Request $request)
    {
        $user = User::findOrFail($id);

        

        $user->delete();
        $referrer = Referrer::where('user_id', $id)->first();

        if($referrer){
            $referrer->delete();
        }

        return response()->json(true);
    }
}