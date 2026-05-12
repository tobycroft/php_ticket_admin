<?php

namespace app\maintenance\model;

use think\Model;

class EventNoteModel extends Model
{
    protected $table = 'mt_event_note';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'event_id' => 'integer',
        'user_id' => 'integer',
    ];

    // 重命名方法避免与父类静态方法冲突
    public function getEvent()
    {
        return $this->belongsTo('EventModel', 'event_id', 'id');
    }
}