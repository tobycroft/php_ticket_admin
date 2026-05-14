<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\MaterialModel;

class MaterialScrap extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = MaterialModel::getScrapList();

        $category_list = \app\stor\model\CategoryModel::getList(['status' => 1]);
        $category_map = [];
        foreach ($category_list as $item) {
            $category_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('作废物料列表')
            ->setTableName('stor_material')
            ->setSearch(['name' => '物料名称', 'seller' => '销售方'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '物料名称'],
                ['seller', '销售方'],
                ['purchase_date', '购入日期', 'date'],
                ['warranty_end', '保修期截止', 'date'],
                ['category_id', '所属分类', $category_map],
                ['unit', '单位'],
                ['remark', '备注'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('')
            ->addRightButtons(['restore' => ['title' => '恢复', 'icon' => 'fa fa-recycle', 'class' => 'btn btn-xs btn-success', 'href' => url('restore', ['id' => '__id__'])], 'delete' => ['title' => '彻底删除', 'icon' => 'fa fa-remove', 'class' => 'btn btn-xs btn-danger', 'href' => url('delete', ['id' => '__id__'])]])))
            ->setRowList($data_list)
            ->fetch();
    }

    public function restore($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            MaterialModel::restore($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '恢复成功', 'url' => url('index')]);
        }
        $this->success('恢复成功', url('index'));
    }

    public function delete($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            MaterialModel::deleteById($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '删除成功', 'url' => url('index')]);
        }
        $this->success('删除成功', url('index'));
    }
}