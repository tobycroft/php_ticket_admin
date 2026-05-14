<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\maintenance\action\HandoverAction;
use app\maintenance\action\EventAction;

class DashboardInfo extends Admin
{
    public function index()
    {
        $my_unfinished_count = HandoverAction::getMyUnfinishedHandoverCount();
        $unassigned_count = HandoverAction::getUnassignedHandoverCount();
        $has_handover_today = HandoverAction::hasHandoverToday();
        
        $my_events = EventAction::getMyEvents(UID);
        $my_event_count = $my_events->total();

        $all_unclosed_count = EventAction::getUnclosedEventCount();

        $this->assign('my_unfinished_count', $my_unfinished_count);
        $this->assign('unassigned_count', $unassigned_count);
        $this->assign('has_handover_today', $has_handover_today);
        $this->assign('my_event_count', $my_event_count);
        $this->assign('all_unclosed_count', $all_unclosed_count);
        $this->assign('page_title', '信息面板');

        return $this->fetch('dashboard_info/index');
    }
}