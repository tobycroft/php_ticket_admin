<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\EventAction;

class EventCanceled extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $map[] = ['is_canceled', '=', 1];

        $data_list = EventAction::getList($map);

        $is_closed_list = EventAction::getIsClosedList();
        $is_canceled_list = EventAction::getIsCanceledList();

        foreach ($data_list as &$item) {
            $item['is_closed_text'] = isset($is_closed_list[$item['is_closed']]) ? $is_closed_list[$item['is_closed']] : '';
            $item['is_canceled_text'] = isset($is_canceled_list[$item['is_canceled']]) ? $is_canceled_list[$item['is_canceled']] : '';
            $item['start_time_text'] = $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '';
            $item['end_time_text'] = $item['end_time'] ? date('Y-m-d H:i:s', $item['end_time']) : '';
            $item['can_active'] = $item['sender_id'] == UID;
        }

        return ZBuilder::make('table')
            ->setPageTitle('作废工单')
            ->setTableName('mt_event')
            ->setSearch(['title' => '标题', 'sender_name' => '发单人', 'customer_name' => '客户'])
            ->addColumns([
                ['id', 'ID'],
                ['title', '事件标题'],
                ['sender_name', '发单人'],
                ['receiver_name', '接单人'],
                ['customer_name', '对接客户'],
                ['start_time_text', '开始时间'],
                ['end_time_text', '结束时间'],
                ['is_canceled_text', '状态'],
                ['is_closed_text', '结单状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-fw fa-eye', 'href' => url('Event/detail', ['id' => '__id__'])], 'active' => ['title' => '激活', 'icon' => 'fa fa-fw fa-refresh', 'class' => 'btn btn-xs btn-info', 'href' => url('Event/active', ['id' => '__id__']), 'condition' => 'can_active']])
            ->setRowList($data_list)
            ->fetch();
    }
}