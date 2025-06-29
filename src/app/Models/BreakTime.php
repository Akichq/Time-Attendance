<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'break_times';

    protected $fillable = [
        'attendance_id',
        'break_start_time',
        'break_end_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'break_start_time' => 'datetime',
        'break_end_time'   => 'datetime',
    ];

    /**
     * この休憩記録に紐づく勤怠記録を取得
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
} 