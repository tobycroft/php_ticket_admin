<?php

namespace app\coop\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\coop\action\CooperateAction;

class CooperateMy extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
            ['receiver_id', '=', UID],
            ['is_canceled', '=', 0],
        ];

        $data_list = CooperateAction::getList($map);

        $priority_list = CooperateAction::getPriorityList();

        foreach ($data_list as &$item) {
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : '';
            $item['is_closed_text'] = $item['is_closed'] ? '<span style="color:green; font-weight:bold;">已关闭</span>' : '<span style="color:red; font-weight:bold;">进行中</span>';
            $item['can_push'] = ($item['creator_id'] == UID || $item['receiver_id'] == UID) && !$item['is_canceled'] && !$item['is_closed'];
            $item['can_close'] = ($item['creator_id'] == UID || $item['receiver_id'] == UID) && !$item['is_canceled'] && !$item['is_closed'];
            $item['can_reopen'] = $item['creator_id'] == UID && !$item['is_canceled'] && $item['is_closed'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('指派给我的协办单')
            ->setTableName('mt_cooperate')
            ->setSearch(['title' => '标题', 'creator_name' => '创建人'])
            ->addColumns([
                ['id', 'ID'],
                ['create_time', '创建时间'],
                ['creator_name', '创建人'],
                ['title', '标题'],
                ['priority_text', '优先级'],
                ['is_closed_text', '状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButton('detail', ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('detail', ['id' => '__id__'])])
            ->addRightButton('push', ['title' => '甩单', 'icon' => 'fa fa-share-alt', 'class' => 'btn btn-xs btn-info', 'href' => url('push', ['id' => '__id__']), 'condition' => 'can_push'])
            ->addRightButton('close', ['title' => '关闭', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('close', ['id' => '__id__']), 'condition' => 'can_close'])
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
        $is_receiver = $info['receiver_id'] == UID;
        $can_cancel = $is_creator && !$info['is_canceled'] && !$info['is_closed'];
        $can_close = ($is_creator || $is_receiver) && !$info['is_canceled'] && !$info['is_closed'];

        $this->assign('info', $info);
        $this->assign('notes', $notes);
        $this->assign('is_creator', $is_creator);
        $this->assign('is_receiver', $is_receiver);
        $this->assign('can_cancel', $can_cancel);
        $this->assign('can_close', $can_close);

        $this->assign('page_title', '协办单详情');

        return $this->fetch('cooperate/detail');
    }

    public function close($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            CooperateAction::close($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }

        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '关闭成功', 'url' => cookie('__forward__')]);
        }
        $this->success('关闭成功', cookie('__forward__'));
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

    public function push($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        $cooperate = CooperateAction::getInfo($id);
        if (!$cooperate) {
            $this->error('协办单不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                CooperateAction::push($id, $data['receiver_id']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }

            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '甩单成功', 'url' => cookie('__forward__')]);
            }
            $this->success('甩单成功', cookie('__forward__'));
        }

        $users = \app\user\model\User::where('status', 1)->where('id', '<>', $cooperate['receiver_id'])->column('id,nickname');

        return ZBuilder::make('form')
            ->setPageTitle('甩单')
            ->addFormItems([
                ['hidden', 'id', $id],
                ['select', 'receiver_id', '接收人', '必填', $users],
            ])
            ->fetch();
    }

    public function note($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                CooperateAction::addNote($id, $data['content']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }

            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '添加进度成功', 'url' => url('detail', ['id' => $id])]);
            }
            $this->success('添加进度成功', url('detail', ['id' => $id]));
        }
    }
}