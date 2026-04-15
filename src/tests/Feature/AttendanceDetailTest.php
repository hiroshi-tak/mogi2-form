<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceRequest;

class AttendanceDetailTest extends TestCase
{
    use RefreshDatabase;

    // 勤怠詳細情報取得機能（一般ユーザー）
    // 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
    public function test_user_detail_name()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'name' => 'test user',
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->date}");

        $response->assertSeeText('test user');
    }

    // 勤怠詳細画面の「日付」が選択した日付になっている
    public function test_user_detail_date()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'date' => '2024-01-01',
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->date}");

        $response->assertSee(
            \Carbon\Carbon::parse($attendance->date)->format('Y年 n月j日')
        );
    }

    //「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
    public function test_user_detail_time()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
            'clock_in' => \Carbon\Carbon::createFromTime(9, 0),
            'clock_out' => \Carbon\Carbon::createFromTime(18, 0),
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->date}");

        $response->assertSee('09:00');
        $response->assertSee('18:00');
    }

    // 「休憩」にて記されている時間がログインユーザーの打刻と一致している
    public function test_user_detail_break()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        BreakTime::factory()->create([
            'attendance_id' => $attendance->id,
            'start_time' => \Carbon\Carbon::createFromTime(12, 0),
            'end_time' => \Carbon\Carbon::createFromTime(13, 0),
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->date}");

        $response->assertSee('12:00');
        $response->assertSee('13:00');
    }

    // 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_user_detail_error_work_leave_after()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->date}", [
            'attendance_id' => $attendance->id,
            'clock_in' => '18:00',
            'clock_out' => '09:00',
            'note' => 'テスト',
        ]);

        $response->assertSessionHasErrors([
            'clock_out' => '出勤時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    // 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
    public function test_user_detail_error_break_start_leave_after()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->date}", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => 'テスト',
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
    public function test_user_detail_error_break_end_leave_after()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->date}", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => 'テスト',
            'breaks' => [
                [
                    'start' => '17:00',
                    'end' => '19:00',
                ]
            ]
        ]);

        $response->assertSessionHasErrors([
            'breaks.0.end' => '休憩時間もしくは退勤時間が不適切な値です。'
        ]);
    }

    // 備考欄が未入力の場合のエラーメッセージが表示される
    public function test_user_detail_error_remarks()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->post("/attendance/detail/{$attendance->date}", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '',
        ]);

        $response->assertSessionHasErrors([
            'note' => '備考を記入してください。'
        ]);
    }

    // 修正申請処理が実行される
    public function test_user_detail_apply()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post("/attendance/detail/{$attendance->date}", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '修正申請',
        ]);

        $this->assertDatabaseHas('attendance_requests', [
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);
    }

    // 「承認待ち」にログインユーザーが行った申請が全て表示されていること
    public function test_user_detail_apply_pending()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post("/attendance/detail/{$attendance->date}", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '修正申請',
        ]);

        AttendanceRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertSee('承認待ち');
    }

    // 「承認済み」に管理者が承認した修正申請が全て表示されている
    public function test_user_detail_apply_approved()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)->post("/attendance/detail/{$attendance->date}", [
            'attendance_id' => $attendance->id,
            'clock_in' => '09:00',
            'clock_out' => '18:00',
            'note' => '修正申請',
        ]);

        AttendanceRequest::factory()->create([
            'attendance_id' => $attendance->id,
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get('/stamp_correction_request/list');

        $response->assertSee('承認済み');
    }

    // 各申請の「詳細」を押下すると勤怠詳細画面に遷移する
    public function test_user_detail_apply_detail()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::factory()->create([
            'user_id' => $user->id,
        ]);

        AttendanceRequest::factory()->create([
            'attendance_id' => $attendance->id,
        ]);

        $response = $this->actingAs($user)
            ->get("/attendance/detail/{$attendance->date}");

        $response->assertStatus(200);
    }
}
