<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;
use App\Models\BreakRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AuthorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(): void
    {
        $users = collect([
            User::create([
                'name' => 'user1',
                'email' => 'user1@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('12345678'),
            ]),
            User::create([
                'name' => 'user2',
                'email' => 'user2@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('12345678'),
            ]),
            User::create([
                'name' => 'user3',
                'email' => 'user3@example.com',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('12345678'),
            ]),
        ]);

        $start = Carbon::create(2026, 3, 1);
        $end   = Carbon::create(2026, 4, 10);

        $attendances = [];

        foreach ($users as $user) {
            for ($date = $start->copy(); $date->lte($end); $date->addDay()) {

                if ($date->isWeekend()) {
                    continue;
                }

                $attendance = Attendance::create([
                    'user_id'   => $user->id,
                    'date'      => $date->format('Y-m-d'),
                    'clock_in'  => '09:00:00',
                    'clock_out' => '18:00:00',
                    'note'      => null,
                ]);

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'start_time'    => '12:00:00',
                    'end_time'      => '13:00:00',
                ]);

                $attendances[] = $attendance;
            }
        }

        $attendancesByUser = collect($attendances)->groupBy('user_id');

        foreach ($attendancesByUser as $userId => $userAttendances) {

            $targets = $userAttendances
                ->filter(fn($att) => !AttendanceRequest::where('attendance_id', $att->id)->exists())
                ->shuffle()
                ->take(4)
                ->values();

            if ($targets->count() < 4) {
                continue;
            }

            for ($i = 0; $i < 2; $i++) {
                $att = $targets[$i];

                $request = AttendanceRequest::create([
                    'attendance_id' => $att->id,
                    'clock_in'      => '09:30:00',
                    'clock_out'     => '18:30:00',
                    'note'          => '申請中データ',
                    'status'        => 'pending',
                ]);

                BreakRequest::create([
                    'attendance_request_id' => $request->id,
                    'start_time'            => '12:30:00',
                    'end_time'              => '13:30:00',
                ]);
            }

            for ($i = 2; $i < 4; $i++) {
                $att = $targets[$i];

                $request = AttendanceRequest::create([
                    'attendance_id' => $att->id,
                    'clock_in'      => '08:45:00',
                    'clock_out'     => '17:45:00',
                    'note'          => '承認済データ',
                    'status'        => 'approved',
                ]);

                BreakRequest::create([
                    'attendance_request_id' => $request->id,
                    'start_time'            => '12:15:00',
                    'end_time'              => '13:15:00',
                ]);
            }
        }
    }
}
