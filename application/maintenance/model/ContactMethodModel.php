<?php

namespace app\maintenance\model;

use think\Model;

class ContactMethodModel extends Model
{
    protected $table = 'mt_contact_method';

    protected $autoWriteTimestamp = false;

    protected $type = [
        'status' => 'integer',
        'sort' => 'integer',
    ];
}