<?php

namespace app\maintenance\model;

use think\Model;

class EventModel extends Model
{
    protected $table = 'mt_event';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = 'update_time';

    protected $type = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'creator_id' => 'integer',
        'receiver_id' => 'integer',
        'closer_id' => 'integer',
        'is_closed' => 'integer',
        'is_canceled' => 'integer',
        'receive_type' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    public function notes()
    {
        return $this->hasMany('EventNoteModel', 'event_id', 'id');
    }

    public function flows()
    {
        return $this->hasMany('EventFlowModel', 'event_id', 'id');
    }

    public static function getReceiveTypeList()
    {
        return [
            0 => '系统分配',
            1 => '手动接单',
        ];
    }

    public static function getStatusList()
    {
        return [
            0 => '禁用',
            1 => '启用',
        ];
    }

    public static function getIsClosedList()
    {
        return [
            0 => '未结单',
            1 => '已结单',
        ];
    }
}