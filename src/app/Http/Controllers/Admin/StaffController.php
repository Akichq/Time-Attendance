<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;

class StaffController extends Controller
{
    /**
     * スタッフ一覧画面
     */
    public function index()
    {
        $users = User::orderBy('name')->get();
        return view('admin.staff.list', compact('users'));
    }
}