<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\HandoverAction;

class HandoverMyReceived extends Admin
{
    public function index()
    {
        $handovers = HandoverAction::getMyReceivedHandovers();

        $status_list = HandoverAction::getStatusList();
        $is_forced_list = HandoverAction::getIsForcedList();

        foreach ($handovers as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['is_forced_text'] = isset($is_forced_list[$item['is_forced']]) ? $is_forced_list[$item['is_forced']] : '';
            $item['can_complete'] = $item['status'] == 1;
        }

        return ZBuilder::make('table')
            ->setPageTitle('我接收的交接')
            ->setPageTips('我接收的交接记录', 'info')
            ->addColumns([
                ['id', 'ID'],
                ['title', '交接标题'],
                ['creator_name', '创建人'],
                ['status_text', '状态'],
                ['is_forced_text', '交接类型'],
                ['create_time', '创建时间'],
                ['receive_time', '接收时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Handover/detail', ['id' => '__id__'])], 'complete' => ['title' => '完成交接', 'icon' => 'fa fa-check', 'class' => 'btn btn-xs btn-primary', 'href' => url('Handover/complete', ['id' => '__id__']), 'condition' => 'can_complete']])
            ->setRowList($handovers)
            ->fetch();
    }
}