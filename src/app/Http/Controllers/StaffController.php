<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Helpers\DateHelper;
use App\Http\Requests\AttendanceUpdateRequest;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $month = $request->month
            ? \Carbon\Carbon::parse($request->month)
            : now();

        $dates = DateHelper::getMonthDays($month);

        $attendances = Attendance::month(auth()->id(), $month)
            ->with('breaks')
            ->get()
            ->keyBy(function ($item) {
                return $item->date->format('Y-m-d');
            });

        return view('attendance.list', compact(
            'month',
            'dates',
            'attendances'
        ));
    }

    public function show($id, Request $request)
    {
        $date = Carbon::parse($id);

        $user = Auth::user();

        $attendance = Attendance::with(['user', 'breaks'])
            ->where('user_id', auth()->id())
            ->whereDate('date', $date)
            ->first();

        $from = $request->from;
        $pendingRequest = $attendance?->pendingRequest;

        return view('attendance.show', compact('attendance', 'date', 'pendingRequest', 'user', 'from'));
    }

    public function update(AttendanceUpdateRequest $request, $id)
    {
        $date = Carbon::parse($id)->toDateString();

        DB::transaction(
            function () use ($request, $date) {

            $attendance = Attendance::firstOrCreate(
                [
                    'user_id' => Auth::id(),
                    'date' => $date,
                ]
            );

            $req = AttendanceRequest::create([
                'attendance_id' => $attendance->id,
                'clock_in' => $request->clock_in
                    ? Carbon::parse($date . ' ' . $request->clock_in)
                    : null,
                'clock_out' => $request->clock_out
                    ? Carbon::parse($date . ' ' . $request->clock_out)
                    : null,
                'note' => $request->note,
                'status' => 'pending'
            ]);

            foreach ($request->breaks ?? [] as $break) {

                if (empty($break['start'])) continue;

                $req->breakRequests()->create([
                    'start_time' => Carbon::parse($date . ' ' . $break['start']),
                    'end_time' => !empty($break['end'])
                        ? Carbon::parse($date . ' ' . $break['end'])
                        : null,
                ]);
            }
        });

        return redirect()->route('request.index');
    }
}
