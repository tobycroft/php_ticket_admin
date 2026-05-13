<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\InboundModel;
use app\stor\model\InboundItemModel;
use app\stor\model\MaterialModel;
use app\stor\model\StockModel;
use app\stor\model\StockSnModel;

class Inbound extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = InboundModel::getList($map);

        return ZBuilder::make('table')
            ->setPageTitle('入库管理')
            ->setTableName('stor_inbound')
            ->setSearch(['code' => '入库单号', 'supplier' => '供应商'])
            ->addColumns([
                ['id', 'ID'],
                ['code', '入库单号'],
                ['supplier', '供应商'],
                ['status', '状态', ['已作废', '正常']],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,delete')
            ->addRightButtons('edit,delete,detail')
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                $inboundId = InboundModel::add(['supplier' => $data['supplier'], 'remark' => $data['remark'], 'create_user' => UID]);
                
                $items = json_decode($data['items'], true);
                InboundItemModel::addItems($inboundId, $items);
                
                foreach ($items as $item) {
                    StockModel::updateStock($item['material_id'], $item['quantity']);
                    if (!empty($item['sns'])) {
                        StockSnModel::addSn($item['material_id'], $item['sns']);
                    }
                }
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '入库成功', 'url' => url('index')]);
            }
            $this->success('入库成功', url('index'));
        }

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_options = [];
        foreach ($material_list as $item) {
            $material_options[$item['id']] = $item['name'] . '(' . $item['code'] . ')';
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增入库')
            ->addFormItems([
                ['text', 'supplier', '供应商'],
                ['textarea', 'remark', '备注'],
                ['html', 'items', '入库明细', '', '<div id="inbound-items"></div>']
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
                InboundModel::edit(['id' => $id, 'supplier' => $data['supplier'], 'remark' => $data['remark']]);
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

        $info = InboundModel::getInfo($id);

        return ZBuilder::make('form')
            ->setPageTitle('编辑入库单')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'code', '入库单号'],
                ['text', 'supplier', '供应商'],
                ['textarea', 'remark', '备注']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = InboundModel::getInfo($id);
        $items = InboundItemModel::getItems($id);

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        foreach ($items as &$item) {
            $item['material_name'] = $material_map[$item['material_id']];
        }

        return ZBuilder::make('form')
            ->setPageTitle('入库单详情')
            ->addFormItems([
                ['static', 'code', '入库单号'],
                ['static', 'supplier', '供应商'],
                ['static', 'remark', '备注'],
                ['static', 'create_time', '创建时间', 'datetime'],
                ['html', 'items', '入库明细', '', $this->renderItems($items)]
            ])
            ->setFormData($info)
            ->fetch();
    }

    private function renderItems($items)
    {
        $html = '<table class="table table-bordered"><thead><tr><th>物料</th><th>数量</th><th>SN码</th><th>备注</th></tr></thead><tbody>';
        foreach ($items as $item) {
            $sns = !empty($item['sns']) ? json_decode($item['sns'], true) : [];
            $html .= '<tr><td>' . $item['material_name'] . '</td><td>' . $item['quantity'] . '</td><td>' . implode(',', $sns) . '</td><td>' . $item['remark'] . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                InboundModel::deleteById($id);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '删除成功']);
        }
        $this->success('删除成功');
    }
}