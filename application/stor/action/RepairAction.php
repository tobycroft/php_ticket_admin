<?php

namespace app\stor\action;

use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;
use app\stor\model\RepairModel;

class RepairAction
{
    public static function addRepair($sn, $problem)
    {
        $snInfo = MaterialSnModel::where('sn', $sn)->where('status', 1)->find();
        if (!$snInfo) {
            throw new \Exception('SN码不存在或已被使用');
        }

        $repairInfo = RepairModel::where('sn', $sn)->where('status', 2)->find();
        if ($repairInfo) {
            throw new \Exception('该物料正在维修中，无法重复维修');
        }

        $materialId = $snInfo['material_id'];

        RepairModel::add([
            'material_id' => $materialId,
            'sn' => $sn,
            'problem' => $problem,
            'create_user' => UID
        ]);

        MaterialSnModel::repairSn($materialId, $sn);
    }

    public static function completeRepair($repairId, $result)
    {
        $repairInfo = RepairModel::getInfo($repairId);
        if (!$repairInfo) {
            throw new \Exception('维修单不存在');
        }

        if ($repairInfo['status'] != 2) {
            throw new \Exception('只有维修中的订单才能完成');
        }

        RepairModel::complete($repairId, $result);
        MaterialSnModel::returnFromRepair($repairInfo['material_id'], $repairInfo['sn']);
    }

    public static function scrapRepair($repairId, $result)
    {
        $repairInfo = RepairModel::getInfo($repairId);
        if (!$repairInfo) {
            throw new \Exception('维修单不存在');
        }

        RepairModel::scrap($repairId, $result);
        MaterialSnModel::scrapSn($repairInfo['material_id'], $repairInfo['sn']);
    }

    public static function getMaterialInfo($materialId)
    {
        return MaterialModel::getInfo($materialId);
    }
}