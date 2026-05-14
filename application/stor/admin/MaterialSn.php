<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;
use app\stor\model\ProjectModel;

class MaterialSn extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = MaterialSnModel::getList($map);

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        $project_list = ProjectModel::getList(['status' => 1]);
        $project_map = ['' => '未分配', '0' => '未分配', '-1' => '维修中'];
        foreach ($project_list as $item) {
            $project_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('物料SN管理')
            ->setTableName('stor_material_sn')
            ->setSearch(['sn' => 'SN码'])
            ->addColumns([
                ['id', 'ID'],
                ['material_id', '所属物料', $material_map],
                ['sn', 'SN码'],
                ['project_id', '所属项目', $project_map],
                ['status', '状态', ['已使用', '可用', '维修中', '已报废']],
                ['remark', '备注'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('')
            ->addRightButtons(['edit', 'delete', 'scrap' => ['title' => '报废', 'icon' => 'fa fa-ban', 'class' => 'btn btn-xs btn-danger', 'href' => url('scrap', ['ids' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                if (!empty($data['sns'])) {
                    $sns = explode("\n", $data['sns']);
                    $sns = array_filter(array_map('trim', $sns));
                    $sns = array_unique($sns);
                    $remark = isset($data['remark']) ? $data['remark'] : '';
                    MaterialSnModel::addBatch($data['material_id'], $sns, $remark);
                } else {
                    throw new \Exception('请输入SN码');
                }
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '新增成功', 'url' => url('index')]);
            }
            $this->success('新增成功', url('index'));
        }

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_options = [];
        foreach ($material_list as $item) {
            $material_options[$item['id']] = $item['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增SN码')
            ->addFormItems([
                ['select', 'material_id', '所属物料', '必填', $material_options],
                ['textarea', 'sns', 'SN码', '每行一个SN码'],
                ['textarea', 'remark', '备注']
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
                MaterialSnModel::edit($data);
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

        $info = MaterialSnModel::getInfo($id);

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_options = [];
        foreach ($material_list as $item) {
            $material_options[$item['id']] = $item['name'];
        }

        $project_list = ProjectModel::getList(['status' => 1]);
        $project_options = ['' => '未分配'];
        foreach ($project_list as $item) {
            $project_options[$item['id']] = $item['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑SN码')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'material_id', '所属物料', '必填', $material_options],
                ['text', 'sn', 'SN码'],
                ['select', 'project_id', '所属项目', '', $project_options],
                ['textarea', 'remark', '备注']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                MaterialSnModel::deleteById($id);
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

    public function scrap($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                $snInfo = MaterialSnModel::getInfo($id);
                if (!$snInfo) {
                    throw new \Exception('SN码不存在');
                }
                MaterialSnModel::scrapSn($snInfo['material_id'], $snInfo['sn']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '报废成功', 'url' => url('index')]);
        }
        $this->success('报废成功', url('index'));
    }
}