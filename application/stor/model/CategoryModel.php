<?php

namespace app\stor\model;

use think\Model;

class CategoryModel extends Model
{
    protected $table = 'stor_category';

    protected $autoWriteTimestamp = false;

    public static function getList($map = [])
    {
        return self::where($map)->whereIn('status', [0, 1])->order('sort ASC, id ASC')->select();
    }

    public static function getInfo($id)
    {
        return self::where('id', $id)->find();
    }

    public static function add($data)
    {
        $result = self::insert($data);
        if ($result) {
            action_log('category_add', 'stor_category', '', UID, $data['name'] ?? '');
        }
        return $result;
    }

    public static function edit($data)
    {
        $result = self::where('id', $data['id'])->update($data);
        if ($result) {
            $info = self::getInfo($data['id']);
            action_log('category_edit', 'stor_category', $data['id'], UID, $info['name'] ?? '');
        }
        return $result;
    }

    public static function scrap($id)
    {
        $info = self::getInfo($id);
        $result = self::where('id', $id)->update(['status' => 2]);
        if ($result) {
            action_log('category_scrap', 'stor_category', $id, UID, $info['name'] ?? '');
        }
        return $result;
    }

    public static function setStatus($type, $ids)
    {
        $status = $type == 'enable' ? 1 : 0;
        $result = self::where('id', 'in', $ids)->update(['status' => $status]);
        if ($result) {
            foreach ($ids as $id) {
                $info = self::getInfo($id);
                action_log('category_' . $type, 'stor_category', $id, UID, $info['name'] ?? '');
            }
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