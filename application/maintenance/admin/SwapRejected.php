<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\UserSwapModel;

class SwapRejected extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [['status', '=', 2]];
        $data_list = UserSwapModel::where($map)->order('create_time desc')->paginate();

        $status_list = UserSwapModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('已拒绝调班')
            ->setTableName('mt_user_swap')
            ->setSearch(['user_name' => '申请人'])
            ->addColumns([
                ['id', 'ID'],
                ['user_name', '申请人'],
                ['target_user_name', '调换对象'],
                ['swap_date', '调换日期'],
                ['reason', '理由'],
                ['status_text', '状态'],
                ['approver_name', '审批人'],
                ['approve_time', '审批时间']
            ])
            ->setRowList($data_list)
            ->fetch();
    }
}