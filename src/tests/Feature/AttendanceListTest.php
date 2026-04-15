<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠一覧情報取得機能（一般ユーザー）
    // 自分が行った勤怠情報が全て表示されている
    public function test_user_list_all()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 自分の勤怠
        collect(range(1, 3))->each(function ($i) use ($user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'date' => today()->addDays($i),
            ]);
        });

        // 他人の勤怠
        Attendance::factory()->create();

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $attendances = Attendance::where('user_id', $user->id)->get();

        foreach ($attendances as $attendance) {
            $response->assertSee(
                \Carbon\Carbon::parse($attendance->date)->format('m/d')
            );
        }
    }

    // 勤怠一覧画面に遷移した際に現在の月が表示される
    public function test_user_list_month()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list');

        $currentMonth = Carbon::now()->format('Y/m');

        $response->assertSee($currentMonth);
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_user_list_month_before()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $date = Carbon::now()->subMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?month=' . $date->format('Y-m'));

        $response->assertSee(
            \Carbon\Carbon::parse($attendance->date)->format('m/d')
        );
    }

    // 「翌月」を押下した時に表示月の翌月の情報が表示される
    public function test_user_list_month_next()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $date = Carbon::now()->addMonth();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
        ]);

        $response = $this->actingAs($user)
            ->get('/attendance/list?month=' . $date->format('Y-m'));

        $response->assertSee(
            \Carbon\Carbon::parse($attendance->date)->format('m/d')
        );
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_user_list_detail()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->date}");

        $response->assertStatus(200);
    }
}
