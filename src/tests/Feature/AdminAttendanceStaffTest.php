<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Admin;
use App\Models\Attendance;
use App\Models\AttendanceRequest;

class AdminAttendanceStaffTest extends TestCase
{
    use RefreshDatabase;

    // ユーザー情報取得機能（管理者）
    // 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
    public function test_admin_staff()
    {
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        User::factory()->count(3)->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/staff/list');

        $users = User::all();

        foreach ($users as $user) {
            $response->assertSee($user->name);
            $response->assertSee($user->email);
        }
    }

    // ユーザーの勤怠情報が正しく表示される
    public function test_admin_staff_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => '09:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/attendance/staff/{$user->id}");

        $response->assertSee('09:00');
    }

    // 「前月」を押下した時に表示月の前月の情報が表示される
    public function test_admin_staff_list_month_before()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subMonth(),
            'clock_in' => '09:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/attendance/staff/{$user->id}?month=" . now()->subMonth()->format('Y-m'));

        $response->assertSee('09:00');
    }

    // 「翌月」を押下した時に表示月の前月の情報が表示される
    public function test_admin_staff_list_month_next()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => now()->addMonth(),
            'clock_in' => '09:00',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/attendance/staff/{$user->id}?month=" . now()->addMonth()->format('Y-m'));

        $response->assertSee('09:00');
    }

    // 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
    public function test_admin_staff_detail()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => today(),
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/attendance/{$attendance->date}?user_id={$attendance->user_id}");

        $response->assertStatus(200);
    }

    // 勤怠情報修正機能（管理者）
    // 承認待ちの修正申請が全て表示されている
    public function test_admin_staff_request_pending()
    {
        User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        AttendanceRequest::factory()->count(3)->create([
            'status' => 'pending',
        ]);

        AttendanceRequest::factory()->create([
            'status' => 'approved',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/list?status=pending');

        $response->assertSee('承認待ち');
    }

    // 承認済みの修正申請が全て表示されている
    public function test_admin_staff_request_approved()
    {
        User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        AttendanceRequest::factory()->count(2)->create([
            'status' => 'approved',
        ]);

        AttendanceRequest::factory()->create([
            'status' => 'pending',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get('/admin/stamp_correction_request/list?status=approved');

        $response->assertSee('承認済み');
    }

    // 修正申請の詳細内容が正しく表示されている
    public function test_admin_staff_request_detail()
    {
        User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $request = AttendanceRequest::factory()->create([
            'note' => '修正内容テスト',
        ]);

        $response = $this->actingAs($admin, 'admin')
            ->get("/admin/stamp_correction_request/approve/{$request->id}");

        $response->assertSee('修正内容テスト');
    }

    // 修正申請の詳細内容が正しく表示されている
    public function test_admin_staff_request_apply()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        /** @var \App\Models\Admin $admin */
        $admin = Admin::factory()->create();

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
        ]);

        $request = AttendanceRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'status' => 'pending',
            'clock_in' => '10:00',
            'clock_out' => '18:00',
        ]);

        $this->actingAs($admin, 'admin')
            ->post("/admin/stamp_correction_request/approve/{$request->id}");

        // ステータス更新
        $this->assertDatabaseHas('attendance_requests', [
            'attendance_id' => $request->attendance_id,
            'status' => 'approved',
        ]);

        // 勤怠更新
        $this->assertDatabaseHas('attendances', [
            'id' => $attendance->id,
            'clock_in' => '10:00:00',
        ]);
    }
}
