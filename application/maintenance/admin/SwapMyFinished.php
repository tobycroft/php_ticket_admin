<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\UserSwapModel;

class SwapMyFinished extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
            ['user_id', '=', UID],
            ['status', '=', 1]
        ];
        $data_list = UserSwapModel::where($map)->order('create_time desc')->paginate();

        $status_list = UserSwapModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('我已批准的调班')
            ->setTableName('mt_user_swap')
            ->addColumns([
                ['id', 'ID'],
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