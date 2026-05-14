<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;

class MaterialSnScrap extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = MaterialSnModel::getScrapList();

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('作废SN码列表')
            ->setTableName('stor_material_sn')
            ->setSearch(['sn' => 'SN码'])
            ->addColumns([
                ['id', 'ID'],
                ['material_id', '所属物料', $material_map],
                ['sn', 'SN码'],
                ['remark', '备注'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('')
            ->addRightButtons(['restore' => ['title' => '恢复', 'icon' => 'fa fa-recycle', 'class' => 'btn btn-xs btn-success', 'href' => url('restore', ['id' => '__id__'])], 'delete' => ['title' => '彻底删除', 'icon' => 'fa fa-remove', 'class' => 'btn btn-xs btn-danger', 'href' => url('delete', ['id' => '__id__'])]])
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
            MaterialSnModel::restore($id);
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
            MaterialSnModel::deleteById($id);
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