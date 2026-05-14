<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\CategoryModel;

class CategoryScrap extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = CategoryModel::getScrapList();

        return ZBuilder::make('table')
            ->setPageTitle('作废分类列表')
            ->setTableName('stor_category')
            ->setSearch(['name' => '分类名称'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '分类名称'],
                ['code', '分类编码'],
                ['sort', '排序'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('delete')
            ->addRightButtons('restore,delete')
            ->setRowList($data_list)
            ->fetch();
    }

    public function restore($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                CategoryModel::restore($id);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '恢复成功']);
        }
        $this->success('恢复成功');
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                CategoryModel::where('id', $id)->delete();
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