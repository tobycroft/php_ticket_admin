<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\CategoryModel;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;

class InboundBatch extends Admin
{
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
                return json(['code' => 1, 'msg' => '批量入库成功', 'url' => url('Inbound/index')]);
            }
            $this->success('批量入库成功', url('Inbound/index'));
        }

        $material_list = MaterialModel::getList(['status' => 1]);
        $category_list = CategoryModel::getList(['status' => 1]);
        $category_map = [];
        foreach ($category_list as $item) {
            $category_map[$item['id']] = $item['name'];
        }
        $material_options = [];
        foreach ($material_list as $item) {
            $category_name = isset($category_map[$item['category_id']]) ? $category_map[$item['category_id']] : '未分类';
            $seller = !empty($item['seller']) ? $item['seller'] : '无';
            $material_options[$item['id']] = $item['name'] . ' (' . $seller . ' - ' . $category_name . ')';
        }

        return ZBuilder::make('form')
            ->setPageTitle('批量入库')
            ->addFormItems([
                ['select', 'material_id', '所属物料', '必填', $material_options],
                ['textarea', 'sns', 'SN码', '每行一个SN码'],
                ['textarea', 'remark', '备注']
            ])
            ->fetch();
    }
}