<?php

namespace app\stor\model;

use think\Model;

class RepairModel extends Model
{
    protected $table = 'stor_repair';

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
        $prefix = 'WX' . date('Ymd');
        $count = self::where('code', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function add($data)
    {
        $insertData = [
            'material_id' => $data['material_id'],
            'sn' => $data['sn'],
            'problem' => $data['problem'],
            'code' => self::generateCode(),
            'status' => 2,
            'create_user' => $data['create_user'] ?? 0
        ];
        return self::insertGetId($insertData);
    }

    public static function edit($data)
    {
        return self::where('id', $data['id'])->update($data);
    }

    public static function complete($id, $result)
    {
        return self::where('id', $id)->update([
            'status' => 1,
            'repair_result' => $result,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public static function scrap($id, $result)
    {
        return self::where('id', $id)->update([
            'status' => 3,
            'repair_result' => $result,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }
}