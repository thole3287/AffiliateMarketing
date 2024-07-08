<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Http\Response;



class AuthController extends Controller
{

    public function listUsersWithRoleUser(Request $request)
    {
        // Fetch users with the role 'user'
        $users = User::where('role', 'user')->get();

        // Return a JSON response
        return response()->json([
            'message' => 'List of users with role user',
            'users' => $users,
        ], Response::HTTP_OK);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'phone' => 'required|numeric',
            'photo' => 'nullable|string',
            'address' => 'nullable|string',
            // 'birthday' => 'nullable|date',
            'username' => 'required|string|unique:users',
            // 'role' => 'string',
            // 'collaborator' => 'boolean',
            // 'points' => 'integer',
            // 'membership_level' => 'string',
            'zalo_id' => 'nullable|unique:users',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = User::create([
            'name' => $request->name ?? null,
            'email' => $request->email,
            'phone' => $request->phone  ?? null,
            'photo' => $request->photo ?? null,
            'address' => $request->address ?? null,
            // 'birthday' => $request->birthday ?? null,
            'username' => $request->username,
            'password' => bcrypt($request->password),
            'zalo_id' => $request->zalo_id  ?? null,
        ]);

        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], Response::HTTP_CREATED);
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
        // dd( $user);
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

    public function loginOrRegister(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'phone' => 'required|numeric',
            'password' => 'required|string|min:6'
        ]);

        // If validation fails, return error response
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $credentials = $request->only('phone', 'password');

        // Attempt to login
        try {
            if ($token = JWTAuth::attempt($credentials)) {
                return response()->json(['message' => 'Login successful', 'token' => $token], 200);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // Check if Zalo_ID exists
        if ($credentials['password']) {
            $user = User::where('zalo_id', $credentials['password'])->first();

            if (!$user) {
                // If Zalo_ID exists, create password using Zalo_ID
                $credentials['password'];
            }
        }

        // Proceed with registration
        $userData = [
            'phone' => $credentials['phone'],
            'password' => bcrypt($credentials['password']),
            'zalo_id' => $credentials['password'],
            'name' => $request->name ?? null,
            'photo' =>  $request->photo ?? null,
            'username' =>  $request->username ?? null,
            'email' => $request->email ?? null,
            'address'  => $request->address ?? null,
            // 'gender'   => $request->gender ?? null,
        ];

        $user = User::create($userData);

        $token = JWTAuth::fromUser($user);

        return response()->json(['message' => 'Registration successful', 'token' => $token], 200);
    }


    // public function loginWeb(Request $request)
    // {
    //     $credentials = $request->only('username', 'password');

    //     // Thử đăng nhập bằng username
    //     try {
    //         if (!$token = JWTAuth::attempt($credentials)) {
    //             $credentials = $request->only('email', 'password');

    //             if (!$token = JWTAuth::attempt($credentials)) {
    //                 return response()->json(['error' => 'Invalid credentials'], 401);
    //             }
    //         }
    //     } catch (JWTException $e) {
    //         return response()->json(['error' => 'Could not create token'], 500);
    //     }

    //     return response()->json(compact('token'));
    // }
    public function loginWeb(Request $request)
    {
        $credentials = $request->only('username', 'password');

        // Thử đăng nhập bằng username
        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                $credentials = $request->only('email', 'password');

                if (!$token = JWTAuth::attempt($credentials)) {
                    return response()->json(['error' => 'Invalid credentials'], 401);
                }
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        // Sau khi đăng nhập, lấy thông tin người dùng
        $user = Auth::user();

        // Kiểm tra nếu người dùng không phải là admin
        if ($user->role == 'user') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return $this->createNewToken($token, 'Login Admin/SuperAdmin Successfully');
    }

    public function updateProfile(Request $request, $id)
    {
        // Kiểm tra xem người dùng hiện tại có quyền cập nhật thông tin người dùng khác không
        // Ví dụ: Chỉ admin mới có quyền cập nhật thông tin người dùng khác
        // if (Auth::user()->role != 'admin') {
        //     return response()->json(['error' => 'Unauthorized'], 403);
        // }

        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
            'email' => 'nullable|string|email|max:255|unique:users,email,' . $user->id,
            'zalo_id' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'photo' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'birthday' => 'nullable|date',
            'collaborator' => 'nullable|boolean',
            'points' => 'nullable|integer',
            'membership_level' => 'nullable|string|max:255',
            'total_commission' => 'nullable|numeric',
            'affiliate_code' => 'nullable|string|max:255',
            'status' => 'required|in:active,inactive',
            'current_password' => 'nullable|required_with:password',
            'password' => 'nullable|confirmed|min:8',
            'bank_account_name' => 'nullable|string|max:255', // Tên chủ tài khoản
            'bank_account_number' => 'nullable|string|max:255', // Số tài khoản ngân hàng
            'bank_name' => 'nullable|string|max:255', // Tên ngân hàng
        ]);

        $data = $request->only([
            'name', 'username', 'email', 'zalo_id', 'phone', 'photo', 'address',
            'birthday', 'collaborator', 'points', 'membership_level',
            'total_commission', 'balance', 'affiliate_code', 'status',
            'bank_account_name', 'bank_account_number', 'bank_name'
        ]);

        if ($request->filled('password')) {
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json(['error' => 'Current password is incorrect'], 400);
            }
            $data['password'] = Hash::make($request->input('password'));
        }

        $user->update($data);

        return response()->json(['message' => 'Profile updated successfully', 'user' => $user], 200);
    }


    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'photo' => 'nullable|string',
            'address' => 'nullable|string',
            // 'birthday' => 'nullable|date',
            // 'collaborator' => 'boolean',
            // 'points' => 'integer',
            // 'membership_level' => 'string|in:basic,premium',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }
        $user->name = $request->input('name')  ?? $user->name;
        $user->phone = $request->input('phone') ??  $user->phone;
        $user->avatar = $request->input('photo') ??  $user->photo;
        $user->address = $request->input('address') ??  $user->address;
        // $user->birthday = $request->input('birthday')??  $user->birthday;
        // $user->collaborator = $request->input('collaborator') ?? null;
        // $user->points = $request->input('points')?? null;
        // $user->membership_level = $request->input('membership_level')?? null;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Update your profile successfuly',
            'user' => $user
        ]);
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
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->refresh(), 'refresh token successfuly');
    }
    /**
     * Get the authenticated User.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->user());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token, $message)
    {
        return response()->json([
            'message' => $message,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            // 'user' => auth()->user()
        ]);
    }
}
