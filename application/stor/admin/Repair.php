<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\action\RepairAction;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;

class Repair extends Admin
{
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                $sn = $data['sn'];
                if (is_array($sn)) {
                    $sn = $sn[0];
                }

                RepairAction::addRepair($sn, $data['problem']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '维修申请已提交', 'url' => url('RepairProcessing/index')]);
            }
            $this->success('维修申请已提交', url('RepairProcessing/index'));
        }

        $sn_list = MaterialSnModel::where('status', '<>', 3)->select();
        $sn_options = ['' => '请选择SN码'];
        foreach ($sn_list as $item) {
            $material = MaterialModel::getInfo($item['material_id']);
            $sn_options[$item['sn']] = $item['sn'] . ' (' . ($material['name'] ?? '未知物料') . ')';
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增维修申请')
            ->addFormItems([
                ['select', 'sn', 'SN码', '必填，请选择SN码', $sn_options],
                ['textarea', 'problem', '故障描述', '必填']
            ])
            ->fetch();
    }
}