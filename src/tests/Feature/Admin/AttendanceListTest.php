<?php

namespace Tests\Feature\Admin;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
    }

    /**
     * その日になされた全ユーザーの勤怠情報が正確に確認できる
     */
    public function test_admin_can_see_all_users_attendance_for_the_day()
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
        $today = Carbon::today();
        Attendance::create([
            'user_id' => $user1->id,
            'clock_in_time' => $today->copy()->setTime(9, 0, 0),
            'clock_out_time' => $today->copy()->setTime(18, 0, 0),
        ]);
        Attendance::create([
            'user_id' => $user2->id,
            'clock_in_time' => $today->copy()->setTime(10, 0, 0),
            'clock_out_time' => $today->copy()->setTime(19, 0, 0),
        ]);

        $this->actingAs($admin, 'admin');
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee('ユーザー1');
        $response->assertSee('ユーザー2');
        $response->assertSee('09:00');
        $response->assertSee('18:00');
        $response->assertSee('10:00');
        $response->assertSee('19:00');
    }

    /**
     * 遷移した際に現在の日付が表示される
     */
    public function test_attendance_list_shows_today_date()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $this->actingAs($admin, 'admin');
        $today = Carbon::today();
        $response = $this->get('/admin/attendance/list');
        $response->assertStatus(200);
        $response->assertSee($today->format('Y年n月j日'));
        $response->assertSee($today->format('Y/m/d'));
    }

    /**
     * 「前日」を押下した時に前の日の勤怠情報が表示される
     */
    public function test_attendance_list_shows_previous_day()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user = User::create([
            'name' => 'ユーザーA',
            'email' => 'usera@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $yesterday = Carbon::yesterday();
        Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $yesterday->copy()->setTime(8, 30, 0),
            'clock_out_time' => $yesterday->copy()->setTime(17, 30, 0),
        ]);
        $this->actingAs($admin, 'admin');
        $response = $this->get('/admin/attendance/list?date=' . $yesterday->toDateString());
        $response->assertStatus(200);
        $response->assertSee($yesterday->format('Y年n月j日'));
        $response->assertSee('ユーザーA');
        $response->assertSee('08:30');
        $response->assertSee('17:30');
    }

    /**
     * 「翌日」を押下した時に次の日の勤怠情報が表示される
     */
    public function test_attendance_list_shows_next_day()
    {
        $admin = Admin::create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
        ]);
        $user = User::create([
            'name' => 'ユーザーB',
            'email' => 'userb@example.com',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);
        $tomorrow = Carbon::tomorrow();
        Attendance::create([
            'user_id' => $user->id,
            'clock_in_time' => $tomorrow->copy()->setTime(11, 0, 0),
            'clock_out_time' => $tomorrow->copy()->setTime(20, 0, 0),
        ]);
        $this->actingAs($admin, 'admin');
        $response = $this->get('/admin/attendance/list?date=' . $tomorrow->toDateString());
        $response->assertStatus(200);
        $response->assertSee($tomorrow->format('Y年n月j日'));
        $response->assertSee('ユーザーB');
        $response->assertSee('11:00');
        $response->assertSee('20:00');
    }
} 