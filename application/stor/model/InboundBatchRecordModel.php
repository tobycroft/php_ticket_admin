<?php

namespace app\stor\model;

use think\Model;

class InboundBatchRecordModel extends Model
{
    protected $table = 'stor_inbound_batch_record';

    protected $autoWriteTimestamp = false;

    public static function getList($map = [])
    {
        return self::where($map)->order('id DESC')->select();
    }

    public static function getInfo($id)
    {
        return self::where('id', $id)->find();
    }

    public static function add($data)
    {
        $data['create_time'] = date('Y-m-d H:i:s');
        return self::insertGetId($data);
    }
}