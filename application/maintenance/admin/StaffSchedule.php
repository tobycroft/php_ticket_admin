<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\UserScheduleAction;
use app\maintenance\model\UserScheduleModel;
use app\maintenance\model\UserLeaveModel;
use app\maintenance\model\UserSwapModel;
use app\user\model\User as UserModel;

class StaffSchedule extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $users = UserScheduleAction::getMaintenanceUsers();
        $day_list = UserScheduleAction::getDayOfWeekList();

        if ($this->request->isPost()) {
            $data = $this->request->post();
            
            try {
                $insert_data = [];
                foreach ($users as $user_id => $user_name) {
                    foreach ($day_list as $day => $day_name) {
                        $key = "schedule_{$user_id}_{$day}";
                        if (isset($data[$key]) && $data[$key] == 1) {
                            $start_key = "start_{$user_id}_{$day}";
                            $end_key = "end_{$user_id}_{$day}";
                            $desc_key = "desc_{$user_id}_{$day}";
                            
                            $insert_data[] = [
                                'user_id' => $user_id,
                                'day_of_week' => $day,
                                'start_time' => isset($data[$start_key]) ? $data[$start_key] : '09:00:00',
                                'end_time' => isset($data[$end_key]) ? $data[$end_key] : '18:00:00',
                                'description' => isset($data[$desc_key]) ? $data[$desc_key] : '',
                                'status' => 1,
                                'create_time' => time(),
                                'update_time' => time(),
                            ];
                        }
                    }
                }

                if (!empty($insert_data)) {
                    UserScheduleModel::where('user_id', 'in', array_keys($users))->delete();
                    UserScheduleModel::insertAll($insert_data);
                    action_log('staff_schedule_batch', 'mt_user_schedule', '', UID);
                }

                $this->success('批量排班设置成功', url('index'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $schedule_data = [];
        $schedules = UserScheduleModel::where('user_id', 'in', array_keys($users))->select();
        foreach ($schedules as $schedule) {
            $schedule_data[$schedule['user_id']][$schedule['day_of_week']] = $schedule;
        }

        $this->assign('users', $users);
        $this->assign('day_list', $day_list);
        $this->assign('schedule_data', $schedule_data);
        $this->assign('page_title', '人员排班管理');

        return $this->fetch('staff_schedule/index');
    }

    public function add($type = '')
    {
        if ($type == 'leave') {
            return $this->addLeave();
        } elseif ($type == 'swap') {
            return $this->addSwap();
        }
        $this->redirect('index');
    }

    public function edit($id = null, $type = '')
    {
        if ($type == 'leave') {
            return $this->editLeave($id);
        } elseif ($type == 'swap') {
            return $this->editSwap($id);
        }
        $this->redirect('index');
    }

    public function delete($ids = [], $type = '')
    {
        if ($type == 'leave') {
            return $this->deleteLeave($ids);
        } elseif ($type == 'swap') {
            return $this->deleteSwap($ids);
        }
        $this->redirect('index');
    }

    public function leave()
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
            ->addTopButtons(['add' => ['title' => '新增', 'icon' => 'fa fa-plus', 'href' => url('add', ['type' => 'leave'])]])
            ->addRightButtons(['edit' => ['href' => url('edit', ['id' => '__id__', 'type' => 'leave'])], 'delete' => ['href' => url('delete', ['ids' => '__id__', 'type' => 'leave'])], 'approve' => ['title' => '批准', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('approveLeave', ['id' => '__id__']), 'condition' => 'can_approve'], 'reject' => ['title' => '拒绝', 'icon' => 'fa fa-times-circle', 'class' => 'btn btn-xs btn-danger', 'href' => url('rejectLeave', ['id' => '__id__']), 'condition' => 'can_approve']])
            ->setRowList($data_list)
            ->fetch();
    }

    public function addLeave()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['user_id'] = UID;
            $data['user_name'] = get_nickname(UID);

            try {
                UserLeaveModel::create($data);
                action_log('leave_add', 'mt_user_leave', '', UID);
                $this->success('请假申请提交成功', url('leave'));
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

    public function editLeave($id = null)
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

    public function approveLeave($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            UserLeaveModel::update([
                'id' => $id,
                'status' => 1,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('leave_approve', 'mt_user_leave', $id, UID);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '批准成功', 'url' => cookie('__forward__')]);
            }
            $this->success('批准成功', cookie('__forward__'));
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }

    public function rejectLeave($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            UserLeaveModel::update([
                'id' => $id,
                'status' => 2,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('leave_reject', 'mt_user_leave', $id, UID);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '已拒绝', 'url' => cookie('__forward__')]);
            }
            $this->success('已拒绝', cookie('__forward__'));
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }

    public function deleteLeave($ids = [])
    {
        $ids = (array)$ids;
        try {
            UserLeaveModel::where('id', 'in', $ids)->delete();
            action_log('leave_delete', 'mt_user_leave', '', UID);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '删除成功']);
            }
            $this->success('删除成功');
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }

    public function swap()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $data_list = UserSwapModel::where($map)->order('create_time desc')->paginate();

        $user_list = UserScheduleAction::getMaintenanceUsers();
        $status_list = UserSwapModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['user_name'] = isset($user_list[$item['user_id']]) ? $user_list[$item['user_id']] : $item['user_name'];
            $item['target_user_name'] = isset($user_list[$item['target_user_id']]) ? $user_list[$item['target_user_id']] : $item['target_user_name'];
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['create_time_text'] = $item['create_time'] ? date('Y-m-d H:i:s', $item['create_time']) : '';
            $item['approve_time_text'] = $item['approve_time'] ? date('Y-m-d H:i:s', $item['approve_time']) : '';
            $item['can_approve'] = $item['status'] == 0;
        }

        return ZBuilder::make('table')
            ->setPageTitle('调班管理')
            ->setTableName('mt_user_swap')
            ->setSearch(['user_name' => '申请人', 'target_user_name' => '调换人'])
            ->addColumns([
                ['id', 'ID'],
                ['user_name', '申请人'],
                ['target_user_name', '调换人'],
                ['swap_date', '调班日期'],
                ['reason', '调班理由'],
                ['status_text', '状态'],
                ['create_time_text', '申请时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons(['add' => ['title' => '新增', 'icon' => 'fa fa-plus', 'href' => url('add', ['type' => 'swap'])]])
            ->addRightButtons(['edit' => ['href' => url('edit', ['id' => '__id__', 'type' => 'swap'])], 'delete' => ['href' => url('delete', ['ids' => '__id__', 'type' => 'swap'])], 'approve' => ['title' => '批准', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('approveSwap', ['id' => '__id__']), 'condition' => 'can_approve'], 'reject' => ['title' => '拒绝', 'icon' => 'fa fa-times-circle', 'class' => 'btn btn-xs btn-danger', 'href' => url('rejectSwap', ['id' => '__id__']), 'condition' => 'can_approve']])
            ->setRowList($data_list)
            ->fetch();
    }

    public function addSwap()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $target_user = UserModel::where('id', $data['target_user_id'])->find();
            if (!$target_user) {
                $this->error('调换人员不存在');
            }

            $data['user_id'] = UID;
            $data['user_name'] = get_nickname(UID);
            $data['target_user_name'] = $target_user['nickname'];

            try {
                UserSwapModel::create($data);
                action_log('swap_add', 'mt_user_swap', '', UID);
                $this->success('调班申请提交成功', url('swap'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $user_list = UserScheduleAction::getMaintenanceUsers();
        unset($user_list[UID]);

        return ZBuilder::make('form')
            ->setPageTitle('调班申请')
            ->addFormItems([
                ['select', 'target_user_id', '调换人员', '必填', $user_list],
                ['date', 'swap_date', '调班日期', '必填'],
                ['textarea', 'reason', '调班理由', '必填'],
            ])
            ->fetch();
    }

    public function editSwap($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();

            $target_user = UserModel::where('id', $data['target_user_id'])->find();
            if (!$target_user) {
                $this->error('调换人员不存在');
            }

            $data['target_user_name'] = $target_user['nickname'];

            try {
                UserSwapModel::update($data);
                action_log('swap_edit', 'mt_user_swap', $id, UID);
                $this->success('编辑成功', cookie('__forward__'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $info = UserSwapModel::where('id', $id)->find();
        $user_list = UserScheduleAction::getMaintenanceUsers();
        unset($user_list[$info['user_id']]);

        return ZBuilder::make('form')
            ->setPageTitle('编辑调班')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'target_user_id', '调换人员', '必填', $user_list],
                ['date', 'swap_date', '调班日期', '必填'],
                ['textarea', 'reason', '调班理由', '必填'],
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function approveSwap($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            UserSwapModel::update([
                'id' => $id,
                'status' => 1,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('swap_approve', 'mt_user_swap', $id, UID);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '批准成功', 'url' => cookie('__forward__')]);
            }
            $this->success('批准成功', cookie('__forward__'));
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }

    public function rejectSwap($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        try {
            UserSwapModel::update([
                'id' => $id,
                'status' => 2,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('swap_reject', 'mt_user_swap', $id, UID);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '已拒绝', 'url' => cookie('__forward__')]);
            }
            $this->success('已拒绝', cookie('__forward__'));
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }

    public function deleteSwap($ids = [])
    {
        $ids = (array)$ids;
        try {
            UserSwapModel::where('id', 'in', $ids)->delete();
            action_log('swap_delete', 'mt_user_swap', '', UID);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '删除成功']);
            }
            $this->success('删除成功');
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }
}