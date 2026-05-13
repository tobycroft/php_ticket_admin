<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\CategoryModel;

class Category extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = CategoryModel::getTreeList();

        return ZBuilder::make('table')
            ->setPageTitle('物料分类')
            ->setTableName('stor_category')
            ->setSearch(['name' => '分类名称'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '分类名称'],
                ['code', '分类编码'],
                ['sort', '排序'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')
            ->addRightButtons('edit,delete')
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                CategoryModel::add($data);
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

        $category_list = CategoryModel::getList(['status' => 1]);
        $category_options = [0 => '顶级分类'];
        foreach ($category_list as $item) {
            $category_options[$item['id']] = $item['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增分类')
            ->addFormItems([
                ['select', 'pid', '上级分类', '', $category_options],
                ['text', 'name', '分类名称', '必填'],
                ['text', 'code', '分类编码'],
                ['text', 'sort', '排序', '', 0],
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
                CategoryModel::edit($data);
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

        $info = CategoryModel::getInfo($id);
        $category_list = CategoryModel::getList(['status' => 1]);
        $category_options = [0 => '顶级分类'];
        foreach ($category_list as $item) {
            if ($item['id'] != $id) {
                $category_options[$item['id']] = $item['name'];
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑分类')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'pid', '上级分类', '', $category_options],
                ['text', 'name', '分类名称', '必填'],
                ['text', 'code', '分类编码'],
                ['text', 'sort', '排序'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                CategoryModel::deleteById($id);
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
        return $this->setStatus('enable', $ids);
    }

    public function disable($ids = [])
    {
        return $this->setStatus('disable', $ids);
    }

    public function setStatus($type, $ids)
    {
        $ids = (array)$ids;
        try {
            CategoryModel::setStatus($type, $ids);
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