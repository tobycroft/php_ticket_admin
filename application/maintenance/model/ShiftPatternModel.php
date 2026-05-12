<?php

namespace app\maintenance\model;

use think\Model;

class ShiftPatternModel extends Model
{
    protected $table = 'mt_shift_pattern';
    protected $pk = 'id';

    protected $type = [
        'start_time' => 'time',
        'end_time' => 'time',
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

    public static function getList($map = [])
    {
        return self::where($map)->order('id')->select();
    }

    public static function getActiveList()
    {
        return self::where('status', 1)->order('id')->select();
    }
}