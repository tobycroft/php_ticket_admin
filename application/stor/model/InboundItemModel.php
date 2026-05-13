<?php

namespace app\stor\model;

use think\Model;

class InboundItemModel extends Model
{
    protected $name = 'stor_inbound_item';

    public static function addItems($inboundId, $items)
    {
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'inbound_id' => $inboundId,
                'material_id' => $item['material_id'],
                'quantity' => $item['quantity'],
                'sns' => isset($item['sns']) ? json_encode($item['sns']) : null,
                'remark' => isset($item['remark']) ? $item['remark'] : null
            ];
        }
        return self::insertAll($data);
    }

    public static function getItems($inboundId)
    {
        return self::where('inbound_id', $inboundId)->select();
    }

    public static function deleteItems($inboundId)
    {
        return self::where('inbound_id', $inboundId)->delete();
    }
}