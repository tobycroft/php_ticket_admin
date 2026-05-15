<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\InboundModel;
use app\stor\model\InboundItemModel;
use app\stor\model\InboundTypeModel;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;
use app\stor\model\ProjectModel;

class Inbound extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = InboundModel::getList($map);

        $type_list = InboundTypeModel::getList(['status' => 1]);
        $type_map = [];
        foreach ($type_list as $item) {
            $type_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('入库管理')
            ->setTableName('stor_inbound')
            ->setSearch(['code' => '入库单号'])
            ->addColumns([
                ['id', 'ID'],
                ['code', '入库单号'],
                ['type', '入库类型', $type_map],
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
                    throw new \Exception('请选择要入库的SN码');
                }
                
                $materialId = $data['material_id'];
                
                $inboundId = InboundModel::add(['type' => $data['type'], 'project_id' => $data['project_id'], 'remark' => $data['remark'], 'create_user' => UID]);
                
                InboundItemModel::addItems($inboundId, [['material_id' => $materialId, 'quantity' => count($sns), 'sns' => json_encode($sns)]]);
                
                MaterialSnModel::where('material_id', $materialId)
                    ->where('sn', 'in', $sns)
                    ->update(['project_id' => null, 'status' => 1, 'update_time' => date('Y-m-d H:i:s')]);
                
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

        $this->assign('type_list', InboundTypeModel::getList(['status' => 1]));
        $this->assign('project_list', ProjectModel::getList(['status' => 1]));
        
        return $this->fetch('inbound_add');
    }

    public function materials()
    {
        $projectId = $this->request->get('project_id');
        
        if (!$projectId) {
            return json(['code' => 0, 'msg' => '缺少参数']);
        }
        
        $sns = MaterialSnModel::where('project_id', $projectId)
            ->where('status', 1)
            ->field('material_id, sn')
            ->select();
        
        $materialIds = [];
        foreach ($sns as $item) {
            if (!in_array($item['material_id'], $materialIds)) {
                $materialIds[] = $item['material_id'];
            }
        }
        
        $materials = [];
        if (!empty($materialIds)) {
            $materials = MaterialModel::where('id', 'in', $materialIds)->where('status', 1)->select();
        }
        
        return json(['code' => 1, 'data' => $materials]);
    }

    public function sns()
    {
        $projectId = $this->request->get('project_id');
        $materialId = $this->request->get('material_id');
        
        if (!$projectId || !$materialId) {
            return json(['code' => 0, 'msg' => '缺少参数']);
        }
        
        $material = MaterialModel::getInfo($materialId);
        $materialName = $material ? $material['name'] : '未知物料';
        
        $sns = MaterialSnModel::where('project_id', $projectId)
            ->where('material_id', $materialId)
            ->where('status', 1)
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
                InboundModel::edit(['id' => $id, 'type' => $data['type'], 'remark' => $data['remark']]);
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

        $type_list = InboundTypeModel::getList(['status' => 1]);
        $type_options = [];
        foreach ($type_list as $item) {
            $type_options[$item['id']] = $item['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑入库单')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'code', '入库单号'],
                ['select', 'type', '入库类型', '', $type_options],
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

        $type_list = InboundTypeModel::getList(['status' => 1]);
        $type_map = [];
        foreach ($type_list as $item) {
            $type_map[$item['id']] = $item['name'];
        }
        $info['type_text'] = $type_map[$info['type']] ?? '未知';

        return ZBuilder::make('form')
            ->setPageTitle('入库单详情')
            ->addFormItems([
                ['static', 'code', '入库单号'],
                ['static', 'type_text', '入库类型'],
                ['static', 'remark', '备注'],
                ['static', 'create_time', '创建时间', 'datetime']
            ])
            ->setExtraHtml($this->renderItems($items), 'form_top')
            ->setFormData($info)
            ->hideBtn('submit')
            ->fetch();
    }

    private function renderItems($items)
    {
        $html = '<table class="table table-bordered"><thead><tr><th>物料</th><th>数量</th><th>SN码</th><th>备注</th></tr></thead><tbody>';
        $sns = [];
        foreach ($items as $item) {
            if (!empty($item['sns'])) {
                $sns_str = trim($item['sns']);
                if (strpos($sns_str, '"') === 0 && substr($sns_str, -1) === '"') {
                    $sns_str = substr($sns_str, 1, -1);
                }
                $sns_str = stripslashes($sns_str);
                $sns = json_decode($sns_str, true);
                if (!is_array($sns)) {
                    $sns = [];
                }
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