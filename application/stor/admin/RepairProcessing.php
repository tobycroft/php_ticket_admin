<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\action\RepairAction;
use app\stor\model\MaterialModel;
use app\stor\model\RepairModel;

class RepairProcessing extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = RepairModel::where('status', 2)->order('id DESC')->select();

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('维修中')
            ->setTableName('stor_repair')
            ->setSearch(['code' => '维修单号'])
            ->addColumns([
                ['id', 'ID'],
                ['code', '维修单号'],
                ['material_id', '物料', $material_map],
                ['sn', 'SN码'],
                ['problem', '故障描述'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['complete' => ['title' => '维修完毕', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('complete', ['id' => '__id__'])], 'scrap' => ['title' => '报废', 'icon' => 'fa fa-trash', 'class' => 'btn btn-xs btn-danger', 'href' => url('scrap', ['id' => '__id__'])], 'detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'class' => 'btn btn-xs btn-info', 'href' => url('detail', ['id' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }

    public function complete($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                RepairAction::completeRepair($id, $data['repair_result']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '维修完成', 'url' => url('index')]);
            }
            $this->success('维修完成', url('index'));
        }

        $info = RepairModel::getInfo($id);

        return ZBuilder::make('form')
            ->setPageTitle('完成维修')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'code', '维修单号'],
                ['textarea', 'repair_result', '维修结果', '必填']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function scrap($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                RepairAction::scrapRepair($id, $data['repair_result']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '已报废', 'url' => url('index')]);
            }
            $this->success('已报废', url('index'));
        }

        $info = RepairModel::getInfo($id);

        return ZBuilder::make('form')
            ->setPageTitle('报废确认')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'code', '维修单号'],
                ['textarea', 'repair_result', '报废原因', '必填']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = RepairModel::getInfo($id);

        $material = RepairAction::getMaterialInfo($info['material_id']);
        $info['material_name'] = $material['name'];
        $info['material_location'] = $material['location'];
        $info['material_purchase_date'] = $material['purchase_date'];
        $info['material_seller'] = $material['seller'];
        $info['material_warranty_end'] = $material['warranty_end'];
        $info['material_remark'] = $material['remark'];

        return ZBuilder::make('form')
            ->setPageTitle('维修单详情')
            ->addFormItems([
                ['static', 'code', '维修单号'],
                ['static', 'material_name', '物料'],
                ['static', 'sn', 'SN码'],
                ['static', 'problem', '故障描述'],
                ['static', 'create_time', '创建时间']
            ])
            ->setExtraHtml('<div class="block"><div class="block-header bg-info"><h3 class="block-title"><i class="fa fa-info-circle mr-5"></i>物料信息</h3></div><div class="block-content"><table class="table table-bordered"><tr><th>存放位置</th><td>' . ($material['location'] ?: '-') . '</td></tr><tr><th>购入日期</th><td>' . ($material['purchase_date'] ?: '-') . '</td></tr><tr><th>销售方</th><td>' . ($material['seller'] ?: '-') . '</td></tr><tr><th>保修期截止</th><td>' . ($material['warranty_end'] ?: '-') . '</td></tr><tr><th>备注</th><td>' . ($material['remark'] ?: '-') . '</td></tr></table></div></div>')
            ->setFormData($info)
            ->fetch();
    }
}