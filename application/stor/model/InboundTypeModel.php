<?php

namespace app\stor\model;

use think\Model;

class InboundTypeModel extends Model
{
    protected $table = 'stor_inbound_type';

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
        $data['update_time'] = date('Y-m-d H:i:s');
        return self::insertGetId($data);
    }

    public static function edit($data)
    {
        $data['update_time'] = date('Y-m-d H:i:s');
        return self::where('id', $data['id'])->update($data);
    }

    public static function deleteById($id)
    {
        return self::where('id', $id)->delete();
    }

    public static function setStatus($type, $ids)
    {
        $status = $type == 'enable' ? 1 : 0;
        return self::where('id', 'in', $ids)->update(['status' => $status]);
    }
}