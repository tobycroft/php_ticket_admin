<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\EventAction;

class EventFinished extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $map[] = ['is_closed', '=', 1];
        $map[] = ['is_canceled', '=', 0];

        $data_list = EventAction::getList($map);

        $is_closed_list = EventAction::getIsClosedList();
        $is_canceled_list = EventAction::getIsCanceledList();

        foreach ($data_list as &$item) {
            $item['is_closed_text'] = isset($is_closed_list[$item['is_closed']]) ? $is_closed_list[$item['is_closed']] : '';
            $item['is_canceled_text'] = isset($is_canceled_list[$item['is_canceled']]) ? $is_canceled_list[$item['is_canceled']] : '';
            $item['start_time_text'] = $item['start_time'] ? date('Y-m-d H:i:s', $item['start_time']) : '';
            $item['end_time_text'] = $item['end_time'] ? date('Y-m-d H:i:s', $item['end_time']) : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('已完结工单')
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
                ['closer_name', '结单人'],
                ['is_closed_text', '结单状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['href' => url('Event/detail', ['id' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }
}