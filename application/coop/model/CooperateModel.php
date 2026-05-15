<?php

namespace app\coop\model;

use think\Model;

class CooperateModel extends Model
{
    protected $table = 'mt_cooperate';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'creator_id' => 'integer',
        'receiver_id' => 'integer',
        'is_canceled' => 'integer',
        'is_closed' => 'integer',
        'closer_id' => 'integer',
        'priority' => 'integer',
        'status' => 'integer',
    ];

    public function notes()
    {
        return $this->hasMany('CooperateNoteModel', 'cooperate_id', 'id');
    }

    public static function getStatusList()
    {
        return [
            0 => '禁用',
            1 => '启用',
        ];
    }
}