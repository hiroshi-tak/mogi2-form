<?php

namespace App\Http\Controllers;

use App\Models\Attendance;

class AttendanceController extends Controller
{
    private function getTodayAttendance()
    {
        return Attendance::where('user_id', auth()->id())
            ->whereDate('date', today())
            ->first();
    }

    public function index()
    {
        $attendance = $this->getTodayAttendance()?->fresh();

        $status = $attendance?->status ?? '勤務外';

        return view('attendance.index', compact('status'));
    }

    public function clockIn()
    {
        $attendance = $this->getTodayAttendance()?->fresh();

        if ($attendance && $attendance->clock_in) {
            return redirect()->route('attendance.index')->withErrors('既に出勤済みです');
        }

        Attendance::create([
            'user_id' => auth()->id(),
            'date' => today(),
            'clock_in' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function clockOut()
    {
        $attendance = $this->getTodayAttendance()?->fresh();

        if (!$attendance) {
            return redirect()->route('attendance.index')->withErrors('出勤していません');
        }

        if ($attendance->clock_out) {
            return redirect()->route('attendance.index')->withErrors('既に退勤済みです');
        }

        $attendance->update([
            'clock_out' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function breakStart()
    {
        $attendance = $this->getTodayAttendance()?->fresh();

        if (!$attendance) {
            return redirect()->route('attendance.index')->withErrors('出勤していません');
        }

        $latestBreak = $attendance->breaks()->latest()->first();

        if ($latestBreak && !$latestBreak->end_time) {
            return redirect()->route('attendance.index')->withErrors('既に休憩中です');
        }

        $attendance->breaks()->create([
            'start_time' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

    public function breakEnd()
    {
        $attendance = $this->getTodayAttendance()?->fresh();

        if (!$attendance) {
            return redirect()->route('attendance.index')->withErrors('出勤していません');
        }

        $break = $attendance->breaks()->latest()->first();

        if (!$break || $break->end_time) {
            return redirect()->route('attendance.index')->withErrors('休憩中ではありません');
        }

        $break->update([
            'end_time' => now(),
        ]);

        return redirect()->route('attendance.index');
    }

}
