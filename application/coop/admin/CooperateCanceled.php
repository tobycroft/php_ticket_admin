<?php

namespace app\coop\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\coop\action\CooperateAction;

class CooperateCanceled extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
            ['creator_id', '=', UID],
            ['is_canceled', '=', 1],
        ];

        $data_list = CooperateAction::getList($map);

        $priority_list = CooperateAction::getPriorityList();

        foreach ($data_list as &$item) {
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('我的作废协办单')
            ->setTableName('mt_cooperate')
            ->setSearch(['title' => '标题', 'receiver_name' => '接收人'])
            ->addColumns([
                ['id', 'ID'],
                ['create_time', '创建时间'],
                ['receiver_name', '接收人'],
                ['title', '标题'],
                ['priority_text', '优先级'],
                ['cancel_time', '作废时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('detail', ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('detail', ['id' => '__id__'])])
            ->setRowList($data_list)
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        $info = CooperateAction::getInfo($id);
        $notes = CooperateAction::getNotes($id);

        if (!$info) {
            $this->error('协办单不存在');
        }

        foreach ($notes as &$note) {
            $note['create_time_text'] = $note['create_time'] ? $note['create_time'] : '';
        }

        $this->assign('info', $info);
        $this->assign('notes', $notes);
        $this->assign('is_creator', false);
        $this->assign('is_receiver', false);
        $this->assign('can_cancel', false);
        $this->assign('can_close', false);

        $this->assign('page_title', '协办单详情');

        return $this->fetch('cooperate/detail');
    }
}