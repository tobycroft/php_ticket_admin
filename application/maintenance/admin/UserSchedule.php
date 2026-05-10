<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\UserScheduleModel;
use app\user\model\Role as RoleModel;
use app\user\model\User as UserModel;

class UserSchedule extends Admin
{
    private function getMaintenanceRoleIds()
    {
        return RoleModel::where('name', 'like', '%运维%')
            ->whereOr('name', 'like', '%维护%')
            ->column('id');
    }

    private function getMaintenanceUsers()
    {
        $role_ids = $this->getMaintenanceRoleIds();
        if (empty($role_ids)) {
            return [];
        }
        return UserModel::where('role', 'in', $role_ids)
            ->where('status', 1)
            ->column('id,nickname');
    }

    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = UserScheduleModel::where($map)->order('user_id, day_of_week, start_time')->paginate();

        $user_list = $this->getMaintenanceUsers();
        $day_list = UserScheduleModel::getDayOfWeekList();
        $status_list = UserScheduleModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['user_name'] = isset($user_list[$item['user_id']]) ? $user_list[$item['user_id']] : '未知用户';
            $item['day_text'] = isset($day_list[$item['day_of_week']]) ? $day_list[$item['day_of_week']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('运维人员排班')
            ->setTableName('mt_user_schedule')
            ->setSearch(['user_id' => '用户ID', 'description' => '备注'])
            ->addColumns([
                ['id', 'ID'],
                ['user_name', '运维人员'],
                ['day_text', '星期'],
                ['start_time', '开始时间'],
                ['end_time', '结束时间'],
                ['description', '备注'],
                ['status', '状态', 'switch', '', $status_list],
                ['create_time', '创建时间', 'datetime'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')
            ->addRightButtons('edit,delete')
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (UserScheduleModel::create($data)) {
                action_log('user_schedule_add', 'mt_user_schedule', '', UID);
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $user_list = $this->getMaintenanceUsers();
        $day_list = UserScheduleModel::getDayOfWeekList();

        return ZBuilder::make('form')
            ->setPageTitle('新增排班')
            ->addFormItems([
                ['select', 'user_id', '运维人员', '必填', $user_list],
                ['select', 'day_of_week', '星期', '必填', $day_list],
                ['time', 'start_time', '开始时间', '必填'],
                ['time', 'end_time', '结束时间', '必填'],
                ['textarea', 'description', '备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (UserScheduleModel::update($data)) {
                action_log('user_schedule_edit', 'mt_user_schedule', $id, UID);
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = UserScheduleModel::where('id', $id)->find();
        $user_list = $this->getMaintenanceUsers();
        $day_list = UserScheduleModel::getDayOfWeekList();

        return ZBuilder::make('form')
            ->setPageTitle('编辑排班')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'user_id', '运维人员', '必填', $user_list],
                ['select', 'day_of_week', '星期', '必填', $day_list],
                ['time', 'start_time', '开始时间', '必填'],
                ['time', 'end_time', '结束时间', '必填'],
                ['textarea', 'description', '备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
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

        if (!$ids) {
            $this->error('请选择要操作的数据');
        }

        switch ($type) {
            case 'enable':
                if (false === UserScheduleModel::where('id', 'in', $ids)->setField('status', 1)) {
                    $this->error('启用失败');
                }
                break;
            case 'disable':
                if (false === UserScheduleModel::where('id', 'in', $ids)->setField('status', 0)) {
                    $this->error('禁用失败');
                }
                break;
            case 'delete':
                if (false === UserScheduleModel::where('id', 'in', $ids)->delete()) {
                    $this->error('删除失败');
                }
                break;
            default:
                $this->error('非法操作');
        }

        action_log('user_schedule_'.$type, 'mt_user_schedule', '', UID);
        $this->success('操作成功');
    }

    public function quickEdit($record = [])
    {
        $id    = input('post.pk', '');
        $field = input('post.name', '');
        $value = input('post.value', '');

        $config  = UserScheduleModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $config . ')，新值：(' . $value . ')';
        return parent::quickEdit(['user_schedule_edit', 'mt_user_schedule', $id, UID, $details]);
    }
}