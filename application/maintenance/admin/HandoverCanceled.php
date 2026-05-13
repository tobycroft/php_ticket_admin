<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\HandoverAction;

class HandoverCanceled extends Admin
{
    public function index()
    {
        $handovers = HandoverAction::getCanceledHandovers();

        $status_list = HandoverAction::getStatusList();
        $is_forced_list = HandoverAction::getIsForcedList();

        foreach ($handovers as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['is_forced_text'] = isset($is_forced_list[$item['is_forced']]) ? $is_forced_list[$item['is_forced']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('已作废交接')
            ->setPageTips('已作废的交接记录，仅可查看详情', 'info')
            ->addColumns([
                ['id', 'ID'],
                ['title', '交接标题'],
                ['creator_name', '创建人'],
                ['default_receiver_name', '默认接收人'],
                ['actual_receiver_name', '实际接收人'],
                ['status_text', '状态'],
                ['is_forced_text', '交接类型'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Handover/detail', ['id' => '__id__'])]])
            ->setRowList($handovers)
            ->fetch();
    }
}