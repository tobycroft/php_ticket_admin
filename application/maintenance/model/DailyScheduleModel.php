<?php

namespace app\maintenance\model;

use think\Model;

class DailyScheduleModel extends Model
{
    protected $table = 'mt_daily_schedule';
    protected $pk = 'id';

    protected $type = [
        'user_id' => 'integer',
        'shift_id' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';

    public static function getStatusList()
    {
        return [
            0 => '禁用',
            1 => '启用',
        ];
    }

    public static function getByDate($schedule_date)
    {
        return self::where('schedule_date', $schedule_date)->where('status', 1)->select();
    }

    public static function getByMonth($year, $month)
    {
        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01"));
        return self::where('mt_daily_schedule.schedule_date', '>=', $start_date)
            ->where('mt_daily_schedule.schedule_date', '<=', $end_date)
            ->where('mt_daily_schedule.status', 1)
            ->join('dp_admin_user u', 'u.id = mt_daily_schedule.user_id', 'LEFT')
            ->field('mt_daily_schedule.*, u.color as user_color')
            ->select();
    }

    public static function getByUserMonth($user_id, $year, $month)
    {
        $start_date = date('Y-m-01', strtotime("$year-$month-01"));
        $end_date = date('Y-m-t', strtotime("$year-$month-01"));
        return self::where('user_id', $user_id)
            ->where('schedule_date', '>=', $start_date)
            ->where('schedule_date', '<=', $end_date)
            ->where('status', 1)
            ->select();
    }

    public static function deleteByDate($schedule_date)
    {
        return self::where('schedule_date', $schedule_date)->delete();
    }
}