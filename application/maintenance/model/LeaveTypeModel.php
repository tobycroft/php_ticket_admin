<?php

namespace app\maintenance\model;

use think\Model;

class LeaveTypeModel extends Model
{
    protected $table = 'mt_leave_type';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';
    protected $updateTime = false;

    protected $type = [
        'id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
    ];

    public static function getTypeList($with_disabled = false)
    {
        $query = self::order('sort', 'asc');
        if (!$with_disabled) {
            $query->where('status', 1);
        }
        $list = $query->column('name', 'id');
        return $list ? $list : [];
    }
}