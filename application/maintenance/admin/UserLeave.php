<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\UserScheduleAction;
use app\maintenance\model\UserLeaveModel;

class UserLeave extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $data_list = UserLeaveModel::where($map)->order('create_time desc')->paginate();

        $user_list = UserScheduleAction::getMaintenanceUsers();
        $type_list = UserLeaveModel::getTypeList();
        $status_list = UserLeaveModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['user_name'] = isset($user_list[$item['user_id']]) ? $user_list[$item['user_id']] : $item['user_name'];
            $item['type_text'] = isset($type_list[$item['type']]) ? $type_list[$item['type']] : '';
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
            $item['approve_time_text'] = $item['approve_time'] ? date('Y-m-d H:i:s', $item['approve_time']) : '';
            $item['can_approve'] = $item['status'] == 0;
        }

        return ZBuilder::make('table')
            ->setPageTitle('请假管理')
            ->setTableName('mt_user_leave')
            ->setSearch(['user_name' => '申请人', 'reason' => '请假理由'])
            ->addColumns([
                ['id', 'ID'],
                ['user_name', '申请人'],
                ['type_text', '类型'],
                ['start_date', '开始日期'],
                ['end_date', '结束日期'],
                ['reason', '请假理由'],
                ['status_text', '状态'],
                ['create_time_text', '申请时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButtons(['edit', 'delete', 'approve' => ['title' => '批准', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('approve', ['id' => '__id__']), 'condition' => 'can_approve'], 'reject' => ['title' => '拒绝', 'icon' => 'fa fa-times-circle', 'class' => 'btn btn-xs btn-danger', 'href' => url('reject', ['id' => '__id__']), 'condition' => 'can_approve']])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['user_id'] = UID;
            $data['user_name'] = get_nickname(UID);

            try {
                UserLeaveModel::create($data);
                action_log('leave_add', 'mt_user_leave', '', UID);
                $this->success('请假申请提交成功', url('index'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('请假申请')
            ->addFormItems([
                ['radio', 'type', '请假类型', '', ['请假', '调休'], 1],
                ['date', 'start_date', '开始日期', '必填'],
                ['date', 'end_date', '结束日期', '必填'],
                ['textarea', 'reason', '请假理由', '必填'],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                UserLeaveModel::update($data);
                action_log('leave_edit', 'mt_user_leave', $id, UID);
                $this->success('编辑成功', cookie('__forward__'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $info = UserLeaveModel::where('id', $id)->find();

        return ZBuilder::make('form')
            ->setPageTitle('编辑请假')
            ->addFormItems([
                ['hidden', 'id'],
                ['radio', 'type', '请假类型', '', ['请假', '调休']],
                ['date', 'start_date', '开始日期', '必填'],
                ['date', 'end_date', '结束日期', '必填'],
                ['textarea', 'reason', '请假理由', '必填'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function approve($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        try {
            UserLeaveModel::update([
                'id' => $id,
                'status' => 1,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('leave_approve', 'mt_user_leave', $id, UID);
            $this->success('批准成功', cookie('__forward__'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function reject($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        try {
            UserLeaveModel::update([
                'id' => $id,
                'status' => 2,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('leave_reject', 'mt_user_leave', $id, UID);
            $this->success('已拒绝', cookie('__forward__'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        try {
            UserLeaveModel::where('id', 'in', $ids)->delete();
            action_log('leave_delete', 'mt_user_leave', '', UID);
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}