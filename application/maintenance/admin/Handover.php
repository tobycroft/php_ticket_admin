<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\HandoverAction;
use app\maintenance\action\EventAction;
use app\user\model\User as UserModel;

class Handover extends Admin
{
    public function index()
    {
        $this->redirect(url('Handover/list'));
    }

    public function list()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = HandoverAction::getList($map);

        $status_list = HandoverAction::getStatusList();
        $is_forced_list = HandoverAction::getIsForcedList();

        foreach ($data_list as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['is_forced_text'] = isset($is_forced_list[$item['is_forced']]) ? $is_forced_list[$item['is_forced']] : '';
            $item['can_receive'] = $item['status'] == 0;
            $item['can_complete'] = $item['status'] == 1 && ($item['actual_receiver_id'] == UID || $item['creator_id'] == UID);
        }

        return ZBuilder::make('table')
            ->setPageTitle('交接列表')
            ->setTableName('mt_handover')
            ->setSearch(['title' => '标题', 'creator_name' => '创建人'])
            ->addColumns([
                ['id', 'ID'],
                ['title', '交接标题'],
                ['creator_name', '创建人'],
                ['default_receiver_name', '默认接收人'],
                ['actual_receiver_name', '实际接收人'],
                ['status_text', '状态'],
                ['is_forced_text', '交接类型'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Handover/detail', ['id' => '__id__'])], 'receive' => ['title' => '接收交接', 'icon' => 'fa fa-handshake-o', 'class' => 'btn btn-xs btn-success', 'href' => url('Handover/receive', ['id' => '__id__']), 'condition' => 'can_receive'], 'complete' => ['title' => '完成交接', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-primary', 'href' => url('Handover/complete', ['id' => '__id__']), 'condition' => 'can_complete'], 'delete' => ['title' => '删除', 'icon' => 'fa fa-trash', 'class' => 'btn btn-xs btn-danger', 'href' => url('Handover/delete', ['id' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            try {
                HandoverAction::add($data);
                $this->success('创建交接成功', url('Handover/list'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $events = EventAction::getAvailableEvents();
        
        $event_options = [];
        foreach ($events as $event) {
            $event_options[$event['id']] = $event['title'];
        }

        $users = UserModel::where('status', 1)->select();
        $user_options = [0 => '无人（公开交接）'];
        foreach ($users as $user) {
            $user_options[$user['id']] = $user['nickname'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('创建交接')
            ->addFormItems([
                ['text', 'title', '交接标题', '必填', true],
                ['textarea', 'description', '交接说明'],
                ['select', 'default_receiver_id', '默认接收人', '选择默认接收人，不选择则为公开交接', false, $user_options],
                ['select', 'event_ids', '交接工单', '选择要交接的工单', true, $event_options, 'multiple'],
            ])
            ->fetch();
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $handover = HandoverAction::getInfo($id);
        if (!$handover) {
            $this->error('交接记录不存在');
        }

        $status_list = HandoverAction::getStatusList();
        $is_forced_list = HandoverAction::getIsForcedList();

        $event_ids = explode(',', $handover['event_ids']);
        $events = [];
        foreach ($event_ids as $event_id) {
            if ($event_id) {
                $event = EventAction::getInfo($event_id);
                if ($event) {
                    $events[] = $event;
                }
            }
        }

        $handover['status_text'] = isset($status_list[$handover['status']]) ? $status_list[$handover['status']] : '';
        $handover['is_forced_text'] = isset($is_forced_list[$handover['is_forced']]) ? $is_forced_list[$handover['is_forced']] : '';

        return ZBuilder::make('form')
            ->setPageTitle('交接详情')
            ->setFormItems([
                ['static', 'title', '交接标题'],
                ['static', 'description', '交接说明'],
                ['static', 'creator_name', '创建人'],
                ['static', 'default_receiver_name', '默认接收人'],
                ['static', 'actual_receiver_name', '实际接收人'],
                ['static', 'status_text', '状态'],
                ['static', 'is_forced_text', '交接类型'],
                ['static', 'create_time', '创建时间'],
                ['static', 'receive_time', '接收时间'],
            ])
            ->setFormData($handover)
            ->setExtraHtml('<div class="panel panel-default"><div class="panel-heading"><h3 class="panel-title">交接工单列表</h3></div><div class="panel-body"><table class="table table-hover"><thead><tr><th>ID</th><th>标题</th><th>发单人</th></tr></thead><tbody>' . 
                implode('', array_map(function($e) { 
                    return '<tr><td>' . $e['id'] . '</td><td>' . $e['title'] . '</td><td>' . $e['creator_name'] . '</td></tr>'; 
                }, $events)) . 
                '</tbody></table></div></div>')
            ->fetch();
    }

    public function receive($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        try {
            HandoverAction::receive($id);
            $this->success('接收交接成功', url('Handover/list'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function complete($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        try {
            HandoverAction::complete($id);
            $this->success('完成交接成功', url('Handover/list'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        try {
            HandoverAction::delete($id);
            $this->success('删除交接成功', url('Handover/list'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}