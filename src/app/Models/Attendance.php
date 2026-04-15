<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    const STATUS_OFF = '勤務外';
    const STATUS_WORKING = '出勤中';
    const STATUS_BREAK = '休憩中';
    const STATUS_DONE = '退勤済';

    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'note'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    public function requests()
    {
        return $this->hasMany(AttendanceRequest::class);
    }

    public function getStatusAttribute()
    {
        if (!$this->clock_in) return self::STATUS_OFF;

        if (!$this->clock_out) {
            $latestBreak = $this->breaks()
                ->orderByDesc('start_time')
                ->orderByDesc('id')
                ->first();

            if ($latestBreak && !$latestBreak->end_time) {
                return self::STATUS_BREAK;
            }

            return self::STATUS_WORKING;
        }

        return self::STATUS_DONE;
    }

    public function scopeMonth($query, $userId, $month)
    {
        return $query->where('user_id', $userId)
            ->whereBetween('date', [
                $month->copy()->startOfMonth(),
                $month->copy()->endOfMonth(),
            ]);
    }

    public function getBreakSecondsAttribute()
    {
        return $this->breaks->sum(function ($b) {
            if (!$b->start_time || !$b->end_time) return 0;
            return strtotime($b->end_time) - strtotime($b->start_time);
        });
    }

    public function getWorkSecondsAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) return 0;

        return strtotime($this->clock_out) - strtotime($this->clock_in) - $this->break_seconds;
    }

    public function hasPendingRequest()
    {
        return $this->requests()
            ->where('status', 'pending')
            ->exists();
    }

    public function pendingRequest()
    {
        return $this->hasOne(AttendanceRequest::class)
            ->where('status', 'pending')
            ->latest();
    }
}

