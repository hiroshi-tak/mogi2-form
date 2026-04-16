<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\Attendance;
use App\Models\User;
use App\Helpers\DateHelper;

class AdminStaffController extends Controller
{
    private function getMonthlyAttendances($userId, $month)
    {
        return Attendance::with('breaks')
            ->where('user_id', $userId)
            ->whereYear('date', $month->year)
            ->whereMonth('date', $month->month)
            ->orderBy('date')
            ->get()
            ->keyBy(fn($item) => $item->date->format('Y-m-d'));
    }

    public function index()
    {
        $users = User::all();

        return view('admin.staff.index', compact('users'));
    }

    public function show($id, Request $request)
    {
        $user = User::findOrFail($id);

        $month = $request->month
            ? Carbon::parse($request->month)
            : Carbon::now();

        $dates = DateHelper::getMonthDays($month);

        $attendances = $this->getMonthlyAttendances($id, $month);

        return view('admin.staff.show', compact('user', 'attendances', 'month', 'dates'));
    }

    public function csv($id, Request $request)
    {
        $user = User::findOrFail($id);

        $month = $request->month
            ? Carbon::parse($request->month)
            : now();

        $dates = DateHelper::getMonthDays($month);

        $attendances = $this->getMonthlyAttendances($id, $month);

        $filename = $user->name . '_' . $month->format('Y_m') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$filename}",
        ];

        $callback = function () use ($dates, $attendances) {
            $handle = fopen('php://output', 'w');

            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['日付', '出勤', '退勤', '休憩', '合計']);

            foreach ($dates as $date) {

                $key = $date->format('Y-m-d');
                $a = $attendances[$key] ?? null;

                fputcsv($handle, [
                    $date->format('Y-m-d'),
                    $a?->clock_in?->format('H:i') ?? '',
                    $a?->clock_out?->format('H:i') ?? '',
                    $a ? gmdate('H:i', $a->break_seconds) : '',
                    $a ? gmdate('H:i', $a->work_seconds) : '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

}
