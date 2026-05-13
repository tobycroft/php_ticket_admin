<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\HandoverAction;

class HandoverMySent extends Admin
{
    public function index()
    {
        $handovers = HandoverAction::getMySentHandovers();

        $status_list = HandoverAction::getStatusList();
        $is_forced_list = HandoverAction::getIsForcedList();

        foreach ($handovers as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['is_forced_text'] = isset($is_forced_list[$item['is_forced']]) ? $is_forced_list[$item['is_forced']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('我的交接')
            ->setPageTips('我创建的交接记录', 'info')
            ->addTopButtons(['add' => ['title' => '新增交接', 'icon' => 'fa fa-plus', 'href' => url('Handover/add')]])
            ->addColumns([
                ['id', 'ID'],
                ['title', '交接标题'],
                ['default_receiver_name', '默认接收人'],
                ['actual_receiver_name', '实际接收人'],
                ['status_text', '状态'],
                ['is_forced_text', '交接类型'],
                ['create_time', '创建时间'],
                ['receive_time', '接收时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Handover/detail', ['id' => '__id__'])]])
            ->setRowList($handovers)
            ->fetch();
    }
}