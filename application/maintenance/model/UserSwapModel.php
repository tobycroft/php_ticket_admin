<?php

namespace app\maintenance\model;

use think\Model;

class UserSwapModel extends Model
{
    protected $table = 'mt_user_swap';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $type = [
        'user_id' => 'integer',
        'target_user_id' => 'integer',
        'status' => 'integer',
        'approver_id' => 'integer',
        'approve_time' => 'datetime',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    public static function getStatusList()
    {
        return [
            0 => '待审核',
            1 => '已批准',
            2 => '已拒绝',
        ];
    }
}