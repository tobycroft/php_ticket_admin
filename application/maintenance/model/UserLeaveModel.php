<?php

namespace app\maintenance\model;

use think\Model;

class UserLeaveModel extends Model
{
    protected $table = 'mt_user_leave';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer',
        'type' => 'integer',
        'status' => 'integer',
        'approver_id' => 'integer',
        'approve_time' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
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