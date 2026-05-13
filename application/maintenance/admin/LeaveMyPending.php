<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\UserLeaveModel;
use app\maintenance\model\DailyScheduleModel;
use app\user\model\User as UserModel;

class LeaveMyPending extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
            ['user_id', '=', UID],
            ['status', '=', 0]
        ];
        $data_list = UserLeaveModel::where($map)->order('create_time desc')->paginate();

        $type_list = UserLeaveModel::getTypeList();
        $status_list = UserLeaveModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['type_text'] = isset($type_list[$item['leave_type']]) ? $type_list[$item['leave_type']] : '';
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('我的待审核申请')
            ->setTableName('mt_user_leave')
            ->addColumns([
                ['id', 'ID'],
                ['type_text', '类型'],
                ['start_date', '开始日期'],
                ['end_date', '结束日期'],
                ['reason', '理由'],
                ['status_text', '状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons(['add' => ['title' => '新增请假申请']])
            ->addRightButtons(['edit', 'delete'])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['leave_type'])) {
                $this->error('请选择请假类型');
            }

            if (empty($data['start_date'])) {
                $this->error('请选择开始日期');
            }

            if (empty($data['end_date'])) {
                $this->error('请选择结束日期');
            }

            if (empty($data['reason'])) {
                $this->error('请填写请假理由');
            }

            if ($data['start_date'] > $data['end_date']) {
                $this->error('开始日期不能大于结束日期');
            }

            $start_date = new \DateTime($data['start_date']);
            $end_date = new \DateTime($data['end_date']);
            $interval = $start_date->diff($end_date);
            $days = $interval->days + 1;

            for ($i = 0; $i < $days; $i++) {
                $current_date = (clone $start_date)->add(new \DateInterval("P{$i}D"))->format('Y-m-d');
                
                $has_schedule = DailyScheduleModel::where('user_id', UID)
                    ->where('schedule_date', $current_date)
                    ->where('status', 1)
                    ->find();
                
                if (!$has_schedule) {
                    $this->error("{$current_date}当天没有排班，无需申请请假");
                }
            }

            $data['user_id'] = UID;
            $data['user_name'] = get_nickname(UID);
            $data['status'] = 0;

            try {
                UserLeaveModel::create($data);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            
            $this->success('申请成功', url('index'));
        }

        $type_list = UserLeaveModel::getTypeList();

        return ZBuilder::make('form')
            ->setPageTitle('新增请假申请')
            ->addFormItems([
                ['select', 'leave_type', '请假类型', '必填', $type_list, 1],
                ['date', 'start_date', '开始日期', '必填'],
                ['date', 'end_date', '结束日期', '必填'],
                ['textarea', 'reason', '理由', '必填']
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = UserLeaveModel::where('id', $id)->where('user_id', UID)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($info['status'] != 0) {
            $this->error('该申请已处理，无法修改');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['leave_type'])) {
                $this->error('请选择请假类型');
            }

            if (empty($data['start_date'])) {
                $this->error('请选择开始日期');
            }

            if (empty($data['end_date'])) {
                $this->error('请选择结束日期');
            }

            if (empty($data['reason'])) {
                $this->error('请填写请假理由');
            }

            if ($data['start_date'] > $data['end_date']) {
                $this->error('开始日期不能大于结束日期');
            }

            $start_date = new \DateTime($data['start_date']);
            $end_date = new \DateTime($data['end_date']);
            $interval = $start_date->diff($end_date);
            $days = $interval->days + 1;

            for ($i = 0; $i < $days; $i++) {
                $current_date = (clone $start_date)->add(new \DateInterval("P{$i}D"))->format('Y-m-d');
                
                $has_schedule = DailyScheduleModel::where('user_id', UID)
                    ->where('schedule_date', $current_date)
                    ->where('status', 1)
                    ->find();
                
                if (!$has_schedule) {
                    $this->error("{$current_date}当天没有排班，无需申请请假");
                }
            }

            try {
                UserLeaveModel::update($data);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            
            $this->success('修改成功', url('index'));
        }

        $type_list = UserLeaveModel::getTypeList();

        return ZBuilder::make('form')
            ->setPageTitle('编辑请假申请')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'leave_type', '请假类型', '必填', $type_list],
                ['date', 'start_date', '开始日期', '必填'],
                ['date', 'end_date', '结束日期', '必填'],
                ['textarea', 'reason', '理由', '必填']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = null)
    {
        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }

        try {
            UserLeaveModel::where('user_id', UID)->whereIn('id', $ids)->delete();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('删除成功');
    }
}