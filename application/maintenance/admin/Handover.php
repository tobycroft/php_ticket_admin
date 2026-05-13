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
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $map['status'] = ['<>', 2];

        $data_list = HandoverAction::getList($map);

        $status_list = HandoverAction::getStatusList();
        $is_forced_list = HandoverAction::getIsForcedList();

        foreach ($data_list as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['is_forced_text'] = isset($is_forced_list[$item['is_forced']]) ? $is_forced_list[$item['is_forced']] : '';
            $item['can_receive'] = $item['status'] == 0;
            $item['can_cancel'] = $item['status'] != 2 && ($item['creator_id'] == UID || UID == 1);
            
            $event_ids = explode(',', $item['event_ids']);
            $event_count = 0;
            $completed_count = 0;
            foreach ($event_ids as $event_id) {
                if ($event_id) {
                    $event_count++;
                    $event = EventAction::getInfo($event_id);
                    if ($event && $event['status'] == 2) {
                        $completed_count++;
                    }
                }
            }
            $item['event_count'] = $event_count;
            $item['event_status'] = $event_count > 0 ? ($completed_count . '/' . $event_count . ' 已完成') : '无工单';
        }

        return ZBuilder::make('table')
            ->setPageTitle('交接总表')
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
                ['event_count', '工单数'],
                ['event_status', '工单状态'],
                ['create_time', '创建时间'],
                ['receive_time', '接收时间'],
                ['update_time', '更新时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('Handover/detail', ['id' => '__id__'])], 'receive' => ['title' => '接收交接', 'icon' => 'fa fa-handshake-o', 'class' => 'btn btn-xs btn-success', 'href' => url('Handover/receive', ['id' => '__id__']), 'condition' => 'can_receive'], 'cancel' => ['title' => '作废交接', 'icon' => 'fa fa-ban', 'class' => 'btn btn-xs btn-warning', 'href' => url('Handover/cancel', ['id' => '__id__']), 'condition' => 'can_cancel'], 'delete' => ['title' => '删除', 'icon' => 'fa fa-trash', 'class' => 'btn btn-xs btn-danger', 'href' => url('Handover/delete', ['id' => '__id__'])]])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            try {
                HandoverAction::add($data);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            
            $this->success('创建交接成功', url('Handover/index'));
        }

        $default_title = date('Y年m月d日 H:i') . ' ' . session('user_auth.nickname') . '的交接';

        $events = UID == 1 ? HandoverAction::getAllAvailableEvents() : HandoverAction::getAvailableEvents();
        
        $event_options = [];
        foreach ($events as $event) {
            $last_handover = $event['last_handover'] ? ' (最后交接: ' . $event['last_handover'] . ')' : '';
            $event_options[$event['id']] = $event['title'] . $last_handover;
        }

        $maintenance_role_ids = [4, 5, 6, 7];
        $users = UserModel::where('status', 1)
            ->where('role', 'in', $maintenance_role_ids)
            ->select();
        $user_options = [0 => '无人（公开交接）'];
        foreach ($users as $user) {
            $user_options[$user['id']] = $user['nickname'];
        }

        return ZBuilder::make('form')
            ->setPageTitle('创建交接')
            ->addFormItems([
                ['text', 'title', '交接标题', '必填'],
                ['ueditor', 'description', '交接说明', ''],
                ['select', 'default_receiver_id', '默认接收人', '选择默认接收人，不选择则为公开交接', $user_options],
                ['checkbox', 'event_ids', '交接工单', '选择要交接的工单（可多选）', $event_options],
            ])
            ->setFormData(['title' => $default_title])
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

        $this->assign('handover', $handover);
        $this->assign('events', $events);
        $this->assign('page_title', '交接详情');

        return $this->fetch('handover/detail');
    }

    public function receive($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        try {
            HandoverAction::receive($id);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('接收交接成功', url('Handover/index'));
    }

    public function cancel($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        try {
            HandoverAction::cancel($id);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('作废交接成功', url('Handover/index'));
    }

    public function delete($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        try {
            HandoverAction::delete($id);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('删除交接成功', url('Handover/index'));
    }
}