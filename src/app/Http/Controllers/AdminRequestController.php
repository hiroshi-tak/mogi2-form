<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceRequest;

class AdminRequestController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('status', 'pending');

        $requests = AttendanceRequest::with(['user', 'attendance'])
            ->where('status', $tab)
            ->get();

        return view('admin.request.index', compact('requests', 'tab'));
    }

    public function show($id)
    {
        $request = AttendanceRequest::with([
            'attendance.user',
            'attendance.breaks',
            'breakRequests'
        ])->findOrFail($id);

        return view('admin.request.show', compact('request'));
    }

    public function approve($id)
    {
        $request = AttendanceRequest::with('breakRequests', 'attendance')->findOrFail($id);

        $attendance = $request->attendance;

        $attendance->update([
            'clock_in' => $request->clock_in,
            'clock_out' => $request->clock_out,
            'note'      => $request->note,
        ]);

        $attendance->breaks()->delete();

        foreach ($request->breakRequests as $break) {
            $attendance->breaks()->create([
                'start_time' => $break->start_time,
                'end_time' => $break->end_time,
            ]);
        }

        $request->update([
            'status' => 'approved',
        ]);

        return redirect()->route('admin.request.show', $request->id);
    }
}
