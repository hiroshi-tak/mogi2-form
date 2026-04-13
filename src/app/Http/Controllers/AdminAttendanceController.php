<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use App\Http\Requests\AttendanceUpdateRequest;

class AdminAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->date
            ? Carbon::parse($request->date)
            : now();

        $attendances = Attendance::with(['user', 'breaks'])
            ->whereNotNull('clock_in')
            ->whereDate('date', $date)
            ->get();

        return view('admin.attendance.index', compact('attendances', 'date'));
    }

    public function show($id, Request $request)
    {
        $date = Carbon::parse($id)->toDateString();
        $user = User::findOrFail($request->user_id);

        $attendance = Attendance::with(['user', 'breaks'])
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->first();

        $isPending = $attendance
            ? \App\Models\AttendanceRequest::where('attendance_id', $attendance->id)
            ->where('status', 'pending')
            ->exists()
            : false;

        return view('admin.attendance.show', compact('attendance', 'date', 'user', 'isPending'));
    }

    public function update(AttendanceUpdateRequest $request, $id)
    {
        $date = Carbon::parse($id)->toDateString();

        $attendance = Attendance::firstOrCreate([
            'user_id' => $request->user_id,
            'date' => $date,
        ]);

        $attendance->update([
            'clock_in'  => $request->clock_in,
            'clock_out' => $request->clock_out,
            'note'      => $request->note,
        ]);

        foreach ($request->breaks as $breakId => $break) {

            $start = $break['start'] ?? null;
            $end   = $break['end'] ?? null;

            if (empty($start) && empty($end)) {
                if (is_numeric($breakId)) {
                    $attendance->breaks()->where('id', $breakId)->delete();
                }
                continue;
            }

            if (is_numeric($breakId)) {
                $attendance->breaks()->where('id', $breakId)->update([
                    'start_time' => $start,
                    'end_time'   => $end,
                ]);
            } else {
                $attendance->breaks()->create([
                    'start_time' => $start,
                    'end_time'   => $end,
                ]);
            }
        }

        return redirect()
            ->route('admin.attendance.show', [
                'id' => $date,
                'user_id' => $request->user_id
            ])
            ->with('success', '更新しました');
    }
}
