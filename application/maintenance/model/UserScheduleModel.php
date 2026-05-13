<?php

namespace app\maintenance\model;

use think\Model;

class UserScheduleModel extends Model
{
    protected $table = 'mt_user_schedule';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer',
        'day_of_week' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo('app\user\model\User', 'user_id', 'id');
    }

    public static function getDayOfWeekList()
    {
        return [
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            7 => '周日',
        ];
    }

    public static function getStatusList()
    {
        return [
            0 => '禁用',
            1 => '启用',
        ];
    }
}