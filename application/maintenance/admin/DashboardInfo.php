<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\HandoverAction;
use app\maintenance\action\EventAction;

class DashboardInfo extends Admin
{
    public function index()
    {
        $unclaimed_handovers = HandoverAction::getUnclaimedHandovers();
        
        $my_events = EventAction::getMyEvents(UID);
        
        $unclaimed_count = count($unclaimed_handovers);
        $my_event_count = $my_events->total();

        foreach ($unclaimed_handovers as &$handover) {
            $handover['is_for_me'] = $handover['default_receiver_id'] == UID;
            $handover['can_force_receive'] = true;
        }

        return ZBuilder::make('table')
            ->setPageTitle('信息面板')
            ->setPageTips('欢迎使用运维系统信息面板', 'info')
            ->addColumns([
                ['id', 'ID'],
                ['title', '交接标题'],
                ['creator_name', '创建人'],
                ['default_receiver_name', '默认接收人'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['receive' => ['title' => '接收交接', 'icon' => 'fa fa-handshake-o', 'class' => 'btn btn-xs btn-success', 'href' => url('Handover/receive', ['id' => '__id__'])], 'detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Handover/detail', ['id' => '__id__'])]])
            ->setRowList($unclaimed_handovers)
            ->setExtraHtml('<div class="row"><div class="col-md-6"><div class="panel panel-primary"><div class="panel-heading"><h3 class="panel-title">待处理交接</h3></div><div class="panel-body"><div class="huge">' . $unclaimed_count . '</div><div>等待接收的交接数量</div></div></div></div><div class="col-md-6"><div class="panel panel-info"><div class="panel-heading"><h3 class="panel-title">我的工单</h3></div><div class="panel-body"><div class="huge">' . $my_event_count . '</div><div>正在处理的工单数量</div></div></div></div>')
            ->fetch();
    }
}