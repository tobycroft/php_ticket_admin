<?php

namespace app\maintenance\model;

use think\Model;

class UserLeaveModel extends Model
{
    protected $table = 'mt_user_leave';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'user_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'approver_id' => 'integer',
    ];

    public static function getTypeList()
    {
        return [
            1 => '请假',
            2 => '调休',
        ];
    }

    public static function getStatusList()
    {
        return [
            0 => '待审核',
            1 => '已批准',
            2 => '已拒绝',
        ];
    }
}