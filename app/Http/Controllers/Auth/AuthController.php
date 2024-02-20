<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator; 


class AuthController extends Controller
{
 
  
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|string',
            'address' => 'nullable|string',
            'birthday' => 'nullable|date',
            'role' => 'string',
            // 'collaborator' => 'boolean',
            // 'points' => 'integer',
            // 'membership_level' => 'string',
            'zalo_id' => 'required|unique:users',
            // 'password' => 'required|string|min:6',

            // 'password' => 'required|string|confirmed|min:6',
        ], [
            'name.required' => 'Name is required!',
            'name.string' => 'Name must be a string!',
            'name.between' => 'Name must be between 2 and 100 characters!',
            'zalo_id.required' => 'Zalo ID is required!',
            'username.required' => 'Username is required!',
            'email.required' => 'Email is required!',
            'email.string' => 'Email must be a string!',
            'email.email' => 'Email must be in the correct format!',
            'email.max' => 'Email can be at most 100 characters long!',
            'email.unique' => 'This email is already registered!',
            'phone.string' => 'Phone must be a string!',
            'phone.max' => 'Phone can be at most 20 characters long!',
            'photo.string' => 'Avatar must be a string!',
            // 'password.required' => 'Password is required!',
            // 'password.string' => 'Password must be a string!',
            // 'password.confirmed' => 'Password confirmation does not match!',
            // 'password.min' => 'Password must be at least 6 characters long!',
            ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::create(array_merge(
            $validator->validated(),
            [
                'password' => bcrypt($request->zalo_id),
                'username' => $request->zalo_id,
                'zalo_id' => $request->zalo_id,
            ]
        ));
        // dd( $user);
        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }
    public function changePassword(Request $request)
    {
        // Validate the request data
        $request->validate([
            'old_password' => 'required',
            'password' => 'required|confirmed|min:6',
        ]);

        // Get the user
        $user = $request->user();

        // Verify the current password
        if (!Hash::check($request->input('old_password'), $user->password)) {
            return response()->json(['error' => 'Old password does not match.'], 400);
        }

        // Update the password
        $user->password = bcrypt($request->input('password'));
        $user->save();

        return response()->json(['message' => 'Password updated successfully.'], 200);
    }



    /**
     * Get a JWT via given credentials.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request){
    	$validator = Validator::make($request->all(), [
            'zalo_id' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        // dd($validator);
        
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        // if (! $token = auth()->attempt($validator->validated())) {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }
        
        if (! $token = auth()->attempt(['zalo_id' => $request->input('zalo_id', $request->zalo_id), 'password' => $request->password])) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        return $this->createNewToken($token);
    }
    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'avatar' => 'nullable|string',
            'address' => 'nullable|string',
            'birthday' => 'nullable|date',
            'collaborator' => 'boolean',
            'points' => 'integer',
            'membership_level' => 'string|in:basic,premium',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $user->name = $request->input('name');
        $user->phone = $request->input('phone');
        $user->avatar = $request->input('avatar');
        $user->address = $request->input('address');
        $user->birthday = $request->input('birthday');
        $user->collaborator = $request->input('collaborator');
        $user->points = $request->input('points');
        $user->membership_level = $request->input('membership_level');
        $user->save();

        return response()->json(['user' => $user]);
    }
    /**
     * Register a User.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    /**
     * Log the user out (Invalidate the token).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout() {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh() {
        return $this->createNewToken(auth()->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile() {
        return response()->json(auth()->user());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            // 'user' => auth()->user()
        ]);
    }

}
