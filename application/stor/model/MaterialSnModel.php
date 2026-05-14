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
        if (empty($sns)) {
            return 0;
        }
        
        $existingSns = self::where('material_id', $materialId)
            ->where('sn', 'in', $sns)
            ->column('sn');
        
        $existingSns = array_flip($existingSns);
        
        $data = [];
        foreach ($sns as $sn) {
            if (!isset($existingSns[$sn])) {
                $data[] = [
                    'material_id' => $materialId,
                    'sn' => $sn,
                    'status' => 1
                ];
            }
        }
        
        if (empty($data)) {
            return 0;
        }
        
        $model = new self();
        $model->startTrans();
        try {
            $result = $model->insertAll($data);
            $model->commit();
            return $result;
        } catch (\Exception $e) {
            $model->rollback();
            throw $e;
        }
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