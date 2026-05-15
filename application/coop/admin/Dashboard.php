<?php

namespace app\coop\admin;

use app\admin\controller\Admin;
use app\coop\model\CooperateModel;

class Dashboard extends Admin
{
    public function index()
    {
        $cooperate_count = CooperateModel::where('status', 1)->count();
        $pending_count = CooperateModel::where('status', 1)->where('is_canceled', 0)->count();
        $canceled_count = CooperateModel::where('status', 1)->where('is_canceled', 1)->count();
        $my_cooperate_count = CooperateModel::where('status', 1)->where('receiver_id', UID)->count();

        $this->assign('cooperate_count', $cooperate_count);
        $this->assign('pending_count', $pending_count);
        $this->assign('canceled_count', $canceled_count);
        $this->assign('my_cooperate_count', $my_cooperate_count);
        
        $this->assign('page_title', '协办单仪表盘');
        
        return $this->fetch();
    }
}