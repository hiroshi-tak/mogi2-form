<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Admin;
use Illuminate\Support\Facades\Notification;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\URL;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // 認証機能（一般ユーザー）
    // 名前が未入力の場合、バリデーションメッセージが表示される
    public function test_register_validation_name_error()
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        /*
        $response->assertSessionHasErrors([
            'name' => 'お名前を入力してください'
        ]);
*/
        $response->assertSessionHasErrors(['name']);
    }

    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_register_validation_email_error()
    {
        $response = $this->post('/register', [
            'name' => 'test user',
            'email' => '',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        /*
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
*/
        $response->assertSessionHasErrors(['email']);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_register_validation_password_error()
    {
        $response = $this->post('/register', [
            'name' => 'test user',
            'email' => 'test@example.com',
            'password' => '',
            'password_confirmation' => '',
        ]);
        /*
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
*/
        $response->assertSessionHasErrors(['password']);
    }

    // パスワードが8文字未満の場合、バリデーションメッセージが表示される
    public function test_register_validation_password_min_error()
    {
        $response = $this->post('/register', [
            'name' => 'test user',
            'email' => 'test@example.com',
            'password' => '1234',
            'password_confirmation' => '1234',
        ]);
        /*
        $response->assertSessionHasErrors([
            'password' => 'パスワードは8文字以上で入力してください'
        ]);
*/
        $response->assertSessionHasErrors(['password']);
    }

    // パスワードが一致しない場合、バリデーションメッセージが表示される
    public function test_register_validation_password_same_error()
    {
        $response = $this->post('/register', [
            'name' => 'test user',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345679',
        ]);
        /*
        // パスワードと一致しません
        $response->assertSessionHasErrors([
            'password' => 'パスワードと一致しません'
        ]);
*/
        $response->assertSessionHasErrors(['password']);
    }

    // フォームに内容が入力されていた場合、データが正常に保存される
    public function test_register_success()
    {
        $response = $this->post('/register', [
            'name' => 'test user',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $response->assertRedirect('/attendance');

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'test user',
        ]);
    }

    // ログイン
    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_login_email_error()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => '12345678',
        ]);
        /*
        $response->assertSessionHasErrors([
            'email' => 'メールアドレスを入力してください'
        ]);
*/
        $response->assertSessionHasErrors(['email']);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_login_password_error()
    {
        $response = $this->post('/login', [
            'email' => 'test@example.com',
            'password' => '',
        ]);
        /*
        $response->assertSessionHasErrors([
            'password' => 'パスワードを入力してください'
        ]);
*/
        $response->assertSessionHasErrors(['password']);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_login_credentials_error()
    {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('12345678'),
        ]);

        $response = $this->post('/login', [
            'email' => 'test2@example.com',
            'password' => '12345678',
        ]);

        /*
        $response->assertSessionHasErrors([
            'email' => 'ログイン情報が登録されていません'
        ]);
*/
        $response->assertSessionHasErrors(['email']);
    }

    // 管理者ログイン
    // メールアドレスが未入力の場合、バリデーションメッセージが表示される
    public function test_admin_login_email_error()
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('12345678'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => '',
            'password' => '12345678',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // パスワードが未入力の場合、バリデーションメッセージが表示される
    public function test_admin_login_password_error()
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('12345678'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'admin@example.com',
            'password' => '',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    // 登録内容と一致しない場合、バリデーションメッセージが表示される
    public function test_admin_login_credentials_error()
    {
        Admin::factory()->create([
            'email' => 'admin@example.com',
            'password' => bcrypt('12345678'),
        ]);

        $response = $this->post('/admin/login', [
            'email' => 'wrong@example.com',
            'password' => '12345678',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    // メール認証機能
    // 会員登録後、認証メールが送信される
    public function test_verification_email_is_sent_after_registration()
    {
        Notification::fake();

        $this->post('/register', [
            'name' => 'test user',
            'email' => 'test@example.com',
            'password' => '12345678',
            'password_confirmation' => '12345678',
        ]);

        $user = User::where('email', 'test@example.com')->first();

        Notification::assertSentTo($user, VerifyEmail::class);
    }

    // メール認証誘導画面で「認証はこちらから」ボタンを押下するとメール認証サイトに遷移する
    public function test_email_verification_notice_page()
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)
            ->get('/email/verify');

        $response->assertStatus(200);

        $response->assertSee('認証はこちらから');
    }

    // メール認証サイトのメール認証を完了すると、勤怠登録画面に遷移する
    public function test_user_can_verify_email()
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->id,
                'hash' => sha1($user->email),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $response->assertRedirect('/attendance');

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
