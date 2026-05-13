<?php

namespace app\stor\model;

use think\Model;

class OutboundItemModel extends Model
{
    protected $table = 'stor_outbound_item';

    public static function addItems($outboundId, $items)
    {
        $data = [];
        foreach ($items as $item) {
            $data[] = [
                'outbound_id' => $outboundId,
                'material_id' => $item['material_id'],
                'quantity' => $item['quantity'],
                'sns' => isset($item['sns']) ? json_encode($item['sns']) : null,
                'remark' => isset($item['remark']) ? $item['remark'] : null
            ];
        }
        return self::insertAll($data);
    }

    public static function getItems($outboundId)
    {
        return self::where('outbound_id', $outboundId)->select();
    }

    public static function deleteItems($outboundId)
    {
        return self::where('outbound_id', $outboundId)->delete();
    }
}