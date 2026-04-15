<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    // 日時取得機能
    // 現在の日時情報がUIと同じ形式で出力されている
    public function test_user_match_date()
    {
         /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        \Carbon\Carbon::setTestNow(
            \Carbon\Carbon::create(2026, 4, 15, 8, 0)
        );

        $date = now();

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertStatus(200);

        $response->assertSee(
            $date->format('Y年n月j日')
        );

        $response->assertSee(
            $date->format('H:i')
        );
    }

    // ステータス確認機能
    // 勤務外の場合、勤怠ステータスが正しく表示される
    public function test_user_status_outside()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('勤務外');
    }

    // 出勤中の場合、勤怠ステータスが正しく表示される
    public function test_user_status_work()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => now(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');
    }

    // 休憩中の場合、勤怠ステータスが正しく表示される
    public function test_user_status_break()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => now(),
            'clock_in' => now(),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'start_time' => now(),
            'end_time' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    // 退勤済の場合、勤怠ステータスが正しく表示される
    public function test_user_status_leave()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => now(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('退勤済');
    }

    // 出勤機能
    // 出勤ボタンが正しく機能する
    public function test_user_work_properly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 出勤前（勤務外）
        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('勤務外');

        // 出勤処理
        $this->actingAs($user)->post('/attendance/clock-in');

        // 再読み込み
        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');

        // DB確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
            'clock_out' => null,
        ]);
    }

    //出勤は一日一回のみできる
    public function test_user_work_one()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 退勤済データ
        Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        // 出勤ボタンが表示されない
        $response->assertSee('退勤済');
    }

    // 出勤時刻が勤怠一覧画面で確認できる
    public function test_user_work_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/clock-in');

        $attendance = \App\Models\Attendance::first();

        $response = $this->actingAs($user)->get('/attendance/list');

        $expected = \Carbon\Carbon::parse($attendance->clock_in)
            ->format('H:i');

        $response->assertSee($expected);
    }

    // 休憩機能
    // 休憩ボタンが正しく機能する
    public function test_user_break_properly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
        ]);

        $response = $this->actingAs($user)->get('/attendance');
        $response->assertSee('休憩入');

        // 休憩開始
        $this->actingAs($user)->post('/attendance/break-start');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');

        $this->assertDatabaseHas('break_times', [
            'attendance_id' => $attendance->id,
            'end_time' => null,
        ]);
    }

    // 休憩は一日に何回でもできる
    public function test_user_break_many()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/break-start');
        $this->actingAs($user)->post('/attendance/break-end');

        $response = $this->actingAs($user)->get('/attendance');

        // 再度休憩入できる
        $response->assertSee('休憩入');
    }

    // 休憩戻ボタンが正しく機能する
    public function test_user_break_return_properly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        $attendance = Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
        ]);

        // 休憩開始
        $this->actingAs($user)->post('/attendance/break-start');

        // 休憩終了
        $this->actingAs($user)->post('/attendance/break-end');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('出勤中');

        $this->assertDatabaseMissing('break_times', [
            'attendance_id' => $attendance->id,
            'end_time' => null,
        ]);
    }

    // 休憩戻は一日に何回でもできる
    public function test_user_break_return_many()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
        ]);

        // 1回目
        $this->actingAs($user)->post('/attendance/break-start');
        $this->actingAs($user)->post('/attendance/break-end');

        // 2回目
        $this->actingAs($user)->post('/attendance/break-start');

        $response = $this->actingAs($user)->get('/attendance');

        $response->assertSee('休憩中');
    }

    // 休憩時刻が勤怠一覧画面で確認できる
    public function test_user_break_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
        ]);

        $this->actingAs($user)->post('/attendance/break-start');
        $this->actingAs($user)->post('/attendance/break-end');

        $break = \App\Models\BreakTime::first();

        $response = $this->actingAs($user)->get('/attendance/list');

        $start = \Carbon\Carbon::parse($break->start_time)->format('H:i');
        $end = \Carbon\Carbon::parse($break->end_time)->format('H:i');

        $response->assertSee($start);
        $response->assertSee($end);
    }

    // 退勤機能
    // 退勤ボタンが正しく機能する
    public function test_user_leave_properly()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 出勤中状態を作る
        Attendance::create([
            'user_id' => $user->id,
            'date' => today(),
            'clock_in' => now(),
            'clock_out' => null,
        ]);

        $response = $this->actingAs($user)->get('/attendance');

        // ボタン表示確認
        $response->assertSee('退勤');

        // 退勤処理
        $this->actingAs($user)->post('/attendance/clock-out');

        $response = $this->actingAs($user)->get('/attendance');

        // ステータス確認
        $response->assertSee('退勤済');

        // DB確認
        $this->assertDatabaseHas('attendances', [
            'user_id' => $user->id,
        ]);

        $this->assertNotNull(
            Attendance::where('user_id', $user->id)->first()->clock_out
        );
    }

    // 退勤時刻が勤怠一覧画面で確認できる
    public function test_user_leave_list()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);

        // 出勤
        $this->actingAs($user)->post('/attendance/clock-in');

        // 退勤
        $this->actingAs($user)->post('/attendance/clock-out');

        $attendance = \App\Models\Attendance::first();

        $response = $this->actingAs($user)->get('/attendance/list');

        $expected = \Carbon\Carbon::parse($attendance->clock_out)
            ->format('H:i');

        $response->assertSee($expected);
    }
}
