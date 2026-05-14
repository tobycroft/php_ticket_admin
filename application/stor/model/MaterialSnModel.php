<?php

namespace app\stor\model;

use think\Model;

class MaterialSnModel extends Model
{
    protected $table = 'stor_material_sn';

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
        return self::insertGetId($data);
    }

    public static function addBatch($materialId, $sns)
    {
        $data = [];
        foreach ($sns as $sn) {
            $data[] = [
                'material_id' => $materialId,
                'sn' => $sn,
                'status' => 1,
                'create_time' => date('Y-m-d H:i:s'),
                'update_time' => date('Y-m-d H:i:s')
            ];
        }
        return self::insertAll($data);
    }

    public static function edit($data)
    {
        $data['update_time'] = date('Y-m-d H:i:s');
        return self::where('id', $data['id'])->update($data);
    }

    public static function deleteById($id)
    {
        return self::where('id', $id)->delete();
    }

    public static function allocateToProject($snIds, $projectId)
    {
        return self::where('id', 'in', $snIds)->update([
            'project_id' => $projectId,
            'status' => 0,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public static function releaseFromProject($snIds)
    {
        return self::where('id', 'in', $snIds)->update([
            'project_id' => null,
            'status' => 1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public static function getAvailableSnByMaterial($materialId)
    {
        return self::where('material_id', $materialId)
            ->where('status', 1)
            ->whereNull('project_id')
            ->select();
    }

    public static function getAvailableCount($materialId)
    {
        return self::where('material_id', $materialId)
            ->where('status', 1)
            ->whereNull('project_id')
            ->count();
    }
}