<?php

namespace app\stor\model;

use think\Model;

class OutboundModel extends Model
{
    protected $table = 'stor_outbound';

    protected $autoWriteTimestamp = false;

    public static function getList($map = [])
    {
        return self::where($map)->where('status', 1)->order('id DESC')->select();
    }

    public static function getInfo($id)
    {
        return self::where('id', $id)->find();
    }

    public static function generateCode()
    {
        $prefix = 'CK' . date('Ymd');
        $count = self::where('code', 'like', $prefix . '%')->count();
        return $prefix . str_pad($count + 1, 4, '0', STR_PAD_LEFT);
    }

    public static function add($data)
    {
        $data['code'] = self::generateCode();
        $id = self::insertGetId($data);
        action_log('outbound_add', 'stor_outbound', $id, UID, $data['code'] ?? '');
        return $id;
    }

    public static function edit($data)
    {
        $result = self::where('id', $data['id'])->update($data);
        if ($result) {
            $info = self::getInfo($data['id']);
            action_log('outbound_edit', 'stor_outbound', $data['id'], UID, $info['code'] ?? '');
        }
        return $result;
    }

    public static function deleteById($id)
    {
        OutboundItemModel::where('outbound_id', $id)->delete();
        return self::where('id', $id)->update(['status' => 0]);
    }
}