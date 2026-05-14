<?php

namespace app\stor\model;

use think\Model;

class StockModel extends Model
{
    protected $table = 'stor_stock';

    protected $autoWriteTimestamp = false;

    public static function getInfo($materialId)
    {
        return self::where('material_id', $materialId)->find();
    }

    public static function add($data)
    {
        return self::insert($data);
    }

    public static function updateStock($materialId, $quantity)
    {
        return self::where('material_id', $materialId)->setInc('quantity', $quantity);
    }

    public static function reduceStock($materialId, $quantity)
    {
        $stock = self::getInfo($materialId);
        if (!$stock || $stock['quantity'] < $quantity) {
            throw new \Exception('库存不足');
        }
        return self::where('material_id', $materialId)->setDec('quantity', $quantity);
    }

    public static function getStockList($map = [])
    {
        return self::where($map)->select();
    }
}