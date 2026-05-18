<?php

namespace app\stor\model;

use think\Model;

class ProjectModel extends Model
{
    protected $table = 'stor_project';

    protected $autoWriteTimestamp = false;

    public static function getList($map = [])
    {
        return self::where($map)->order('id DESC')->select();
    }

    public static function getInfo($id)
    {
        return self::where('id', $id)->find();
    }

    public static function add($data)
    {
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        $id = self::insertGetId($data);
        action_log('project_add', 'stor_project', $id, UID, $data['name'] ?? '');
        return $id;
    }

    public static function edit($data)
    {
        $data['update_time'] = date('Y-m-d H:i:s');
        $result = self::where('id', $data['id'])->update($data);
        if ($result) {
            $info = self::getInfo($data['id']);
            action_log('project_edit', 'stor_project', $data['id'], UID, $info['name'] ?? '');
        }
        return $result;
    }

    public static function deleteById($id)
    {
        $info = self::getInfo($id);
        $result = self::where('id', $id)->delete();
        if ($result) {
            action_log('project_delete', 'stor_project', $id, UID, $info['name'] ?? '');
        }
        return $result;
    }

    public static function setStatus($type, $ids)
    {
        $status = $type == 'enable' ? 1 : 0;
        return self::where('id', 'in', $ids)->update(['status' => $status]);
    }
}