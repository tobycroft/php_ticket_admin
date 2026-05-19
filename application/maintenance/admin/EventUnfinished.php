<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\EventAction;

class EventUnfinished extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $map[] = ['is_closed', '=', 0];
        $map[] = ['is_canceled', '=', 0];

        $data_list = EventAction::getList($map);

        $is_closed_list = EventAction::getIsClosedList();
        $is_canceled_list = EventAction::getIsCanceledList();
        $is_no_feedback_list = EventAction::getIsNoFeedbackList();
        $priority_list = EventAction::getPriorityList();

        foreach ($data_list as &$item) {
            if ($item['is_no_feedback'] == 1) {
                $item['is_closed_text'] = '<span style="color:orange; font-weight:bold;"><i class="fa fa-check-square-o"></i> 已解决，客户无反馈</span>';
            } elseif ($item['is_closed'] == 1) {
                $item['is_closed_text'] = '<span style="color:green; font-weight:bold;"><i class="fa fa-check-circle"></i> 已结单</span>';
            } else {
                $item['is_closed_text'] = '<span style="color:red; font-weight:bold;"><i class="fa fa-exclamation-circle"></i> 未结单</span>';
            }
            $item['is_canceled_text'] = isset($is_canceled_list[$item['is_canceled']]) ? $is_canceled_list[$item['is_canceled']] : '';
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : '';
            $item['start_time_text'] = $item['start_time'] ? $item['start_time'] : '';
            $item['end_time_text'] = $item['end_time'] ? $item['end_time'] : '';
            $item['can_close'] = ($item['receiver_id'] == UID || $item['creator_id'] == UID) && !$item['is_closed'] && !$item['is_canceled'] && !$item['is_no_feedback'];
            $item['can_cancel'] = $item['creator_id'] == UID && !$item['is_closed'] && !$item['is_canceled'] && !$item['is_no_feedback'];
            $item['can_mark_no_feedback'] = !$item['is_closed'] && !$item['is_canceled'] && !$item['is_no_feedback'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('未完结工单')
            ->setTableName('mt_event')
            ->setSearch(['title' => '标题', 'creator_name' => '发单人', 'customer_name' => '客户'])
            ->addColumns([
                ['id', 'ID'],
                ['title', '事件标题'],
                ['creator_name', '发单人'],
                ['receiver_name', '接单人'],
                ['customer_name', '对接客户'],
                ['start_time_text', '开始时间'],
                ['priority_text', '优先级'],
                ['is_closed_text', '结单状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Event/detail', ['id' => '__id__'])], 'close' => ['title' => '结单', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('Event/close', ['id' => '__id__']), 'condition' => 'can_close'], 'cancel' => ['title' => '作废', 'icon' => 'fa fa-trash', 'class' => 'btn btn-xs btn-danger', 'href' => url('Event/cancel', ['id' => '__id__']), 'condition' => 'can_cancel'], 'mark_no_feedback' => ['title' => '标记为客户无反馈', 'icon' => 'fa fa-check-square-o', 'class' => 'btn btn-xs btn-warning', 'href' => url('Event/markNoFeedback', ['id' => '__id__']), 'condition' => 'can_mark_no_feedback']])
            ->setRowList($data_list)
            ->fetch();
    }
}