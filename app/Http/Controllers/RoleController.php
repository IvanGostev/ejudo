<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function setRole(Request $request)
    {
        $request->validate([
            'role' => 'required|string|in:Отходообразователь,Переработчик отходов',
        ]);

        session(['user_role' => $request->role]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back();
    }
}
