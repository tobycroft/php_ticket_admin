<?php

namespace app\maintenance\model;

use think\Model;

class ShiftPatternModel extends Model
{
    protected $name = 'shift_pattern';
    protected $pk = 'id';

    protected $type = [
        'start_time' => 'time',
        'end_time' => 'time',
        'status' => 'integer',
        'create_time' => 'integer',
        'update_time' => 'integer',
    ];

    protected $autoWriteTimestamp = true;
    protected $createTime = 'create_time';
    protected $updateTime = 'update_time';
    protected $dateFormat = 'timestamp';

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