<?php

namespace app\stor\model;

use think\Model;

class InventoryItemModel extends Model
{
    protected $name = 'stor_inventory_item';

    public static function addItems($inventoryId, $items)
    {
        $data = [];
        foreach ($items as $item) {
            $diff = $item['actual_qty'] - $item['stock_qty'];
            $data[] = [
                'inventory_id' => $inventoryId,
                'material_id' => $item['material_id'],
                'stock_qty' => $item['stock_qty'],
                'actual_qty' => $item['actual_qty'],
                'diff_qty' => $diff,
                'remark' => isset($item['remark']) ? $item['remark'] : null
            ];
        }
        return self::insertAll($data);
    }

    public static function getItems($inventoryId)
    {
        return self::where('inventory_id', $inventoryId)->select();
    }

    public static function updateItem($id, $data)
    {
        $diff = $data['actual_qty'] - $data['stock_qty'];
        return self::where('id', $id)->update([
            'actual_qty' => $data['actual_qty'],
            'diff_qty' => $diff,
            'remark' => isset($data['remark']) ? $data['remark'] : null
        ]);
    }
}