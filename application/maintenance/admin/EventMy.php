<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\EventAction;

class EventMy extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $data_list = EventAction::getMyEvents(UID);

        $is_closed_list = EventAction::getIsClosedList();
        $is_canceled_list = EventAction::getIsCanceledList();
        $priority_list = EventAction::getPriorityList();

        foreach ($data_list as &$item) {
            $item['is_closed_text'] = isset($is_closed_list[$item['is_closed']]) ? $is_closed_list[$item['is_closed']] : '';
            $item['is_canceled_text'] = isset($is_canceled_list[$item['is_canceled']]) ? $is_canceled_list[$item['is_canceled']] : '';
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : '';
            $item['start_time_text'] = $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '';
            $item['can_close'] = !$item['is_closed'] && !$item['is_canceled'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('我的工单')
            ->setTableName('mt_event')
            ->setSearch(['title' => '标题', 'creator_name' => '发单人'])
            ->addColumns([
                ['id', 'ID'],
                ['title', '事件标题'],
                ['content', '事件描述'],
                ['creator_name', '发单人'],
                ['customer_name', '对接客户'],
                ['start_time_text', '开始时间'],
                ['priority_text', '优先级'],
                ['is_canceled_text', '状态'],
                ['is_closed_text', '结单状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Event/detail', ['id' => '__id__'])], 'close' => ['title' => '结单', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('Event/close', ['id' => '__id__']), 'condition' => 'can_close']])
            ->setRowList($data_list)
            ->fetch();
    }
}