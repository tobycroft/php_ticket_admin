<?php

namespace app\stor\model;

use think\Model;

class InventoryModel extends Model
{
    protected $table = 'stor_inventory';

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
        $prefix = 'PD' . date('Ymd');
        $count = self::where('code', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function add($data)
    {
        $data['code'] = self::generateCode();
        $data['create_time'] = time();
        $data['update_time'] = time();
        return self::insertGetId($data);
    }

    public static function complete($id)
    {
        return self::where('id', $id)->update([
            'status' => 2,
            'update_time' => time()
        ]);
    }
}