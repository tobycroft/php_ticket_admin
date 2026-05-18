<?php

namespace app\stor\model;

use app\admin\model\ActionLog;
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
        
        $newData = self::where('id', $id)->find();
        ActionLog::addRecord('stor', 'stor_material', 'add', $id, $data['name'] ?? '新物料', $newData->toArray(), '新增物料');
        
        return $id;
    }

    public static function edit($data)
    {
        $oldData = self::where('id', $data['id'])->find();
        $result = self::where('id', $data['id'])->update($data);
        
        if ($result) {
            ActionLog::editRecord('stor', 'stor_material', $data['id'], $oldData['name'] ?? '物料', $oldData->toArray(), $data, '编辑物料');
        }
        
        return $result;
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
        $actionType = $type == 'enable' ? 'enable' : 'disable';
        $remark = $type == 'enable' ? '启用物料' : '禁用物料';
        
        foreach ($ids as $id) {
            $oldData = self::where('id', $id)->find();
            self::where('id', $id)->update(['status' => $status]);
            ActionLog::editRecord('stor', 'stor_material', $id, $oldData['name'] ?? '物料', $oldData->toArray(), ['status' => $status], $remark);
        }
        
        return true;
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
        $oldData = self::where('id', $id)->find();
        $result = self::where('id', $id)->update(['status' => 2]);
        
        if ($result) {
            ActionLog::editRecord('stor', 'stor_material', $id, $oldData['name'] ?? '物料', $oldData->toArray(), ['status' => 2], '作物料');
        }
        
        return $result;
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