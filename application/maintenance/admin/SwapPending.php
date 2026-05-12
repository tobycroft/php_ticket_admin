<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\DailyScheduleModel;
use app\maintenance\model\UserSwapModel;

class SwapPending extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [['status', '=', 0]];
        $data_list = UserSwapModel::where($map)->order('create_time desc')->paginate();

        $status_list = UserSwapModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('待审核调班')
            ->setTableName('mt_user_swap')
            ->setSearch(['user_name' => '申请人'])
            ->addColumns([
                ['id', 'ID'],
                ['user_name', '申请人'],
                ['target_user_name', '调换对象'],
                ['swap_date', '调换日期'],
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

        $info = UserSwapModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($info['status'] != 0) {
            $this->error('该申请已处理');
        }

        try {
            \think\Db::startTrans();

            UserSwapModel::update([
                'id' => $id,
                'status' => 1,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => date('Y-m-d H:i:s')
            ]);

            $schedule = DailyScheduleModel::where('user_id', $info['target_user_id'])
                ->where('schedule_date', $info['swap_date'])
                ->where('status', 1)
                ->find();

            if ($schedule) {
                DailyScheduleModel::update([
                    'id' => $schedule['id'],
                    'user_id' => $info['user_id'],
                    'user_name' => $info['user_name']
                ]);
            }

            \think\Db::commit();
            $this->success('批准成功，已替换排班人员', url('index'));
        } catch (\ErrorException $e) {
            \think\Db::rollback();
            $this->error('操作失败: ' . $e->getMessage());
        }
    }

    public function reject($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = UserSwapModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($info['status'] != 0) {
            $this->error('该申请已处理');
        }

        try {
            \think\Db::startTrans();

            UserSwapModel::update([
                'id' => $id,
                'status' => 2,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => date('Y-m-d H:i:s')
            ]);

            \think\Db::commit();

            $this->success('拒绝成功', url('index'));
        } catch (\ErrorException $e) {
            \think\Db::rollback();
            $this->error($e->getMessage());
        }
    }

    public function detail($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = UserSwapModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        $status_list = UserSwapModel::getStatusList();
        $info['status_text'] = isset($status_list[$info['status']]) ? $status_list[$info['status']] : '';

        return ZBuilder::make('form')
            ->setPageTitle('调班申请详情')
            ->addFormItems([
                ['static', 'user_name', '申请人'],
                ['static', 'target_user_name', '调换对象'],
                ['static', 'swap_date', '调换日期'],
                ['static', 'reason', '理由'],
                ['static', 'status_text', '状态'],
                ['static', 'approver_name', '审批人'],
                ['static', 'approve_time', '审批时间'],
                ['static', 'create_time', '申请时间']
            ])
            ->setFormData($info)
            ->fetch();
    }
}