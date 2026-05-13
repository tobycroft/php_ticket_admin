<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\maintenance\action\HandoverAction;

/**
 * 运维系统首页
 * @package app\maintenance\admin
 */
class Index extends Admin
{
    /**
     * 首页 Dashboard
     * @return mixed
     */
    public function index()
    {
        $my_unfinished_count = HandoverAction::getMyUnfinishedHandoverCount();
        $unassigned_count = HandoverAction::getUnassignedHandoverCount();
        $has_handover_today = HandoverAction::hasHandoverToday();

        $this->assign('my_unfinished_count', $my_unfinished_count);
        $this->assign('unassigned_count', $unassigned_count);
        $this->assign('has_handover_today', $has_handover_today);
        $this->assign('page_title', '运维管理系统');

        return $this->fetch('index/index');
    }
}