<?php

namespace app\maintenance\model;

use think\Model;

class EventNoteModel extends Model
{
    protected $table = 'mt_event_note';

    protected $autoWriteTimestamp = true;

    protected $createTime = 'create_time';

    protected $updateTime = false;

    protected $type = [
        'event_id' => 'integer',
        'user_id' => 'integer',
        'create_time' => 'integer',
    ];

    public function event()
    {
        return $this->belongsTo('EventModel', 'event_id', 'id');
    }
}