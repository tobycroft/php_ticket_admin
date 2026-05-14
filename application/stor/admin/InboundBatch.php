<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\CategoryModel;
use app\stor\model\InboundBatchRecordModel;
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

                    $material = MaterialModel::getInfo($data['material_id']);
                    InboundBatchRecordModel::add([
                        'material_id' => $data['material_id'],
                        'material_name' => $material['name'],
                        'sn_count' => count($sns),
                        'sn_list' => implode(',', $sns),
                        'remark' => $remark,
                        'create_user' => UID
                    ]);
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

    public function record()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = InboundBatchRecordModel::getList();

        return ZBuilder::make('table')
            ->setPageTitle('批量入库记录')
            ->setTableName('stor_inbound_batch_record')
            ->setSearch(['material_name' => '物料名称'])
            ->addColumns([
                ['id', 'ID'],
                ['material_name', '导入物料'],
                ['sn_count', '导入数量'],
                ['remark', '备注'],
                ['create_time', '导入时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('')
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'class' => 'btn btn-xs btn-info', 'href' => url('detail', ['id' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = InboundBatchRecordModel::getInfo($id);
        if (!$info) {
            $this->error('记录不存在');
        }

        $snArray = explode(',', $info['sn_list']);
        $snTableHtml = '<table class="table table-striped table-bordered table-hover"><thead><tr><th>序号</th><th>SN码</th></tr></thead><tbody>';
        foreach ($snArray as $index => $sn) {
            $snTableHtml .= '<tr><td>' . ($index + 1) . '</td><td>' . htmlspecialchars($sn) . '</td></tr>';
        }
        $snTableHtml .= '</tbody></table>';

        $html = '<!DOCTYPE html>
<html>
<head>
    <title>批量入库记录详情</title>
    <link rel="stylesheet" href="/static/admin/css/style.css">
    <link rel="stylesheet" href="/static/admin/plugins/bootstrap/css/bootstrap.min.css">
</head>
<body>
<div class="wrapper wrapper-content animated fadeInRight">
    <div class="row">
        <div class="col-sm-12">
            <div class="ibox float-e-margins">
                <div class="ibox-title">
                    <h5>批量入库记录详情</h5>
                </div>
                <div class="ibox-content">
                    <form class="form-horizontal">
                        <div class="form-group">
                            <label class="col-sm-2 control-label">导入物料</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">' . htmlspecialchars($info['material_name']) . '</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">导入数量</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">' . htmlspecialchars($info['sn_count']) . '</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">导入SN码</label>
                            <div class="col-sm-10">' . $snTableHtml . '</div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">备注</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">' . htmlspecialchars($info['remark'] ?? '') . '</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label">导入时间</label>
                            <div class="col-sm-10">
                                <p class="form-control-static">' . htmlspecialchars($info['create_time']) . '</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>';

        return $html;
    }
}