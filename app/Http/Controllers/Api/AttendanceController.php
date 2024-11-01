<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use Carbon\Carbon;

class AttendanceController extends Controller
{
    /**
     * Display a listing of the attendances.
     */


     public function getAnalytics(Request $request)
{
    $year = $request->input('year', 2024);
    $yearLevel = $request->input('year_level', 'all');
    
    // Initialize the months array with all months
    $months = [];
    for ($month = 1; $month <= 12; $month++) {
        $months[$month] = [
            'month' => $month,
            'BSIT' => 0,
            'BEED' => 0,
            'BSED-ENG' => 0,
            'BSED-MATH' => 0,
            'THEO' => 0,
            'SHS'=> 0,
        ];
    }

    $query = Attendance::with('user')
    ->whereYear('date', $year)
    ->join('users', 'attendance.user_id', '=', 'users.id');

// Add year level filter if not 'all'
        if ($yearLevel !== 'all') {
            $query->where('users.year_level', $yearLevel);
        }

// Get attendance data
$attendanceData = $query
    ->selectRaw('
        MONTH(attendance.date) as month, 
        users.course, 
        COUNT(DISTINCT CONCAT(attendance.date, attendance.user_id)) as count
    ')
    ->groupBy('month', 'users.course')
    ->get();


    // Fill in the actual counts
    foreach ($attendanceData as $record) {
        if (isset($months[$record->month][$record->course])) {
            $months[$record->month][$record->course] = $record->count;
        }
    }

    // Convert to indexed array and sort by month
    $result = array_values($months);

    return response()->json($result);
}
public function getDailyAnalytics()
{
    $today = now()->format('Y-m-d');

    $dailyData = Attendance::with('user')
        ->whereDate('date', $today)
        ->join('users', 'attendance.user_id', '=', 'users.id')
        ->selectRaw('
            users.course, 
            COUNT(DISTINCT attendance.user_id) as count
        ')
        ->groupBy('users.course')
        ->get()
        ->pluck('count', 'course')
        ->toArray();


    return response()->json([
        'date' => $today,
        'BSIT' => $dailyData['BSIT'] ?? 0,
        'BEED' => $dailyData['BEED'] ?? 0,
        'BSED-ENG' => $dailyData['BSED-ENG'] ?? 0,
        'BSED-MATH' => $dailyData['BSED-MATH'] ?? 0,
        'THEO' => $dailyData['THEO'] ?? 0,
        'SHS' => $dailyData['SHS'] ?? 0
    ]);
}

public function getMonthlyAnalytics(Request $request)
{
    $year = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);
    
    $startDate = Carbon::create($year, $month, 1);
    $endDate = $startDate->copy()->endOfMonth();
    $daysInMonth = $endDate->day;

    // Initialize all days
    $formattedData = [];
    for ($day = 1; $day <= $daysInMonth; $day++) {
        $formattedData[$day] = [
            'day' => $day,
            'BSIT' => 0,
            'BEED' => 0,
            'BSED-ENG' => 0,
            'BSED-MATH' => 0,
            'THEO' => 0,
            'SHS'=> 0,
        ];
    }

    // Get attendance data
    $attendanceData = Attendance::with('user')
        ->whereYear('date', $year)
        ->whereMonth('date', $month)
        ->join('users', 'attendance.user_id', '=', 'users.id')
        ->selectRaw('DAY(attendance.date) as day, users.course, COUNT(DISTINCT attendance.user_id) as count')
        ->groupBy('day', 'users.course')
        ->get();

    // Fill in the actual data
    foreach ($attendanceData as $record) {
        if (isset($formattedData[$record->day])) {
            $formattedData[$record->day][$record->course] = $record->count;
        }
    }

    return response()->json(array_values($formattedData));
}

public function getWeeklyAnalytics(Request $request)
{
    $year = $request->input('year', now()->year);
    $month = $request->input('month', now()->month);

    $startDate = Carbon::create($year, $month, 1);
    $endDate = $startDate->copy()->endOfMonth();

    // Initialize weeks
    $formattedData = [
        ['week' => 'Week 1'],
        ['week' => 'Week 2'],
        ['week' => 'Week 3'],
        ['week' => 'Week 4']
    ];

    // Initialize course counts for each week
    foreach ($formattedData as &$week) {
        $week['BSIT'] = 0;
        $week['BEED'] = 0;
        $week['BSED-ENG'] = 0;
        $week['BSED-MATH'] = 0;
        $week['THEO'] = 0;
        $week['SHS'] = 0;

    }

    // Get attendance data
    $attendanceData = Attendance::with('user')
        ->whereYear('date', $year)
        ->whereMonth('date', $month)
        ->join('users', 'attendance.user_id', '=', 'users.id')
        ->selectRaw('
            FLOOR((DAY(attendance.date) - 1) / 7) as week_number,
            users.course,
            COUNT(DISTINCT attendance.user_id) as count
        ')
        ->groupBy('week_number', 'users.course')
        ->get();

    // Fill in the actual data
    foreach ($attendanceData as $record) {
        if (isset($formattedData[$record->week_number])) {
            $formattedData[$record->week_number][$record->course] = $record->count;
        }
    }

    return response()->json($formattedData);
}

    public function index(Request $request)
    {
        $query = Attendance::with('user')->orderBy('created_at', 'desc');

        // Search functionality
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->whereDate('date', 'LIKE', "%{$searchTerm}%")
                  ->orWhereHas('user', function ($q) use ($searchTerm) {
                      $q->where('first_name', 'LIKE', "%{$searchTerm}%");
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



    // public function checkInOut(Request $request)
    // {
    //     $request->validate([
    //         'user_id' => 'required|exists:users,id',
    //         'notes' => 'required|string',
    //     ]);

    //     $user = User::findOrFail($request->user_id);
    //     $today = Carbon::today();

    //     $attendance = Attendance::where('user_id', $user->id)
    //         ->where('date', $today)
    //         ->first();

    //     if (!$attendance) {
    //         // Check-in
    //         $attendance = Attendance::create([
    //             'user_id' => $user->id,
    //             'date' => $today,
    //             'check_in' => Carbon::now(),
    //             'notes' => $request->notes,
    //         ]);

    //         return response()->json([
    //             'message' => 'Check-in successful',
    //             'attendance' => $attendance
    //         ], 201);
    //     } elseif ($attendance->check_out === null) {
    //         // Check-out
    //         $attendance->update([
    //             'check_out' => Carbon::now(),
    //             'notes' => $request->notes ? $attendance->notes . "\n" . $request->notes : $attendance->notes,
    //         ]);

    //         return response()->json([
    //             'message' => 'Check-out successful',
    //             'attendance' => $attendance
    //         ]);
    //     } else {
    //         return response()->json([
    //             'message' => 'You have already checked in and out for today',
    //             'attendance' => $attendance
    //         ], 400);
    //     }
    // }
        public function checkInOut(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'notes' => 'required|string',
        ]);

        $user = User::findOrFail($request->user_id);

        if (!$user) {
            return response()->json([
                'found' => false,
                'message' => 'Student not found'
            ], 404);
        }

        $now = Carbon::now();

        $lastAttendance = Attendance::where('user_id', $user->id)
            ->whereDate('date', $now->toDateString())
            ->latest('check_in')
            ->first();

        if (!$lastAttendance || $lastAttendance->check_out !== null) {
            // Check-in: Create a new attendance record
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $now->toDateString(),
                'check_in' => $now,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'message' => 'Check-in successful',
                'attendance' => $attendance
            ], 201);
        } else {
            // Check-out: Update the last attendance record
            $lastAttendance->update([
                'check_out' => $now,
            ]);

            return response()->json([
                'message' => 'Check-out successful',
                'attendance' => $lastAttendance
            ]);
        }
    }

    
}