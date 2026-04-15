<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;

class AdminAttendanceTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠一覧情報取得機能（管理者）
    // その日になされた全ユーザーの勤怠情報が正確に確認できる
    public function test_admin_list()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $date = Carbon::today();

        // 複数ユーザー
        $users = User::factory()->count(3)->create([
            'email_verified_at' => now(),
        ]);

        foreach ($users as $user) {
            Attendance::factory()->create([
                'user_id' => $user->id,
                'date' => $date->format('Y-m-d'),
                'clock_in' => '09:00',
            ]);
        }

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list?date=' . $date->format('Y-m-d'));

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSeeInOrder([
                $user->name,
                '09:00'
            ]);
        }
    }

    // 遷移した際に現在の日付が表示される
    public function test_admin_list_day()
    {
        User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $date = Carbon::today();

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list?date=' . $date->format('Y-m-d'));

        $response->assertSee($date->format('Y/m/d'));
    }

    // 「前日」を押下した時に前の日の勤怠情報が表示される
    public function test_admin_list_day_before()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $date = Carbon::yesterday();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in' => '09:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list?date=' . $date->format('Y-m-d'));

        $response->assertSee($date->format('m/d'));
        $response->assertSeeInOrder([
            $user->name,
            '09:00'
        ]);
    }

    // 「翌日」を押下した時に次の日の勤怠情報が表示される
    public function test_admin_list_day_next()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $date = Carbon::tomorrow();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => $date->format('Y-m-d'),
            'clock_in' => '09:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/attendance/list?date=' . $date->format('Y-m-d'));

        $response->assertSee($date->format('m/d'));
        $response->assertSeeInOrder([
            $user->name,
            '09:00'
        ]);
    }
}
