<?php

namespace app\coop\admin;

use app\admin\controller\Admin;
use app\coop\model\CooperateModel;
use app\user\model\User;

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

    public function statistics()
    {
        if (!is_admin()) {
            $this->error('无权限访问');
        }

        $total = CooperateModel::where('status', 1)->count();
        $pending = CooperateModel::where('status', 1)->where('is_canceled', 0)->where('is_closed', 0)->count();
        $closed = CooperateModel::where('status', 1)->where('is_closed', 1)->count();
        $canceled = CooperateModel::where('status', 1)->where('is_canceled', 1)->count();

        $creator_stats = CooperateModel::where('status', 1)
            ->field('creator_id, creator_name, COUNT(*) as count')
            ->group('creator_id, creator_name')
            ->order('count DESC')
            ->limit(10)
            ->select();

        $receiver_stats = CooperateModel::where('status', 1)
            ->field('receiver_id, receiver_name, COUNT(*) as count')
            ->group('receiver_id, receiver_name')
            ->order('count DESC')
            ->limit(10)
            ->select();

        $priority_stats = CooperateModel::where('status', 1)
            ->field('priority, COUNT(*) as count')
            ->group('priority')
            ->select();

        $priority_list = [1 => '低', 2 => '中', 3 => '高'];
        foreach ($priority_stats as &$item) {
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : $item['priority'];
        }

        $this->assign('total', $total);
        $this->assign('pending', $pending);
        $this->assign('closed', $closed);
        $this->assign('canceled', $canceled);
        $this->assign('creator_stats', $creator_stats);
        $this->assign('receiver_stats', $receiver_stats);
        $this->assign('priority_stats', $priority_stats);

        $this->assign('page_title', '协办单统计');

        return $this->fetch('dashboard/statistics');
    }
}