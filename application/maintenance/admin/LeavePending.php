<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\UserLeaveModel;
use app\maintenance\model\DailyScheduleModel;

class LeavePending extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
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
            ->setPageTitle('待审核请假')
            ->setTableName('mt_user_leave')
            ->addColumns([
                ['id', 'ID'],
                ['user_name', '申请人'],
                ['type_text', '类型'],
                ['start_date', '开始日期'],
                ['end_date', '结束日期'],
                ['reason', '理由'],
                ['status_text', '状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons([
                'approve' => ['title' => '批准', 'icon' => 'fa fa-check', 'class' => 'btn btn-xs btn-success', 'href' => url('approve', ['id' => '__id__'])],
                'reject' => ['title' => '拒绝', 'icon' => 'fa fa-times', 'class' => 'btn btn-xs btn-danger', 'href' => url('reject', ['id' => '__id__'])],
                'detail' => ['title' => '详情', 'icon' => 'fa fa-eye', 'href' => url('detail', ['id' => '__id__'])]
            ])
            ->setRowList($data_list)
            ->fetch();
    }

    public function approve($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = UserLeaveModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($info['status'] != 0) {
            $this->error('该申请已处理');
        }

        try {
            \think\Db::startTrans();

            UserLeaveModel::update([
                'id' => $id,
                'status' => 1,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID)
            ]);

            $start_date = new \DateTime($info['start_date']);
            $end_date = new \DateTime($info['end_date']);
            $interval = $start_date->diff($end_date);
            $days = $interval->days + 1;

            for ($i = 0; $i < $days; $i++) {
                $current_date = (clone $start_date)->add(new \DateInterval("P{$i}D"))->format('Y-m-d');
                
                DailyScheduleModel::where('user_id', $info['user_id'])
                    ->where('schedule_date', $current_date)
                    ->delete();
            }

            \think\Db::commit();
        } catch (\Exception $e) {
            if ($e instanceof \think\exception\HttpResponseException) {
                throw $e;
            }
            \think\Db::rollback();
            $this->error($e->getMessage());
        }
        
        $this->success('批准成功', url('index'));
    }

    public function reject($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = UserLeaveModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($info['status'] != 0) {
            $this->error('该申请已处理');
        }

        try {
            UserLeaveModel::update([
                'id' => $id,
                'status' => 2,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID)
            ]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('拒绝成功', url('index'));
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = UserLeaveModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        $type_list = UserLeaveModel::getTypeList();
        $status_list = UserLeaveModel::getStatusList();

        $info['type_text'] = isset($type_list[$info['leave_type']]) ? $type_list[$info['leave_type']] : '';
        $info['status_text'] = isset($status_list[$info['status']]) ? $status_list[$info['status']] : '';

        return ZBuilder::make('form')
            ->setPageTitle('请假详情')
            ->addFormItems([
                ['static', 'user_name', '申请人'],
                ['static', 'type_text', '请假类型'],
                ['static', 'start_date', '开始日期'],
                ['static', 'end_date', '结束日期'],
                ['static', 'reason', '理由'],
                ['static', 'status_text', '状态'],
                ['static', 'approver_name', '审批人'],
                ['static', 'create_time', '申请时间']
            ])
            ->setFormData($info)
            ->fetch();
    }
}