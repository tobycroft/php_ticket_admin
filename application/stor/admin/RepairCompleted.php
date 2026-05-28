<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\action\RepairAction;
use app\stor\model\MaterialModel;
use app\stor\model\RepairModel;

class RepairCompleted extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = RepairModel::where('status', 1)->order('id DESC')->select();

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('维修完成')
            ->setTableName('stor_repair')
            ->setSearch(['code' => '维修单号'])
            ->addColumns([
                ['id', 'ID'],
                ['code', '维修单号'],
                ['material_id', '物料', $material_map],
                ['sn', 'SN码'],
                ['problem', '故障描述'],
                ['repair_result', '维修结果'],
                ['create_time', '创建时间'],
                ['update_time', '完成时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButton('custom', ['title' => '导出Excel', 'icon' => 'fa fa-download', 'class' => 'btn btn-success', 'href' => url('export')])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'class' => 'btn btn-xs btn-info', 'href' => url('detail', ['id' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }

    /**
     * 导出Excel
     */
    public function export()
    {
        $data_list = RepairModel::where('status', 1)->order('id DESC')->select();

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        // 准备Excel数据
        $header = ['ID', '维修单号', '物料', 'SN码', '故障描述', '维修结果', '创建时间', '完成时间'];
        $data = [];
        foreach ($data_list as $item) {
            $data[] = [
                $item['id'],
                $item['code'],
                $material_map[$item['material_id']] ?? '未知',
                $item['sn'],
                $item['problem'],
                $item['repair_result'],
                $item['create_time'],
                $item['update_time']
            ];
        }

        // 生成Excel文件
        $filename = '维修完成工单_' . date('YmdHis') . '.csv';
        
        // 设置响应头
        header('Content-Type: application/vnd.ms-excel;charset=utf-8');
        header('Content-Disposition: attachment;filename=' . $filename);
        header('Cache-Control: max-age=0');
        
        // 输出BOM头，解决中文乱码
        echo "\xEF\xBB\xBF";
        
        // 输出表头
        echo implode(',', $header) . "\n";
        
        // 输出数据
        foreach ($data as $row) {
            // 处理包含逗号、引号、换行符的字段
            $processed_row = array_map(function($item) {
                $item = str_replace('"', '""', $item);
                if (strpos($item, ',') !== false || strpos($item, '"') !== false || strpos($item, "\n") !== false) {
                    return '"' . $item . '"';
                }
                return $item;
            }, $row);
            echo implode(',', $processed_row) . "\n";
        }
        exit;
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = RepairModel::getInfo($id);

        $material = RepairAction::getMaterialInfo($info['material_id']);
        $info['material_name'] = $material['name'];
        $info['material_location'] = $material['location'];
        $info['material_purchase_date'] = $material['purchase_date'];
        $info['material_seller'] = $material['seller'];
        $info['material_warranty_end'] = $material['warranty_end'];
        $info['material_remark'] = $material['remark'];

        return ZBuilder::make('form')
            ->setPageTitle('维修单详情')
            ->addFormItems([
                ['static', 'code', '维修单号'],
                ['static', 'material_name', '物料'],
                ['static', 'sn', 'SN码'],
                ['static', 'problem', '故障描述'],
                ['static', 'repair_result', '维修结果'],
                ['static', 'create_time', '创建时间'],
                ['static', 'update_time', '完成时间']
            ])
            ->setExtraHtml('<div class="block"><div class="block-header bg-info"><h3 class="block-title"><i class="fa fa-info-circle mr-5"></i>物料信息</h3></div><div class="block-content"><table class="table table-bordered"><tr><th>存放位置</th><td>' . ($material['location'] ?: '-') . '</td></tr><tr><th>购入日期</th><td>' . ($material['purchase_date'] ?: '-') . '</td></tr><tr><th>销售方</th><td>' . ($material['seller'] ?: '-') . '</td></tr><tr><th>保修期截止</th><td>' . ($material['warranty_end'] ?: '-') . '</td></tr><tr><th>备注</th><td>' . ($material['remark'] ?: '-') . '</td></tr></table></div></div>')
            ->setFormData($info)
            ->fetch();
    }
}