<?php

namespace app\coop\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\coop\action\CooperateAction;
use app\user\model\User as UserModel;

class Cooperate extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $map[] = ['is_canceled', '=', 0];

        $data_list = CooperateAction::getList($map);

        $priority_list = CooperateAction::getPriorityList();

        foreach ($data_list as &$item) {
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : '';
            $item['is_canceled_text'] = $item['is_canceled'] ? '已作废' : '正常';
            $item['can_cancel'] = $item['creator_id'] == UID && !$item['is_canceled'];
            $item['can_push'] = !$item['is_canceled'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('协办单列表')
            ->setTableName('mt_cooperate')
            ->setSearch(['title' => '标题', 'creator_name' => '创建人', 'receiver_name' => '接收人'])
            ->addColumns([
                ['id', 'ID'],
                ['create_time', '创建时间'],
                ['creator_name', '创建人'],
                ['receiver_name', '接收人'],
                ['title', '标题'],
                ['priority_text', '优先级'],
                ['is_canceled_text', '状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButton('detail', ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('detail', ['id' => '__id__'])])
            ->addRightButton('push', ['title' => '甩单', 'icon' => 'fa fa-share-alt', 'class' => 'btn btn-xs btn-info', 'href' => url('push', ['id' => '__id__']), 'condition' => 'can_push'])
            ->addRightButton('cancel', ['title' => '作废', 'icon' => 'fa fa-trash', 'class' => 'btn btn-xs btn-danger', 'href' => url('cancel', ['id' => '__id__']), 'condition' => 'can_cancel'])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['title'])) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => '请输入标题']);
                }
                $this->error('请输入标题');
            }

            try {
                CooperateAction::add($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }

            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '创建成功', 'url' => url('index')]);
            }
            $this->success('创建成功', url('index'));
        }

        $priority_list = CooperateAction::getPriorityList();

        $users = UserModel::where('status', 1)->column('id,nickname');

        return ZBuilder::make('form')
            ->setPageTitle('新增协办单')
            ->addFormItems([
                ['text', 'title', '标题', '必填'],
                ['select', 'receiver_id', '接收人', '必填', $users],
                ['ueditor', 'content', '内容'],
                ['select', 'priority', '优先级', '', $priority_list, 1],
            ])
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
        $can_cancel = $is_creator && !$info['is_canceled'];

        $this->assign('info', $info);
        $this->assign('notes', $notes);
        $this->assign('is_creator', $is_creator);
        $this->assign('is_receiver', $is_receiver);
        $this->assign('can_cancel', $can_cancel);

        $this->assign('page_title', '协办单详情');

        return $this->fetch('cooperate/detail');
    }

    public function cancel($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            CooperateAction::cancel($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }

        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '作废成功', 'url' => cookie('__forward__')]);
        }
        $this->success('作废成功', cookie('__forward__'));
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

        $users = UserModel::where('status', 1)->where('id', '<>', $cooperate['receiver_id'])->column('id,nickname');

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