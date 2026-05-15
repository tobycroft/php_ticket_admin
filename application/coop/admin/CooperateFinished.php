<?php

namespace app\coop\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\coop\action\CooperateAction;

class CooperateFinished extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
            ['creator_id', '=', UID],
            ['is_canceled', '=', 0],
            ['is_closed', '=', 1],
        ];

        $data_list = CooperateAction::getList($map);

        $priority_list = CooperateAction::getPriorityList();

        foreach ($data_list as &$item) {
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : '';
            $item['can_reopen'] = $item['creator_id'] == UID && !$item['is_canceled'] && $item['is_closed'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('已完成的协办单')
            ->setTableName('mt_cooperate')
            ->setSearch(['title' => '标题', 'receiver_name' => '接收人'])
            ->addColumns([
                ['id', 'ID'],
                ['create_time', '创建时间'],
                ['receiver_name', '接收人'],
                ['title', '标题'],
                ['priority_text', '优先级'],
                ['close_time', '完成时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('detail', ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('detail', ['id' => '__id__'])])
            ->addRightButton('reopen', ['title' => '重新打开', 'icon' => 'fa fa-undo', 'class' => 'btn btn-xs btn-warning', 'href' => url('reopen', ['id' => '__id__']), 'condition' => 'can_reopen'])
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

        $is_creator = $info['creator_id'] == UID;
        $this->assign('info', $info);
        $this->assign('notes', $notes);
        $this->assign('is_creator', $is_creator);
        $this->assign('is_receiver', false);
        $this->assign('can_cancel', false);
        $this->assign('can_close', false);

        $this->assign('page_title', '协办单详情');

        return $this->fetch('cooperate/detail');
    }

    public function reopen($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            CooperateAction::reopen($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }

        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '重新打开成功', 'url' => cookie('__forward__')]);
        }
        $this->success('重新打开成功', cookie('__forward__'));
    }
}