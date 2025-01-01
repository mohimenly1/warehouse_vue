<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function activateUser(Request $request, $id)
{
    $user = User::findOrFail($id);

    if (auth()->user()->user_type !== 'admin') {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    if (!$user->is_paid) {
        return response()->json(['message' => 'User has not paid yet.'], 400);
    }

    $user->update(['status' => 'active']);

    return response()->json(['message' => 'User activated successfully.']);
}


public function index()
{
    // Fetch all users with their subscription info
    $users = User::with('subscription')->get();

    return response()->json([
        'users' => $users,
    ]);
}

public function updateStatus(Request $request, $id)
{
    $validated = $request->validate([
        'status' => 'required|in:active,inactive',
        'is_paid' => 'required|boolean',
    ]);

    $user = User::findOrFail($id);
    $user->update([
        'status' => $validated['status'],
        'is_paid' => $validated['is_paid'],
    ]);

    return response()->json(['message' => 'User status updated successfully']);
}



public function destroy($id)
{
    $user = User::find($id);

    if (!$user) {
        return response()->json([
            'message' => 'User not found.'
        ], 404);
    }

    $user->delete();

    return response()->json([
        'message' => 'User deleted successfully.'
    ], 200);
}
}
