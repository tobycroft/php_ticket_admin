<?php

namespace app\maintenance\model;

use think\Model;

class EventFlowModel extends Model
{
    protected $table = 'mt_event_flow';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = false;

    protected $type = [
        'event_id' => 'integer',
        'from_user_id' => 'integer',
        'to_user_id' => 'integer',
        'status' => 'integer',
        'create_time' => 'integer',
        'handle_time' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo('EventModel', 'event_id', 'id');
    }

    public static function getStatusList()
    {
        return [
            0 => '待处理',
            1 => '已处理',
            2 => '已退回',
        ];
    }
}