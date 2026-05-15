<?php

namespace app\coop\model;

use think\Model;

class CooperateNoteModel extends Model
{
    protected $table = 'mt_cooperate_note';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'cooperate_id' => 'integer',
        'user_id' => 'integer',
    ];
}