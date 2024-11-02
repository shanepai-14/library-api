<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
class UserController extends Controller
{
    /**
     * Register a new user.
     */
    // public function register(Request $request)
    // {
    //     $request->validate([
    //         'email' => 'required|string|email|max:255|unique:users',
    //         'password' => 'required|string|min:8|confirmed',
    //         'first_name' => 'required|string',
    //         'middle_name' => 'nullable|string',
    //         'last_name' => 'required|string',
    //         'course' => 'required|string',
    //         'year_level' => 'required|string',
    //         'gender' => 'required|string',
    //         'profile_picture' => 'required|string',
    //         'role' => 'nullable|string',
    //     ]);

    //     $user = User::create([
    //         'role' => $request->role ?? 'student', // Default role to 'student' if not provided
    //         'first_name' => $request->first_name,
    //         'middle_name' => $request->middle_name,
    //         'last_name' => $request->last_name,
    //         'course' => $request->course,
    //         'year_level' => $request->year_level,
    //         'gender' => $request->gender,
    //         'profile_picture' => $request->profile_picture,
    //         'email' => $request->email,
    //         'password' => Hash::make($request->password),  
    //     ]);

    //     $token = $user->createToken('auth_token')->plainTextToken;

    //     return response()->json([
    //         'user' => $user,
    //         'token' => $token,
    //         'token_type' => 'Bearer',
    //     ], 201);
    // }

    public function register(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'password_confirmation' => 'required|string|min:8',
                'first_name' => 'required|string',
                'middle_name' => 'nullable|string',
                'last_name' => 'required|string',
                'course' => 'required|string',
                'year_level' => 'required|string',
                'gender' => 'required|string',
                'contact_number' => 'required|string',
                'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
                'role' => 'nullable|string',
                'birthday' => 'required|date',
            ], [
                'email.unique' => 'This email address has already been taken',
                'password.confirmed' => 'Password confirmation does not match',
                'profile_picture.required' => 'Profile picture is required',
                // Add any other custom messages you want
            ]);
    
            $profilePicturePath = null;
            if ($request->hasFile('profile_picture')) {
                $image = $request->file('profile_picture');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('public/profile_pictures', $filename);
                $profilePicturePath = str_replace('public/', '', $path);
            }
    
            $user = User::create([
                'role' => $request->role ?? 'student',
                'first_name' => $request->first_name,
                'middle_name' => $request->middle_name,
                'last_name' => $request->last_name,
                'course' => $request->course,
                'year_level' => $request->year_level,
                'gender' => $request->gender,
                'profile_picture' => $profilePicturePath,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'password' => Hash::make($request->password),
                'birthday' => $request->birthday,
            ]);
    
            $token = $user->createToken('auth_token')->plainTextToken;
    
            return response()->json([
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer',
            ], 201);
    
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred during registration',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Login user and create token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = User::where('email', $request->email)->firstOrFail();
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user' => $user,
            'token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Log the user out (Invalidate the token).
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out successfully']);
    }

    /**
     * Get the authenticated User.
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $query = User::query()->where('role','student');

        if ($request->has('search')) {
            $searchTerm = $request->search == "all" ? "" : $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('id_number', 'LIKE', "%{$searchTerm}%");
            });
        }

        $perPage = $request->input('row', 15);
        $page = $request->input('page', 1);

        $users = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $users->items(),
            'current_page' => $users->currentPage(),
            'per_page' => $users->perPage(),
            'total' => $users->total(),
            'last_page' => $users->lastPage(),
        ]);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return response()->json($user);
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            $validatedData = $request->validate([
                'first_name' => 'sometimes|required|string|max:255',
                'last_name' => 'sometimes|required|string|max:255',
                'middle_name' => 'nullable|string|max:255',
                'contact_number' => 'sometimes|required|string|max:255',
                'password' => 'sometimes|required|string|min:8|confirmed',
                'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
             
            ]);
    
            // Handle profile picture upload
            if ($request->hasFile('profile_picture')) {
                // Delete old profile picture if it exists
                if ($user->profile_picture) {
                    $oldPath = storage_path('app/public/' . $user->profile_picture);
                    if (file_exists($oldPath)) {
                        unlink($oldPath);
                    }
                }
    
                // Store new profile picture
                $image = $request->file('profile_picture');
                $filename = time() . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('public/profile_pictures', $filename);
                $validatedData['profile_picture'] = str_replace('public/', '', $path);
            }
    
            // Handle password update
            if (isset($validatedData['password'])) {
                $validatedData['password'] = Hash::make($validatedData['password']);
            }
    
            // Update user
            $user->update($validatedData);

            $user = $user->fresh();
    
            // Return response with updated user data
            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user
            ], 200);
    
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the user',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return response()->json(null, 204);
    }
    // public function checkStudent(Request $request)
    // {
    //     $request->validate([
    //         'id_number' => 'required|string',
    //     ]);

    //     $student = User::where('id_number', $request->id_number)
    //                    ->where('role', 'student')
    //                    ->first();

    //     if ($student) {
    //         return response()->json([
    //             'found' => true,
    //             'student' => [
    //                 'id' => $student->id,
    //                 'first_name' => $student->first_name,
    //                 'id_number' => $student->id_number,
    //                 // Add any other fields you want to return
    //             ]
    //         ]);
    //     } else {
    //         return response()->json([
    //             'found' => false,
    //             'message' => 'Student not found'
    //         ], 404);
    //     }
    // }

//     public function checkStudent(Request $request)
// {
//     $request->validate([
//         'id_number' => 'required|string',
//     ]);

//     $student = User::where('id_number', $request->id_number)
//                    ->where('role', 'student')
//                    ->first();

//     if (!$student) {
//         return response()->json([
//             'found' => false,
//             'message' => 'Student not found'
//         ], 404);
//     }

//     $today = Carbon::today();
//     $attendance = Attendance::where('user_id', $student->id)
//         ->where('date', $today)
//         ->first();

//     if (!$attendance) {
//         // No check-in or check-out for today
//         return response()->json([
//             'found' => true,
//             'message' => 'No check-in or check-out for today',
//             'student' => [
//                 'id' => $student->id,
//                 'first_name' => $student->first_name,
//                 'id_number' => $student->id_number,
//             ]
//         ]);
//     } elseif ($attendance->check_out === null) {
//         // Check-out the user
//         $attendance->update([
//             'check_out' => Carbon::now(),
//         ]);

//         return response()->json([
//             'found' => true,
//             'message' => 'Check-out successful',
//             'student' => [
//                 'id' => $student->id,
//                 'first_name' => $student->first_name,
//                 'id_number' => $student->id_number,
//             ],
//             'attendance' => $attendance
//         ]);
//     } else {
//         // Already checked in and out for today
//         return response()->json([
//             'found' => true,
//             'message' => 'You have already checked in and out for today',
//             'attendance' => $attendance,
//             'student' => [
//                 'id' => $student->id,
//                 'first_name' => $student->first_name,
//                 'id_number' => $student->id_number,
//             ]
//         ]);
//     }
// }

public function checkStudent(Request $request)
{
    $request->validate([
        'id_number' => 'required|string',
    ]);

    $student = User::where('id_number', $request->id_number)
                   ->where('role', 'student')
                   ->first();

    if (!$student) {
        return response()->json([
            'found' => false,
            'message' => 'Student not found'
        ], 404);
    }

    $now = Carbon::now();
    $latestAttendance = Attendance::where('user_id', $student->id)
        ->whereDate('date', $now->toDateString())
        ->latest('check_in')
        ->first();

    $studentInfo = [
        'id' => $student->id,
        'first_name' => $student->first_name,
        'id_number' => $student->id_number,
    ];

    if (!$latestAttendance) {
        // No check-in for today
        return response()->json([
            'found' => true,
            'message' => 'No check-in for today',
            'student' => $studentInfo,
            'status' => 'ready_for_checkin'
        ]);

    } elseif ($latestAttendance->check_out === null) {
        // Last entry is a check-in, ready for check-out
        $latestAttendance->update([
                        'check_out' => Carbon::now(),
                    ]);

        return response()->json([
            'found' => true,
            'message' => 'Check-out successful',
            'student' => $studentInfo,
            'attendance' => $latestAttendance,
            'status' => 'ready_for_checkout'
        ]);

    } else {
        // Last entry is a check-out, ready for new check-in
        return response()->json([
            'found' => true,
            'message' => 'Ready for new check-in',
            'student' => $studentInfo,
            'last_attendance' => $latestAttendance,
            'status' => 'ready_for_checkin'
        ]);
    }
}
}