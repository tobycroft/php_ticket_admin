<?php

namespace app\maintenance\model;

use think\Model;

class HandoverModel extends Model
{
    protected $table = 'mt_handover';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'creator_id' => 'integer',
        'default_receiver_id' => 'integer',
        'actual_receiver_id' => 'integer',
        'status' => 'integer',
        'is_forced' => 'integer',
    ];

    public static function getStatusList()
    {
        return [
            0 => '待交接',
            1 => '已接收',
            2 => '已完成',
        ];
    }

    public static function getIsForcedList()
    {
        return [
            0 => '正常交接',
            1 => '强行交接',
        ];
    }
}