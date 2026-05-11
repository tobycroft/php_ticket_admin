<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\UserScheduleAction;
use app\maintenance\model\UserSwapModel;
use app\user\model\User as UserModel;

class UserSwap extends Admin
{
    public function index()
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
            ->addTopButtons('add')
            ->addRightButtons(['edit', 'delete', 'approve' => ['title' => '批准', 'icon' => 'fa fa-check-circle', 'class' => 'btn btn-xs btn-success', 'href' => url('approve', ['id' => '__id__']), 'condition' => 'can_approve'], 'reject' => ['title' => '拒绝', 'icon' => 'fa fa-times-circle', 'class' => 'btn btn-xs btn-danger', 'href' => url('reject', ['id' => '__id__']), 'condition' => 'can_approve']])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
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
                $this->success('调班申请提交成功', url('index'));
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

    public function edit($id = null)
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

    public function approve($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        try {
            UserSwapModel::update([
                'id' => $id,
                'status' => 1,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('swap_approve', 'mt_user_swap', $id, UID);
            $this->success('批准成功', cookie('__forward__'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function reject($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        try {
            UserSwapModel::update([
                'id' => $id,
                'status' => 2,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => time(),
            ]);
            action_log('swap_reject', 'mt_user_swap', $id, UID);
            $this->success('已拒绝', cookie('__forward__'));
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }

    public function delete($ids = [])
    {
        $ids = (array)$ids;
        try {
            UserSwapModel::where('id', 'in', $ids)->delete();
            action_log('swap_delete', 'mt_user_swap', '', UID);
            $this->success('删除成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
    }
}