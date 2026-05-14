<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;
use app\stor\model\RepairModel;
use app\stor\model\StockSnModel;

class Repair extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = RepairModel::getList($map);

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        $status_map = [1 => '维修中', 2 => '已完成', 3 => '已报废'];

        return ZBuilder::make('table')
            ->setPageTitle('维修管理')
            ->setTableName('stor_repair')
            ->setSearch(['code' => '维修单号'])
            ->addColumns([
                ['id', 'ID'],
                ['code', '维修单号'],
                ['material_id', '物料', $material_map],
                ['sn', 'SN码'],
                ['status', '状态', $status_map],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButtons('edit,detail,complete,scrap')
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                $sn = is_array($data['sn']) ? $data['sn'][0] : $data['sn'];
                
                $snInfo = MaterialSnModel::where('sn', $sn)->where('status', 1)->find();
                if (!$snInfo) {
                    throw new \Exception('SN码不存在或已被使用');
                }
                
                RepairModel::add([
                    'material_id' => $snInfo['material_id'],
                    'sn' => $sn,
                    'problem' => $data['problem'],
                    'create_user' => UID
                ]);
                
                StockSnModel::useSn($snInfo['material_id'], [$sn]);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '维修申请已提交', 'url' => url('index')]);
            }
            $this->success('维修申请已提交', url('index'));
        }

        $sn_list = MaterialSnModel::where('status', 1)->whereNull('project_id')->select();
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

    public function edit($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                RepairModel::edit(['id' => $id, 'problem' => $data['problem']]);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '编辑成功', 'url' => cookie('__forward__')]);
            }
            $this->success('编辑成功', cookie('__forward__'));
        }

        $info = RepairModel::getInfo($id);

        return ZBuilder::make('form')
            ->setPageTitle('编辑维修申请')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'code', '维修单号'],
                ['textarea', 'problem', '故障描述']
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

        $material = MaterialModel::getInfo($info['material_id']);
        $info['material_name'] = $material['name'];
        $info['material_location'] = $material['location'];
        $info['material_purchase_date'] = $material['purchase_date'];
        $info['material_seller'] = $material['seller'];
        $info['material_warranty_end'] = $material['warranty_end'];
        $info['material_remark'] = $material['remark'];

        $status_map = [1 => '维修中', 2 => '已完成', 3 => '已报废'];
        $info['status_text'] = $status_map[$info['status']];

        return ZBuilder::make('form')
            ->setPageTitle('维修单详情')
            ->addFormItems([
                ['static', 'code', '维修单号'],
                ['static', 'material_name', '物料'],
                ['static', 'sn', 'SN码'],
                ['static', 'problem', '故障描述'],
                ['static', 'status_text', '状态'],
                ['static', 'repair_result', '维修结果'],
                ['static', 'create_time', '创建时间'],
                ['static', 'update_time', '更新时间']
            ])
            ->setExtraHtml('<div class="block"><div class="block-header bg-info"><h3 class="block-title"><i class="fa fa-info-circle mr-5"></i>物料信息</h3></div><div class="block-content"><table class="table table-bordered"><tr><th>存放位置</th><td>' . ($material['location'] ?: '-') . '</td></tr><tr><th>购入日期</th><td>' . ($material['purchase_date'] ?: '-') . '</td></tr><tr><th>销售方</th><td>' . ($material['seller'] ?: '-') . '</td></tr><tr><th>保修期截止</th><td>' . ($material['warranty_end'] ?: '-') . '</td></tr><tr><th>备注</th><td>' . ($material['remark'] ?: '-') . '</td></tr></table></div></div>')
            ->setFormData($info)
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
                $info = RepairModel::getInfo($id);
                RepairModel::complete($id, $data['repair_result']);
                StockSnModel::returnSn($info['material_id'], [$info['sn']]);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '维修完成', 'url' => cookie('__forward__')]);
            }
            $this->success('维修完成', cookie('__forward__'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('完成维修')
            ->addFormItems([
                ['hidden', 'id'],
                ['textarea', 'repair_result', '维修结果', '必填']
            ])
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
                $info = RepairModel::getInfo($id);
                RepairModel::scrap($id, $data['repair_result']);
                StockSnModel::deleteSn($info['material_id'], [$info['sn']]);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '已报废', 'url' => cookie('__forward__')]);
            }
            $this->success('已报废', cookie('__forward__'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('报废确认')
            ->addFormItems([
                ['hidden', 'id'],
                ['textarea', 'repair_result', '报废原因', '必填']
            ])
            ->fetch();
    }
}