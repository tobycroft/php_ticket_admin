<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\UserLeaveModel;
use app\user\model\User as UserModel;

class UserLeave extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $data_list = UserLeaveModel::where($map)->order('create_time desc')->paginate();

        $type_list = UserLeaveModel::getTypeList();
        $status_list = UserLeaveModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['type_text'] = isset($type_list[$item['leave_type']]) ? $type_list[$item['leave_type']] : '';
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['can_approve'] = $item['status'] == 0;
        }

        return ZBuilder::make('table')
            ->setPageTitle('请假管理')
            ->setTableName('mt_user_leave')
            ->setSearch(['user_name' => '申请人'])
            ->addColumns([
                ['id', 'ID'],
                ['user_name', '申请人'],
                ['type_text', '类型'],
                ['start_date', '开始日期'],
                ['end_date', '结束日期'],
                ['reason', '理由'],
                ['status_text', '状态'],
                ['approver_name', '审批人'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButtons(['edit', 'delete', 'approve' => ['title' => '批准', 'icon' => 'fa fa-check', 'class' => 'btn btn-xs btn-success', 'href' => url('approve', ['id' => '__id__']), 'condition' => 'can_approve'], 'reject' => ['title' => '拒绝', 'icon' => 'fa fa-times', 'class' => 'btn btn-xs btn-danger', 'href' => url('reject', ['id' => '__id__']), 'condition' => 'can_approve']])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['start_date'])) {
                $this->error('请选择开始日期');
            }

            if (empty($data['end_date'])) {
                $this->error('请选择结束日期');
            }

            $data['user_id'] = UID;
            $data['user_name'] = get_nickname(UID);
            $data['status'] = 0;

            try {
                UserLeaveModel::create($data);
                if ($this->request->isAjax()) {
                    return json(['code' => 1, 'msg' => '申请成功', 'url' => url('index')]);
                }
                $this->success('申请成功', url('index'));
            } catch (\ErrorException $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }

        $type_list = UserLeaveModel::getTypeList();

        return ZBuilder::make('form')
            ->setPageTitle('新增请假')
            ->addFormItems([
                ['select', 'leave_type', '类型', '必填', $type_list, 1],
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

        $info = UserLeaveModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($info['status'] != 0) {
            $this->error('该申请已处理，无法修改');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['start_date'])) {
                $this->error('请选择开始日期');
            }

            if (empty($data['end_date'])) {
                $this->error('请选择结束日期');
            }

            try {
                UserLeaveModel::update($data);
                if ($this->request->isAjax()) {
                    return json(['code' => 1, 'msg' => '修改成功', 'url' => url('index')]);
                }
                $this->success('修改成功', url('index'));
            } catch (\ErrorException $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }

        $type_list = UserLeaveModel::getTypeList();

        return ZBuilder::make('form')
            ->setPageTitle('编辑请假')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'leave_type', '类型', '必填', $type_list],
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
            UserLeaveModel::destroy($ids);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '删除成功']);
            }
            $this->success('删除成功');
        } catch (\ErrorException $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
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
            UserLeaveModel::update([
                'id' => $id,
                'status' => 1,
                'approver_id' => UID,
                'approver_name' => get_nickname(UID),
                'approve_time' => date('Y-m-d H:i:s')
            ]);

            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '批准成功']);
            }
            $this->success('批准成功');
        } catch (\ErrorException $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
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
                'approver_name' => get_nickname(UID),
                'approve_time' => date('Y-m-d H:i:s')
            ]);

            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '拒绝成功']);
            }
            $this->success('拒绝成功');
        } catch (\ErrorException $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }
}