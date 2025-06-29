<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrection extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'requested_clock_in_time',
        'requested_clock_out_time',
        'requested_breaks',
        'remarks',
        'status',
        'admin_remarks',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
} 