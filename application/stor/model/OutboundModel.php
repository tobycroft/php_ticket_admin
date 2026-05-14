<?php

namespace app\stor\model;

use think\Model;

class OutboundModel extends Model
{
    protected $table = 'stor_outbound';

    protected $autoWriteTimestamp = false;

    public static function getList($map = [])
    {
        return self::where($map)->order('id DESC')->select();
    }

    public static function getInfo($id)
    {
        return self::where('id', $id)->find();
    }

    public static function generateCode()
    {
        $prefix = 'CK' . date('Ymd');
        $count = self::where('code', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function add($data)
    {
        $data['code'] = self::generateCode();
        return self::insertGetId($data);
    }

    public static function edit($data)
    {
        return self::where('id', $data['id'])->update($data);
    }

    public static function deleteById($id)
    {
        OutboundItemModel::where('outbound_id', $id)->delete();
        return self::where('id', $id)->update(['status' => 0]);
    }
}