<?php

namespace app\stor\model;

use think\Model;

class StockSnModel extends Model
{
    protected $table = 'stor_stock_sn';

    protected $autoWriteTimestamp = false;

    public static function addSn($materialId, $sns)
    {
        $data = [];
        foreach ($sns as $sn) {
            $data[] = [
                'material_id' => $materialId,
                'sn' => $sn,
                'status' => 1,
                'create_time' => time(),
                'update_time' => time()
            ];
        }
        return self::insertAll($data);
    }

    public static function getAvailableSns($materialId)
    {
        return self::where(['material_id' => $materialId, 'status' => 1])->column('sn');
    }

    public static function useSn($materialId, $sns)
    {
        return self::where(['material_id' => $materialId, 'sn' => ['in', $sns]])->update(['status' => 0]);
    }

    public static function returnSn($materialId, $sns)
    {
        return self::where(['material_id' => $materialId, 'sn' => ['in', $sns]])->update(['status' => 1]);
    }

    public static function deleteSn($materialId, $sns)
    {
        return self::where(['material_id' => $materialId, 'sn' => ['in', $sns]])->delete();
    }
}