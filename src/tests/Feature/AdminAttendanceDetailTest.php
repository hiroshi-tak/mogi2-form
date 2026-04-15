<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;

class AdminAttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠詳細情報取得・修正機能（管理者）
    // 勤怠詳細画面に表示されるデータが選択したものになっている
    public function test_admin_detail()
    {
        User::factory()->count(3)->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create([
            'date' => '2024-01-01',
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/attendance/{$attendance->date}?user_id={$attendance->user_id}");

        $response->assertSee('2024年 1月1日');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_detail_error_work_leave_after()
    {
        User::factory()->count(3)->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post("/admin/attendance/{$attendance->date}", [
                'user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id,
                'clock_in' => '18:00',
                'clock_out' => '09:00',
                'note' => '修正',
            ]);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_detail_error_break_start_leave_after()
    {
        User::factory()->count(3)->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post("/admin/attendance/{$attendance->date}", [
                'attendance_id' => $attendance->id,
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => '修正',
                'breaks' => [
                    [
                        'start' => '20:00',
                        'end' => null,
                    ]
                ]
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.start' => '休憩時間が不適切な値です。'
        ]);
    }

    // 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_admin_detail_error_break_end_leave_after()
    {
        User::factory()->count(3)->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post("/admin/attendance/{$attendance->date}", [
                'attendance_id' => $attendance->id,
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => '修正',
                'breaks' => [
                    [
                        'start' => '17:00',
                        'end' => '20:00',
                    ]
                ]
            ]);

        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    // 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_admin_detail_error_remarks()
    {
        User::factory()->count(3)->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create();

        $response = $this->actingAs($admin, 'admin')
            ->post("/admin/attendance/{$attendance->date}", [
                'attendance_id' => $attendance->id,
                'clock_in' => '09:00',
                'clock_out' => '18:00',
                'note' => '',
            ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください。'
        ]);
    }
}
