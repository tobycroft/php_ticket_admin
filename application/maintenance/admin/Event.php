<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\EventAction;
use app\maintenance\model\ContactMethodModel;
use app\user\model\User as UserModel;

class Event extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $map[] = ['is_canceled', '=', 0];

        $data_list = EventAction::getList($map);

        $is_closed_list = EventAction::getIsClosedList();
        $is_canceled_list = EventAction::getIsCanceledList();
        $priority_list = EventAction::getPriorityList();

        foreach ($data_list as &$item) {
            $item['is_closed_text'] = isset($is_closed_list[$item['is_closed']]) ? $is_closed_list[$item['is_closed']] : '';
            $item['is_canceled_text'] = isset($is_canceled_list[$item['is_canceled']]) ? $is_canceled_list[$item['is_canceled']] : '';
            $item['priority_text'] = isset($priority_list[$item['priority']]) ? $priority_list[$item['priority']] : '';
            $item['start_time_text'] = $item['start_time'] ? $item['start_time'] : '';
            $item['end_time_text'] = $item['end_time'] ? $item['end_time'] : '';
            $item['can_close'] = ($item['receiver_id'] == UID || $item['creator_id'] == UID) && !$item['is_closed'] && !$item['is_canceled'];
            $item['can_cancel'] = $item['creator_id'] == UID && !$item['is_closed'] && !$item['is_canceled'];
            $item['can_reopen'] = $item['is_closed'] && !$item['is_canceled'];
            $item['can_complete'] = !$item['is_closed'] && !$item['is_canceled'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('工单列表')
            ->setTableName('mt_event')
            ->setSearch(['title' => '标题', 'creator_name' => '发单人', 'customer_name' => '客户'])
            ->addTimeFilter('start_time', '开始时间')
            ->addColumns([
                ['id', 'ID'],
                ['title', '事件标题'],
                ['creator_name', '发单人'],
                ['receiver_name', '接单人'],
                ['customer_name', '对接客户'],
                ['start_time_text', '开始时间'],
                ['priority_text', '优先级'],
                ['is_canceled_text', '状态'],
                ['is_closed_text', '结单状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButtons(['edit', 'detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('detail', ['id' => '__id__'])], 'close' => ['title' => '标注已完成', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('close', ['id' => '__id__']), 'condition' => 'can_complete'], 'reopen' => ['title' => '标注未完成', 'icon' => 'fa fa-undo', 'class' => 'btn btn-xs btn-warning', 'href' => url('reopen', ['id' => '__id__']), 'condition' => 'can_reopen'], 'cancel' => ['title' => '作废', 'icon' => 'fa fa-trash', 'class' => 'btn btn-xs btn-danger', 'href' => url('cancel', ['id' => '__id__']), 'condition' => 'can_cancel']])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['title'])) {
                $this->error('请输入事件标题');
            }

            $data['start_time'] = date('Y/m/d H:i:s', strtotime($data['start_time']));

            try {
                $event = EventAction::add($data);
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

        $priority_list = [
            1 => '<span class="label label-default">1 - 普通</span>',
            2 => '<span class="label label-info">2 - 低</span>',
            3 => '<span class="label label-warning">3 - 中</span>',
            4 => '<span class="label label-danger">4 - 高</span>',
            5 => '<span class="label label-red">5 - 紧急</span>',
        ];

        $contact_method_list = ContactMethodModel::where('status', 1)->column('name', 'id');

        return ZBuilder::make('form')
            ->setPageTitle('新增工单')
            ->addFormItems([
                ['text', 'title', '事件标题', '必填'],
                ['text', 'creator_name', '发单人', '必填'],
                ['checkbox', 'contact_method', '对接方式', '可多选', $contact_method_list],
                ['ueditor', 'content', '事件描述'],
                ['datetime', 'start_time', '开始时间', '必填', '', date('Y/m/d 08:30')],
                ['text', 'customer_name', '对接客户'],
                ['select', 'priority', '优先级', '', $priority_list, 1],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['id'])) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => '缺少ID参数']);
                }
                $this->error('缺少ID参数');
            }

            try {
                EventAction::edit($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '编辑成功', 'url' => cookie('__forward__') ?: url('index')]);
            }
            $this->success('编辑成功', cookie('__forward__') ?: url('index'));
        }

        $info = EventAction::getInfo($id);
        if (!$info) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '工单不存在']);
            }
            $this->error('工单不存在');
        }

        $priority_list = [
            1 => '<span class="label label-default">1 - 普通</span>',
            2 => '<span class="label label-info">2 - 低</span>',
            3 => '<span class="label label-warning">3 - 中</span>',
            4 => '<span class="label label-danger">4 - 高</span>',
            5 => '<span class="label label-red">5 - 紧急</span>',
        ];

        $contact_method_list = ContactMethodModel::where('status', 1)->column('name', 'id');

        if (!empty($info['contact_method'])) {
            $info['contact_method'] = explode(',', $info['contact_method']);
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑工单')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'title', '事件标题', '必填'],
                ['text', 'creator_name', '发单人', '必填'],
                ['checkbox', 'contact_method', '对接方式', '可多选', $contact_method_list],
                ['ueditor', 'content', '事件描述'],
                ['datetime', 'start_time', '开始时间', '必填'],
                ['text', 'customer_name', '对接客户'],
                ['select', 'priority', '优先级', '', $priority_list],
                ['radio', 'is_closed', '结单状态', '', ['未结单', '已结单'], $info['is_closed']],
                ['radio', 'is_canceled', '作废状态', '', ['正常', '已作废'], $info['is_canceled']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        $info = EventAction::getInfo($id);
        $notes = EventAction::getNotes($id);
        $flows = EventAction::getFlows($id);

        $info['start_time_text'] = $info['start_time'] ? $info['start_time'] : '';
        $info['end_time_text'] = $info['end_time'] ? $info['end_time'] : '';
        $info['is_closed_text'] = $info['is_closed'] ? '已结单' : '未结单';

        foreach ($notes as &$note) {
            $note['create_time_text'] = $note['create_time'] ? $note['create_time'] : '';
        }

        foreach ($flows as &$flow) {
            $flow['create_time_text'] = $flow['create_time'] ? $flow['create_time'] : '';
            $flow['handle_time_text'] = $flow['handle_time'] ? $flow['handle_time'] : '';
            $flow['status_text'] = ['待处理', '已处理', '已退回'][$flow['status']];
        }

        $is_current_receiver = $info['receiver_id'] == UID;
        $is_sender = $info['creator_id'] == UID;
        $can_close = $is_current_receiver && !$info['is_closed'];

        $this->assign('info', $info);
        $this->assign('notes', $notes);
        $this->assign('flows', $flows);
        $this->assign('can_close', $can_close);
        $this->assign('is_current_receiver', $is_current_receiver);
        $this->assign('is_sender', $is_sender);

        $this->assign('page_title', '工单详情');

        return $this->fetch('event/detail');
    }

    public function receive($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            EventAction::receive($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '接单成功', 'url' => cookie('__forward__')]);
        }
        $this->success('接单成功', cookie('__forward__'));
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
            EventAction::close($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '标注已完成', 'url' => cookie('__forward__')]);
        }
        $this->success('标注已完成', cookie('__forward__'));
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
            EventAction::reopen($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '标注未完成', 'url' => cookie('__forward__')]);
        }
        $this->success('标注未完成', cookie('__forward__'));
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
            EventAction::cancel($id);
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

    public function active($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            EventAction::active($id);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '激活成功', 'url' => cookie('__forward__')]);
        }
        $this->success('激活成功', cookie('__forward__'));
    }

    public function push($id = null)
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
                EventAction::push($id, $data['to_user_id'], $data['reason']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '推送成功', 'url' => cookie('__forward__')]);
            }
            $this->success('推送成功', cookie('__forward__'));
        }

        $users = UserModel::where('status', 1)->whereIn('role', [3, 4, 5, 6, 7])->column('id,nickname');

        return ZBuilder::make('form')
            ->setPageTitle('推送工单')
            ->addFormItems([
                ['select', 'to_user_id', '接收人', '必填', $users],
                ['textarea', 'reason', '推送理由'],
            ])
            ->fetch();
    }

    public function addNote($id = null)
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
                EventAction::addNote($id, $data['content']);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '添加备注成功', 'url' => url('detail', ['id' => $id])]);
            }
            $this->success('添加备注成功', url('detail', ['id' => $id]));
        }
    }

    public function delete($ids = [])
    {
        return $this->setStatus('delete');
    }

    public function enable($ids = [])
    {
        return $this->setStatus('enable');
    }

    public function disable($ids = [])
    {
        return $this->setStatus('disable');
    }

    public function setStatus($type = '', $record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        foreach ($ids as $id) {
            if ($type == 'delete') {
                try {
                    EventAction::delete($id);
                } catch (\ErrorException $e) {
                    $this->error($e->getMessage());
                }
            } else {
                try {
                    EventAction::edit(['id' => $id, 'status' => $type == 'enable' ? 1 : 0]);
                } catch (\ErrorException $e) {
                    $this->error($e->getMessage());
                }
            }
        }

        $this->success('操作成功');
    }

    public function quickEdit($record = [])
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (empty($data['pk']) || empty($data['name']) || !isset($data['value'])) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }

            try {
                if ($data['name'] == 'is_closed') {
                    if ($data['value']) {
                        EventAction::close($data['pk']);
                    } else {
                        EventAction::edit(['id' => $data['pk'], 'is_closed' => 0, 'end_time' => null, 'closer_id' => 0, 'closer_name' => '']);
                    }
                } else {
                    EventAction::edit(['id' => $data['pk'], $data['name'] => $data['value']]);
                }
                return json(['code' => 1, 'msg' => '操作成功']);
            } catch (\ErrorException $e) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
        }
        return json(['code' => 0, 'msg' => '请求方式错误']);
    }
}