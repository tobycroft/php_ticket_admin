<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\CategoryModel;
use app\stor\model\MaterialModel;

class Material extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = MaterialModel::getList($map);

        $category_list = CategoryModel::getList(['status' => 1]);
        $category_map = [];
        foreach ($category_list as $item) {
            $category_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('物料管理')
            ->setTableName('stor_material')
            ->setSearch(['name' => '物料名称', 'seller' => '销售方'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '物料名称'],
                ['seller', '销售方'],
                ['purchase_date', '购入日期', 'datetime'],
                ['warranty_end', '保修期截止', 'datetime'],
                ['category_id', '所属分类', $category_map],
                ['unit', '单位'],
                ['safe_stock', '安全库存'],
                ['max_stock', '最大库存'],
                ['location', '存放位置'],
                ['remark', '备注'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable')
            ->addRightButtons(['edit', 'scrap' => ['title' => '作废', 'icon' => 'fa fa-trash', 'class' => 'btn btn-xs btn-danger', 'href' => url('scrap', ['id' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                MaterialModel::add($data);
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
        $category_options = [];
        foreach ($category_list as $item) {
            $category_options[$item['id']] = $item['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增物料')
            ->addFormItems([
                ['select', 'category_id', '所属分类', '必填', $category_options],
                ['text', 'name', '物料名称', '必填'],
                ['text', 'seller', '销售方'],
                ['datetime', 'purchase_date', '购入日期'],
                ['datetime', 'warranty_end', '保修期截止'],
                ['text', 'unit', '单位', '', '个'],
                ['text', 'safe_stock', '安全库存', '', 0],
                ['text', 'max_stock', '最大库存', '', 0],
                ['text', 'location', '存放位置'],
                ['textarea', 'remark', '备注'],
                ['image', 'image', '物料图片'],
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
                MaterialModel::edit($data);
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

        $info = MaterialModel::getInfo($id);
        $category_list = CategoryModel::getList(['status' => 1]);
        $category_options = [];
        foreach ($category_list as $item) {
            $category_options[$item['id']] = $item['name'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑物料')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'category_id', '所属分类', '必填', $category_options],
                ['text', 'name', '物料名称', '必填'],
                ['text', 'seller', '销售方'],
                ['date', 'purchase_date', '购入日期'],
                ['date', 'warranty_end', '保修期截止'],
                ['text', 'unit', '单位'],
                ['text', 'safe_stock', '安全库存'],
                ['text', 'max_stock', '最大库存'],
                ['text', 'location', '存放位置'],
                ['textarea', 'remark', '备注'],
                ['image', 'image', '物料图片'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function scrap($id = null)
    {
        if ($id === null) {
            $id = $this->request->param('id');
        }

        try {
            MaterialModel::scrap($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '作废成功']);
        }
        $this->success('作废成功');
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
            MaterialModel::setStatus($type, $ids);
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