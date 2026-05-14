<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;
use app\stor\model\OutboundModel;
use app\stor\model\OutboundItemModel;
use app\stor\model\OutboundTypeModel;
use app\stor\model\ProjectModel;
use app\stor\model\StockModel;
use app\stor\model\StockSnModel;

class Outbound extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = OutboundModel::getList($map);

        $type_list = OutboundTypeModel::getList(['status' => 1]);
        $type_map = [];
        foreach ($type_list as $item) {
            $type_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('出库管理')
            ->setTableName('stor_outbound')
            ->setSearch(['code' => '出库单号'])
            ->addColumns([
                ['id', 'ID'],
                ['code', '出库单号'],
                ['type', '出库类型', $type_map],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,delete')
            ->addRightButton('edit', ['title' => '编辑', 'icon' => 'fa fa-edit', 'href' => url('edit', ['id' => '__id__'])])
            ->addRightButton('detail', ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('detail', ['id' => '__id__'])])
            ->addRightButton('delete', ['title' => '删除', 'icon' => 'fa fa-trash'])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                $sns = $data['sns'] ?? [];
                
                if (empty($sns)) {
                    throw new \Exception('请选择SN码');
                }
                
                $materialId = $data['material_id'];
                
                $outboundId = OutboundModel::add(['type' => $data['type'], 'project_id' => $data['project_id'], 'remark' => $data['remark'], 'create_user' => UID]);
                
                OutboundItemModel::addItems($outboundId, [['material_id' => $materialId, 'quantity' => count($sns), 'sns' => json_encode($sns)]]);
                
                if ($data['type'] == 3) {
                    StockSnModel::deleteSn($materialId, $sns);
                    MaterialSnModel::where('material_id', $materialId)->where('sn', 'in', $sns)->update(['status' => 3, 'project_id' => null]);
                } else {
                    StockSnModel::useSn($materialId, $sns);
                    if (!empty($data['project_id'])) {
                        MaterialSnModel::where('material_id', $materialId)->where('sn', 'in', $sns)->update(['project_id' => $data['project_id'], 'status' => 1]);
                    } else {
                        MaterialSnModel::where('material_id', $materialId)->where('sn', 'in', $sns)->update(['status' => 1]);
                    }
                }
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '出库成功', 'url' => url('index')]);
            }
            $this->success('出库成功', url('index'));
        }

        $this->assign('material_list', MaterialModel::getList(['status' => 1]));
        $this->assign('project_list', ProjectModel::getList(['status' => 1]));
        $this->assign('type_list', OutboundTypeModel::getList(['status' => 1]));
        
        return $this->fetch('outbound_add');
    }

    public function avails()
    {
        $materialId = $this->request->get('material_id');
        
        if (!$materialId) {
            return json(['code' => 0, 'msg' => '缺少参数']);
        }
        
        $material = MaterialModel::getInfo($materialId);
        $materialName = $material ? $material['name'] : '未知物料';
        
        $sns = MaterialSnModel::where('material_id', $materialId)
            ->where('status', 1)
            ->whereNull('project_id')
            ->field('sn')
            ->select();
        
        foreach ($sns as &$item) {
            $item['material_name'] = $materialName;
        }
        
        return json(['code' => 1, 'data' => $sns]);
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
                OutboundModel::edit(['id' => $id, 'type' => $data['type'], 'remark' => $data['remark']]);
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

        $info = OutboundModel::getInfo($id);

        $type_list = OutboundTypeModel::getList(['status' => 1]);
        $type_options = [];
        foreach ($type_list as $item) {
            $type_options[$item['id']] = $item['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑出库单')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'code', '出库单号'],
                ['select', 'type', '出库类型', '', $type_options],
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

        $info = OutboundModel::getInfo($id);
        $items = OutboundItemModel::getItems($id);

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        foreach ($items as &$item) {
            $item['material_name'] = $material_map[$item['material_id']];
        }

        $type_list = OutboundTypeModel::getList(['status' => 1]);
        $type_map = [];
        foreach ($type_list as $item) {
            $type_map[$item['id']] = $item['name'];
        }
        $info['type_text'] = $type_map[$info['type']] ?? '未知';

        return ZBuilder::make('form')
            ->setPageTitle('出库单详情')
            ->addFormItems([
                ['static', 'code', '出库单号'],
                ['static', 'type_text', '出库类型'],
                ['static', 'remark', '备注'],
                ['static', 'create_time', '创建时间', 'datetime']
            ])
            ->setExtraHtml($this->renderItems($items), 'form_top')
            ->setFormData($info)
            ->fetch();
    }

    private function renderItems($items)
    {
        $html = '<table class="table table-bordered"><thead><tr><th>物料</th><th>数量</th><th>SN码</th><th>备注</th></tr></thead><tbody>';
        foreach ($items as $item) {
            $sns = !empty($item['sns']) ? json_decode($item['sns'], true) : [];
            if (!is_array($sns)) {
                $sns = [];
            }
            $html .= '<tr><td>' . $item['material_name'] . '</td><td>' . $item['quantity'] . '</td><td>' . implode(',', $sns) . '</td><td>' . ($item['remark'] ?? '') . '</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                OutboundModel::deleteById($id);
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