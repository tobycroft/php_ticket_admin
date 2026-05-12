<?php

namespace app\maintenance\model;

use think\Model;

class EventFlowModel extends Model
{
    protected $table = 'mt_event_flow';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'event_id' => 'integer',
        'from_user_id' => 'integer',
        'to_user_id' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'handle_time' => 'datetime',
    ];

    public function getEvent()
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