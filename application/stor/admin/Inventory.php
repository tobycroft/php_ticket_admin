<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\InventoryItemModel;
use app\stor\model\InventoryModel;
use app\stor\model\MaterialModel;
use app\stor\model\StockModel;

class Inventory extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = InventoryModel::getList($map);

        $status_map = [1 => '进行中', 2 => '已完成'];

        return ZBuilder::make('table')
            ->setPageTitle('库存盘点')
            ->setTableName('stor_inventory')
            ->setSearch(['code' => '盘点单号'])
            ->addColumns([
                ['id', 'ID'],
                ['code', '盘点单号'],
                ['status', '状态', $status_map],
                ['create_time', '创建时间', 'datetime'],
                ['update_time', '更新时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButtons('edit,detail,complete')
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                $inventoryId = InventoryModel::add(['remark' => $data['remark'], 'create_user' => UID]);
                
                $items = json_decode($data['items'], true);
                InventoryItemModel::addItems($inventoryId, $items);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '盘点任务已创建', 'url' => url('index')]);
            }
            $this->success('盘点任务已创建', url('index'));
        }

        $material_list = MaterialModel::getList(['status' => 1]);
        $items = [];
        foreach ($material_list as $item) {
            $stock = StockModel::getInfo($item['id']);
            $items[] = [
                'material_id' => $item['id'],
                'material_name' => $item['name'],
                'stock_qty' => $stock ? $stock['quantity'] : 0,
                'actual_qty' => 0
            ];
        }

        return ZBuilder::make('form')
            ->setPageTitle('创建盘点任务')
            ->addFormItems([
                ['textarea', 'remark', '备注'],
                ['hidden', 'items', '', json_encode($items)]
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

        $info = InventoryModel::getInfo($id);
        if ($info['status'] == 2) {
            $this->error('已完成的盘点任务不能编辑');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                $items = json_decode($data['items'], true);
                foreach ($items as $item) {
                    if (isset($item['id'])) {
                        InventoryItemModel::updateItem($item['id'], $item);
                    }
                }
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '保存成功', 'url' => cookie('__forward__')]);
            }
            $this->success('保存成功', cookie('__forward__'));
        }

        $items = InventoryItemModel::getItems($id);
        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        foreach ($items as &$item) {
            $item['material_name'] = $material_map[$item['material_id']];
        }

        return ZBuilder::make('form')
            ->setPageTitle('录入盘点数据')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'code', '盘点单号'],
                ['html', 'items', '盘点明细', '', $this->renderItems($items)]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = InventoryModel::getInfo($id);
        $items = InventoryItemModel::getItems($id);

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        foreach ($items as &$item) {
            $item['material_name'] = $material_map[$item['material_id']];
        }

        $status_map = [1 => '进行中', 2 => '已完成'];
        $info['status_text'] = $status_map[$info['status']];

        return ZBuilder::make('form')
            ->setPageTitle('盘点详情')
            ->addFormItems([
                ['static', 'code', '盘点单号'],
                ['static', 'status_text', '状态'],
                ['static', 'remark', '备注'],
                ['static', 'create_time', '创建时间', 'datetime'],
                ['html', 'items', '盘点明细', '', $this->renderItems($items)]
            ])
            ->setFormData($info)
            ->fetch();
    }

    private function renderItems($items)
    {
        $html = '<table class="table table-bordered"><thead><tr><th>物料</th><th>账面数量</th><th>实际数量</th><th>差异</th><th>备注</th></tr></thead><tbody>';
        foreach ($items as $item) {
            $diff_class = $item['diff_qty'] != 0 ? 'text-danger' : '';
            $html .= '<tr><td>' . $item['material_name'] . '</td><td>' . $item['stock_qty'] . '</td><td>' . $item['actual_qty'] . '</td><td class="' . $diff_class . '">' . $item['diff_qty'] . '</td><td>' . $item['remark'] . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    public function complete($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        try {
            InventoryModel::complete($id);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        $this->success('盘点已完成', cookie('__forward__'));
    }
}