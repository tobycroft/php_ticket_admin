<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\HandoverAction;
use app\user\model\User as UserModel;

class DashboardStats extends Admin
{
    public function index()
    {
        $current_month = date('Y-m');
        $last_month = date('Y-m', strtotime('-1 month'));

        $current_count = HandoverAction::getHandoverStatsByMonth($current_month);
        $last_count = HandoverAction::getHandoverStatsByMonth($last_month);

        $users = UserModel::where('status', 1)->select();
        
        $user_stats = [];
        $users_without_handover = [];
        
        foreach ($users as $user) {
            $current = HandoverAction::getUserHandoverStats($user['id'], $current_month);
            $last = HandoverAction::getUserHandoverStats($user['id'], $last_month);
            
            $user_stats[] = [
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'current_month_count' => $current,
                'last_month_count' => $last,
                'change' => $last > 0 ? (($current - $last) / $last * 100) : ($current > 0 ? 100 : 0),
            ];
            
            if ($current == 0) {
                $users_without_handover[] = $user['nickname'];
            }
        }

        $change_rate = $last_count > 0 ? (($current_count - $last_count) / $last_count * 100) : ($current_count > 0 ? 100 : 0);

        return ZBuilder::make('table')
            ->setPageTitle('交接统计')
            ->setPageTips('运维交接统计分析', 'info')
            ->addColumns([
                ['id', 'ID'],
                ['nickname', '用户'],
                ['current_month_count', '本月交接数'],
                ['last_month_count', '上月交接数'],
                ['change', '环比变化', 'callback', function($val) {
                    $class = $val >= 0 ? 'text-green' : 'text-red';
                    $sign = $val >= 0 ? '+' : '';
                    return "<span class='$class'>$sign" . number_format($val, 1) . '%</span>';
                }],
            ])
            ->setRowList($user_stats)
            ->setExtraHtml('<div class="row"><div class="col-md-6"><div class="panel panel-primary"><div class="panel-heading"><h3 class="panel-title">本月交接总数</h3></div><div class="panel-body"><div class="huge">' . $current_count . '</div><div>较上月 <span class="' . ($change_rate >= 0 ? 'text-green' : 'text-red') . '">' . ($change_rate >= 0 ? '+' : '') . number_format($change_rate, 1) . '%</span></div></div></div></div><div class="col-md-6"><div class="panel panel-warning"><div class="panel-heading"><h3 class="panel-title">本月未交接人员</h3></div><div class="panel-body"><div>' . (empty($users_without_handover) ? '所有人都已完成交接' : implode(', ', $users_without_handover)) . '</div></div></div></div>')
            ->fetch();
    }
}