<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Admin;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();

        // 管理者データを1件作成
        Admin::create([
            'name' => 'テスト管理者',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
        ]);

        // テスト用ダミーデータを作成（開発環境のみ）
        if (app()->environment('local', 'development')) {
            $this->call(TestDataSeeder::class);
        }
    }
}
