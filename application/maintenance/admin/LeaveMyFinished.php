<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\LeaveModel;

class LeaveMyFinished extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
            ['user_id', '=', UID],
            ['status', 'in', [1, 2]]
        ];
        $data_list = LeaveModel::where($map)->order('create_time desc')->paginate();

        $type_list = LeaveModel::getTypeList();
        $status_list = LeaveModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['type_text'] = isset($type_list[$item['leave_type']]) ? $type_list[$item['leave_type']] : '';
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('我已完成的请假')
            ->setTableName('mt_user_leave')
            ->addColumns([
                ['id', 'ID'],
                ['type_text', '类型'],
                ['start_date', '开始日期'],
                ['end_date', '结束日期'],
                ['reason', '理由'],
                ['status_text', '状态'],
                ['approver_name', '审批人'],
                ['create_time', '申请时间']
            ])
            ->setRowList($data_list)
            ->fetch();
    }
}