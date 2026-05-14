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
        if (empty($data['material_id'])) {
            throw new \Exception('物料ID不能为空');
        }
        
        $materialExists = \app\stor\model\MaterialModel::getInfo($data['material_id']);
        if (!$materialExists) {
            throw new \Exception('物料不存在');
        }
        
        if (isset($data['project_id']) && !empty($data['project_id'])) {
            $projectExists = \app\stor\model\ProjectModel::getInfo($data['project_id']);
            if (!$projectExists) {
                throw new \Exception('项目不存在');
            }
        }
        
        $data['create_time'] = date('Y-m-d H:i:s');
        $data['update_time'] = date('Y-m-d H:i:s');
        return self::insertGetId($data);
    }

    public static function addBatch($materialId, $sns, $remark = '')
    {
        if (empty($materialId)) {
            throw new \Exception('物料ID不能为空');
        }
        
        $materialExists = \app\stor\model\MaterialModel::getInfo($materialId);
        if (!$materialExists) {
            throw new \Exception('物料不存在');
        }
        
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
                $item = [
                    'material_id' => $materialId,
                    'sn' => $sn,
                    'status' => 1
                ];
                if (!empty($remark)) {
                    $item['remark'] = $remark;
                }
                $data[] = $item;
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
        if (isset($data['project_id']) && !empty($data['project_id'])) {
            $projectExists = \app\stor\model\ProjectModel::getInfo($data['project_id']);
            if (!$projectExists) {
                throw new \Exception('项目不存在');
            }
        }
        
        if (isset($data['material_id'])) {
            $materialExists = \app\stor\model\MaterialModel::getInfo($data['material_id']);
            if (!$materialExists) {
                throw new \Exception('物料不存在');
            }
        }
        
        $data['update_time'] = date('Y-m-d H:i:s');
        return self::where('id', $data['id'])->update($data);
    }

    public static function deleteById($id)
    {
        return self::where('id', $id)->delete();
    }

    public static function allocateToProject($snIds, $projectId)
    {
        if (!empty($projectId)) {
            $projectExists = \app\stor\model\ProjectModel::getInfo($projectId);
            if (!$projectExists) {
                throw new \Exception('项目不存在');
            }
        }
        
        return self::where('id', 'in', $snIds)->update([
            'project_id' => $projectId,
            'status' => 0,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public static function repairSn($materialId, $sn)
    {
        return self::where('material_id', $materialId)->where('sn', $sn)->update([
            'status' => 2,
            'project_id' => -1,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public static function returnFromRepair($materialId, $sn)
    {
        return self::where('material_id', $materialId)->where('sn', $sn)->update([
            'status' => 1,
            'project_id' => null,
            'update_time' => date('Y-m-d H:i:s')
        ]);
    }

    public static function scrapSn($materialId, $sn)
    {
        return self::where('material_id', $materialId)->where('sn', $sn)->update([
            'status' => 3,
            'project_id' => null,
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