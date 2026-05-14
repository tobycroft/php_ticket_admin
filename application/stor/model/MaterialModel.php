<?php

namespace app\stor\model;

use think\Model;

class MaterialModel extends Model
{
    protected $table = 'stor_material';

    protected $autoWriteTimestamp = false;

    public static function getList($map = [])
    {
        $list = self::where($map)->whereIn('status', [0, 1])->order('id DESC')->select();
        
        foreach ($list as &$item) {
            $item['current_stock'] = self::getCurrentStock($item['id']);
        }
        
        return $list;
    }

    public static function getCurrentStock($materialId)
    {
        return MaterialSnModel::getAvailableCount($materialId);
    }

    public static function getInfo($id)
    {
        return self::where('id', $id)->find();
    }

    public static function add($data)
    {
        $id = self::insertGetId($data);
        StockModel::add(['material_id' => $id]);
        return $id;
    }

    public static function edit($data)
    {
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

    public static function scrap($id)
    {
        return self::where('id', $id)->update(['status' => 2]);
    }

    public static function getScrapList()
    {
        return self::where('status', 2)->order('id DESC')->select();
    }

    public static function restore($id)
    {
        return self::where('id', $id)->update(['status' => 1]);
    }
}