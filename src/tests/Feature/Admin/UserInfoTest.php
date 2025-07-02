<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class UserInfoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * 管理者ユーザーが全一般ユーザーの「氏名」「メールアドレス」を確認できる
     */
    public function test_admin_can_see_all_users_name_and_email()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user1 = User::create([
            'name' => 'ユーザー1',
            'email' => 'user1@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name' => 'ユーザー2',
            'email' => 'user2@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('ユーザー1');
        $response->assertSee('user1@example.com');
        $response->assertSee('ユーザー2');
        $response->assertSee('user2@example.com');
    }

    /**
     * ユーザーの勤怠情報が正しく表示される
     */
    public function test_user_attendance_info_is_displayed_correctly()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(18, 0, 0),
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start_time' => $today->copy()->setTime(12, 0, 0),
            'break_end_time' => $today->copy()->setTime(13, 0, 0),
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200);
        $response->assertSee('テストユーザーさんの勤怠');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('01:00'); // 休憩時間
    }

    /**
     * 「前月」を押下した時に表示月の前月の情報が表示される
     */
    public function test_previous_month_button_shows_previous_month_info()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $lastMonth = Carbon::now()->subMonth();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $lastMonth->copy()->setTime(8, 30, 0),
            'clock_out_time' => $lastMonth->copy()->setTime(17, 30, 0),
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get("/admin/attendance/staff/{$user->id}?month=" . $lastMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($lastMonth->format('Y/m'));
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    /**
     * 「翌月」を押下した時に表示月の翌月の情報が表示される
     */
    public function test_next_month_button_shows_next_month_info()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $nextMonth = Carbon::now()->addMonth();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $nextMonth->copy()->setTime(10, 0, 0),
            'clock_out_time' => $nextMonth->copy()->setTime(19, 0, 0),
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get("/admin/attendance/staff/{$user->id}?month=" . $nextMonth->format('Y-m'));

        $response->assertStatus(200);
        $response->assertSee($nextMonth->format('Y/m'));
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
     */
    public function test_detail_link_redirects_to_attendance_detail()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $today = Carbon::today();
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(18, 0, 0),
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get("/admin/attendance/staff/{$user->id}");

        $response->assertStatus(200);
        $response->assertSee('詳細');
        $response->assertSee("/attendance/{$attendance->id}");
    }

    /**
     * スタッフ一覧から詳細ページへのリンクが正しく動作する
     */
    public function test_staff_list_detail_link_works_correctly()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);

        $user = User::create([
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get('/admin/staff/list');

        $response->assertStatus(200);
        $response->assertSee('詳細');
        $response->assertSee("/admin/attendance/staff/{$user->id}");
    }
} 