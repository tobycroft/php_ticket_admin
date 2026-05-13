<?php

namespace app\stor\model;

use think\Model;

class MaterialModel extends Model
{
    protected $table = 'stor_material';

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
        $data['create_time'] = time();
        $data['update_time'] = time();
        $id = self::insertGetId($data);
        StockModel::add(['material_id' => $id]);
        return $id;
    }

    public static function edit($data)
    {
        $data['update_time'] = time();
        return self::where('id', $data['id'])->update($data);
    }

    public static function deleteById($id)
    {
        StockModel::where('material_id', $id)->delete();
        StockSnModel::where('material_id', $id)->delete();
        return self::where('id', $id)->delete();
    }

    public static function setStatus($type, $ids)
    {
        $status = $type == 'enable' ? 1 : 0;
        return self::where('id', 'in', $ids)->update(['status' => $status]);
    }

    public static function checkCodeExists($code, $id = 0)
    {
        $map = ['code' => $code];
        if ($id > 0) {
            $map['id'] = ['neq', $id];
        }
        return self::where($map)->count() > 0;
    }
}