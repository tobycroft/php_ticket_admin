<?php

namespace app\maintenance\action;

use app\maintenance\model\UserScheduleModel;
use app\user\model\Role as RoleModel;
use app\user\model\User as UserModel;

class UserScheduleAction
{
    public static function getMaintenanceRoleIds()
    {
        return RoleModel::where('name', 'like', '%运维%')
            ->whereOr('name', 'like', '%维护%')
            ->column('id');
    }

    public static function getMaintenanceUsers()
    {
        $role_ids = self::getMaintenanceRoleIds();
        if (empty($role_ids)) {
            return [];
        }
        return UserModel::where('role', 'in', $role_ids)
            ->where('status', 1)
            ->column('id,nickname');
    }

    public static function getDayOfWeekList()
    {
        return [
            1 => '周一',
            2 => '周二',
            3 => '周三',
            4 => '周四',
            5 => '周五',
            6 => '周六',
            7 => '周日',
        ];
    }

    public static function getStatusList()
    {
        return [
            0 => '禁用',
            1 => '启用',
        ];
    }

    public static function getList($map = [])
    {
        return UserScheduleModel::where($map)->order('user_id, day_of_week, start_time')->paginate();
    }

    public static function add($data)
    {
        if (UserScheduleModel::create($data)) {
            action_log('user_schedule_add', 'mt_user_schedule', '', UID);
            return true;
        }
        
        throw new \Exception('新增失败');
    }

    public static function edit($data)
    {
        if (UserScheduleModel::update($data)) {
            action_log('user_schedule_edit', 'mt_user_schedule', $data['id'], UID);
            return true;
        }
        
        throw new \Exception('编辑失败');
    }

    public static function setStatus($type, $ids)
    {
        $ids = (array)$ids;

        if (!$ids) {
            throw new \Exception('请选择要操作的数据');
        }

        switch ($type) {
            case 'enable':
                if (false === UserScheduleModel::where('id', 'in', $ids)->setField('status', 1)) {
                    throw new \Exception('启用失败');
                }
                break;
            case 'disable':
                if (false === UserScheduleModel::where('id', 'in', $ids)->setField('status', 0)) {
                    throw new \Exception('禁用失败');
                }
                break;
            case 'delete':
                if (false === UserScheduleModel::where('id', 'in', $ids)->delete()) {
                    throw new \Exception('删除失败');
                }
                break;
            default:
                throw new \Exception('非法操作');
        }

        action_log('user_schedule_'.$type, 'mt_user_schedule', '', UID);
        return true;
    }

    public static function getInfo($id)
    {
        return UserScheduleModel::where('id', $id)->find();
    }
}