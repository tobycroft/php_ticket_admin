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
        $data['create_time'] = time();
        $data['update_time'] = time();
        return self::insert($data);
    }

    public static function edit($data)
    {
        $data['update_time'] = time();
        return self::where('id', $data['id'])->update($data);
    }

    public static function scrap($id)
    {
        return self::where('id', $id)->update(['status' => 2]);
    }

    public static function setStatus($type, $ids)
    {
        $status = $type == 'enable' ? 1 : 0;
        return self::where('id', 'in', $ids)->update(['status' => $status]);
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