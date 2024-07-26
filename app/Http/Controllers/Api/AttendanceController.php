<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the attendances.
     */
    public function index(Request $request)
    {
        $query = Attendance::with('user');

        // Search functionality
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereDate('date', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('user', function ($q) use ($searchTerm) {
                      $q->where('name', 'LIKE', "%{$searchTerm}%");
                  });
            });
        }

        // Date range filter
        if ($request->has('from_date') && $request->has('to_date')) {
            $query->whereBetween('date', [$request->from_date, $request->to_date]);
        }

        // Pagination
        $perPage = $request->input('row', 15); // Default to 15 if not specified
        $page = $request->input('page', 1);

        $attendances = $query->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'data' => $attendances->items(),
            'current_page' => $attendances->currentPage(),
            'per_page' => $attendances->perPage(),
            'total' => $attendances->total(),
            'last_page' => $attendances->lastPage(),
        ]);
    }

    /**
     * Store a newly created attendance in storage.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'user_id' => 'required|exists:users,id',
            'date' => 'required|date',
            'check_in' => 'required|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
        ]);

        $attendance = Attendance::create($validatedData);
        return response()->json($attendance, Response::HTTP_CREATED);
    }

    /**
     * Display the specified attendance.
     */
    public function show(Attendance $attendance)
    {
        return response()->json($attendance->load('user'));
    }

    /**
     * Update the specified attendance in storage.
     */
    public function update(Request $request, Attendance $attendance)
    {
        $validatedData = $request->validate([
            'user_id' => 'sometimes|required|exists:users,id',
            'date' => 'sometimes|required|date',
            'check_in' => 'sometimes|required|date_format:H:i',
            'check_out' => 'nullable|date_format:H:i|after:check_in',
        ]);

        $attendance->update($validatedData);
        return response()->json($attendance);
    }

    /**
     * Remove the specified attendance from storage.
     */
    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}