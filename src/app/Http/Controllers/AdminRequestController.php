<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\AttendanceRequest;

class AdminRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('status', 'pending');

        $attendanceRequests = AttendanceRequest::with(['user', 'attendance'])
            ->where('status', $tab)
            ->get();

        return view('admin.request.index', compact('attendanceRequests', 'tab'));
    }

    public function show($id)
    {
        $attendanceRequest= AttendanceRequest::with([
            'attendance.user',
            'attendance.breaks',
            'breakRequests'
        ])->findOrFail($id);

        return view('admin.request.show', compact('attendanceRequest'));
    }

    public function approve($id)
    {
        $attendanceRequest = AttendanceRequest::with('breakRequests', 'attendance')->findOrFail($id);

        DB::transaction(
            function () use ($attendanceRequest) {

            $attendance = $attendanceRequest->attendance;

            $attendance->update([
                'clock_in' => $attendanceRequest->clock_in,
                'clock_out' => $attendanceRequest->clock_out,
                'note'      => $attendanceRequest->note,
            ]);

            $attendance->breaks()->delete();

            foreach ($attendanceRequest->breakRequests as $break) {
                $attendance->breaks()->create([
                    'start_time' => $break->start_time,
                    'end_time' => $break->end_time,
                ]);
            }

                $attendanceRequest->update([
                'status' => 'approved',
            ]);

            }
        );

        return redirect()->route('admin.request.show', $attendanceRequest->id);
    }
}
