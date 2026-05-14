<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\ProjectModel;

class Project extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = ProjectModel::getList($map);

        return ZBuilder::make('table')
            ->setPageTitle('项目管理')
            ->setTableName('stor_project')
            ->setSearch(['name' => '项目名称'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '项目名称'],
                ['description', '项目描述'],
                ['remark', '项目备注'],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')
            ->addRightButtons(['edit', 'delete', 'detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'class' => 'btn btn-xs btn-info', 'href' => url('detail', ['id' => '__id__'])]]))
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                ProjectModel::add($data);
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

        return ZBuilder::make('form')
            ->setPageTitle('新增项目')
            ->addFormItems([
                ['text', 'name', '项目名称', '必填'],
                ['textarea', 'description', '项目描述'],
                ['textarea', 'remark', '项目备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
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
                ProjectModel::edit($data);
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

        $info = ProjectModel::getInfo($id);

        return ZBuilder::make('form')
            ->setPageTitle('编辑项目')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '项目名称', '必填'],
                ['textarea', 'description', '项目描述'],
                ['textarea', 'remark', '项目备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = ProjectModel::getInfo($id);

        $snList = MaterialSnModel::where('project_id', $id)->select();
        
        $materialMap = [];
        $materials = [];
        
        foreach ($snList as $snItem) {
            $materialId = $snItem['material_id'];
            if (!isset($materialMap[$materialId])) {
                $material = MaterialModel::getInfo($materialId);
                $materialMap[$materialId] = $material;
                $materials[$materialId] = [
                    'material' => $material,
                    'sns' => []
                ];
            }
            $materials[$materialId]['sns'][] = $snItem['sn'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('项目详情')
            ->addFormItems([
                ['static', 'name', '项目名称'],
                ['static', 'description', '项目描述'],
                ['static', 'remark', '项目备注'],
                ['static', 'create_time', '创建时间']
            ])
            ->setExtraHtml($this->renderProjectMaterials($materials))
            ->setFormData($info)
            ->fetch();
    }

    private function renderProjectMaterials($materials)
    {
        if (empty($materials)) {
            return '<div class="block"><div class="block-header bg-gray-lighter"><h3 class="block-title"><i class="fa fa-cubes mr-5"></i>使用物料</h3></div><div class="block-content text-center py-20"><div class="alert alert-info">该项目暂无使用物料</div></div></div>';
        }
        
        $html = '<div class="block"><div class="block-header bg-gray-lighter"><h3 class="block-title"><i class="fa fa-cubes mr-5"></i>使用物料</h3></div><div class="block-content">';
        
        foreach ($materials as $item) {
            $material = $item['material'];
            $sns = $item['sns'];
            
            $html .= '<div class="panel panel-default mb-10"><div class="panel-heading"><h4 class="panel-title">' . $material['name'] . '</h4></div><div class="panel-body"><div class="row"><div class="col-md-6"><p><strong>所属分类：</strong>' . $material['category_id'] . '</p><p><strong>存放位置：</strong>' . ($material['location'] ?: '-') . '</p><p><strong>单位：</strong>' . ($material['unit'] ?: '-') . '</p></div><div class="col-md-6"><p><strong>SN码列表：</strong></p><div class="tag-list">';
            
            foreach ($sns as $sn) {
                $html .= '<span class="label label-default mr-2">' . $sn . '</span>';
            }
            
            $html .= '</div></div></div></div>';
        }
        
        $html .= '</div></div>';
        return $html;
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                ProjectModel::deleteById($id);
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

    public function enable($ids = [])
    {
        return $this->setStatus('enable');
    }

    public function disable($ids = [])
    {
        return $this->setStatus('disable');
    }

    public function setStatus($type = '', $record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        try {
            ProjectModel::setStatus($type, $ids);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '操作成功']);
        }
        $this->success('操作成功');
    }
}