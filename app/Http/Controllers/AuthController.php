<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStaff;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'username' => 'required|string|max:255|unique:users',
                'email' => 'nullable|string|email|max:255|unique:users',
                'password' => 'required|string',
                'user_type' => 'required|string',
            ]);
    
            $user = User::create([
                'name' => $validated['name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => bcrypt($validated['password']),
                'user_type' => $validated['user_type'],
            ]);
    
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                ],
                'auth_token' => $token,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation Failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }
    
    // Login user
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);
    
        if (!Auth::attempt($credentials)) {
            throw ValidationException::withMessages([
                'username' => ['The provided credentials are incorrect.'],
            ]);
        }
    
        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;
    
        $warehouse = Warehouse::where('user_id', $user->id)->first();
        $warehouseStaff = WarehouseStaff::where('user_id', $user->id)->first();


        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'user_type' => $user->user_type,
                'warehouse_id' => $warehouse ? $warehouse->id : ($warehouseStaff ? $warehouseStaff->warehouse_id : null), // Handle warehouse_id gracefully
            ],
            'auth_token' => $token,
        ]);
    }
    
    // Logout user
    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json(['message' => 'Logged out successfully.']);
    }


    public function createWarehouseStaff(Request $request)
{
    // Validate input
    $validated = $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'username' => 'required|string|unique:users',
        'password' => 'required|string|min:3',
        'warehouse_id' => 'required|exists:warehouses,id',
    ]);

    // Create the user
    $user = User::create([
        'name' => $validated['name'],
        'email' => $validated['email'],
        'username' => $validated['username'],
        'password' => $validated['password'],
        'user_type' => 'staff', // Set user_type as trader
    ]);

    // Assign the user to the warehouse
    WarehouseStaff::create([
        'warehouse_id' => $validated['warehouse_id'], // From request
        'user_id' => $user->id, // Newly created user
    ]);

    return response()->json([
        'message' => 'Staff member created and assigned to warehouse successfully.',
        'user' => $user,
    ], 201);
}



public function indexStaff(Request $request)
{
    $warehouseId = $request->header('warehouse_id'); // Get warehouse_id from the request header

    if (!$warehouseId) {
        return response()->json(['error' => 'Warehouse ID is required'], 400);
    }

    $staff = WarehouseStaff::where('warehouse_id', $warehouseId)->with('user')->get();

    return response()->json($staff, 200);
}
}
