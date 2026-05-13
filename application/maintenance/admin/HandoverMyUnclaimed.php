<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\HandoverAction;

class HandoverMyUnclaimed extends Admin
{
    public function index()
    {
        $handovers = HandoverAction::getMyUnclaimedHandovers();

        $status_list = HandoverAction::getStatusList();
        $is_forced_list = HandoverAction::getIsForcedList();

        foreach ($handovers as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['is_forced_text'] = isset($is_forced_list[$item['is_forced']]) ? $is_forced_list[$item['is_forced']] : '';
            $item['can_receive'] = $item['status'] == 0;
        }

        return ZBuilder::make('table')
            ->setPageTitle('我的未交接')
            ->setPageTips('指派给我的未接收交接记录', 'info')
            ->addColumns([
                ['id', 'ID'],
                ['title', '交接标题'],
                ['creator_name', '创建人'],
                ['status_text', '状态'],
                ['is_forced_text', '交接类型'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Handover/detail', ['id' => '__id__'])], 'receive' => ['title' => '接收交接', 'icon' => 'fa fa-handshake-o', 'class' => 'btn btn-xs btn-success', 'href' => url('Handover/receive', ['id' => '__id__']), 'condition' => 'can_receive']])
            ->setRowList($handovers)
            ->fetch();
    }
}