<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\BreakTime;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'clock_in_time',
        'clock_out_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'clock_in_time'  => 'datetime',
        'clock_out_time' => 'datetime',
    ];

    /**
     * この勤怠記録に紐づくユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この勤怠記録に紐づく休憩記録を取得
     */
    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * 合計休憩時間を計算するアクセサ
     *
     * @return \Carbon\CarbonInterval|null
     */
    public function getTotalBreakTimeAttribute()
    {
        if ($this->breaks->isEmpty()) {
            return null;
        }

        $totalSeconds = $this->breaks->reduce(function ($carry, $break) {
            if ($break->break_start_time && $break->break_end_time) {
                return $carry + $break->break_start_time->diffInSeconds($break->break_end_time);
            }
            return $carry;
        }, 0);

        return \Carbon\CarbonInterval::seconds($totalSeconds)->cascade();
    }

    /**
     * 実働時間を計算するアクセサ
     *
     * @return \Carbon\CarbonInterval|null
     */
    public function getWorkDurationAttribute()
    {
        if (!$this->clock_in_time || !$this->clock_out_time) {
            return null;
        }

        $workSeconds = $this->clock_in_time->diffInSeconds($this->clock_out_time);
        $breakSeconds = $this->total_break_time ? $this->total_break_time->totalSeconds : 0;
        $actualWorkSeconds = $workSeconds - $breakSeconds;

        return \Carbon\CarbonInterval::seconds($actualWorkSeconds)->cascade();
    }
} 