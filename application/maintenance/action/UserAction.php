<?php

namespace app\maintenance\action;

use app\user\model\Role as RoleModel;
use app\user\model\User as UserModel;
use think\facade\Hook;

/**
 * 运维人员操作类
 * @package app\maintenance\action
 */
class UserAction
{
    /**
     * 获取运维组父角色ID
     * @return int|false
     */
    private static function getMaintenanceGroupId()
    {
        return RoleModel::where('name', '运维组')
            ->whereOr('name', '运行维护组')
            ->value('id');
    }

    /**
     * 获取运维组及其所有子角色ID列表
     * @return array
     */
    public static function getMaintenanceRoleIds()
    {
        $group_id = self::getMaintenanceGroupId();
        
        if ($group_id) {
            return array_merge([$group_id], RoleModel::getChildsId($group_id));
        }
        
        return RoleModel::where('name', 'like', '%运维%')
            ->whereOr('name', 'like', '%维护%')
            ->column('id');
    }

    /**
     * 获取运维组及其所有子角色列表（树形显示）
     * @return array
     */
    public static function getMaintenanceRoles()
    {
        $role_ids = self::getMaintenanceRoleIds();
        
        if (empty($role_ids)) {
            return [];
        }

        $roles = RoleModel::where('id', 'in', $role_ids)
            ->column('id,pid,name');

        $tree_config = ['title' => 'name', 'id' => 'id', 'pid' => 'pid'];
        $roles = \util\Tree::config($tree_config)->toList($roles);
        
        $result = [];
        foreach ($roles as $role) {
            $result[$role['id']] = $role['title_display'];
        }
        
        return $result;
    }

    /**
     * 获取运维部门用户列表
     * @param array $map 查询条件
     * @return \think\Paginator
     */
    public static function getList($map = [])
    {
        $role_ids = self::getMaintenanceRoleIds();
        
        if (!empty($role_ids)) {
            $map[] = ['role', 'in', $role_ids];
        } else {
            $map[] = ['role', '=', 0];
        }

        return UserModel::where($map)->order('sort,role,id desc')->paginate();
    }

    /**
     * 新增运维人员
     * @param array $data 用户数据
     * @return bool
     */
    public static function add($data)
    {
        $role_ids = self::getMaintenanceRoleIds();
        
        if (!in_array($data['role'], $role_ids)) {
            throw new \Exception('权限不足，禁止创建非法角色的用户');
        }

        if (isset($data['roles'])) {
            $deny_role = array_diff($data['roles'], $role_ids);
            if ($deny_role) {
                throw new \Exception('权限不足，附加角色设置错误');
            }
        }

        $data['roles'] = isset($data['roles']) ? implode(',', $data['roles']) : '';

        if ($user = UserModel::create($data)) {
            Hook::listen('user_add', $user);
            action_log('user_add', 'admin_user', $user['id'], UID);
            return true;
        }
        
        throw new \Exception('新增失败');
    }

    /**
     * 编辑运维人员
     * @param array $data 用户数据
     * @return bool
     */
    public static function edit($data)
    {
        if ($data['id'] == 1 && $data['role'] != 1) {
            throw new \Exception('禁止修改超级管理员角色');
        }

        if ($data['id'] == 1 && $data['status'] != 1) {
            throw new \Exception('禁止修改超级管理员状态');
        }

        if ($data['password'] == '') {
            unset($data['password']);
        }

        $role_ids = self::getMaintenanceRoleIds();
        
        if (!in_array($data['role'], $role_ids)) {
            throw new \Exception('权限不足，禁止修改为非法角色的用户');
        }

        if (isset($data['roles'])) {
            $deny_role = array_diff($data['roles'], $role_ids);
            if ($deny_role) {
                throw new \Exception('权限不足，附加角色设置错误');
            }
        }

        $data['roles'] = isset($data['roles']) ? implode(',', $data['roles']) : '';

        if (UserModel::update($data)) {
            $user = UserModel::get($data['id']);
            Hook::listen('user_edit', $user);
            action_log('user_edit', 'admin_user', $user['id'], UID, get_nickname($user['id']));
            return true;
        }
        
        throw new \Exception('编辑失败');
    }

    /**
     * 检查是否为可操作的运维用户
     * @param int $id 用户ID
     * @return bool
     */
    public static function checkAccess($id)
    {
        $role_ids = self::getMaintenanceRoleIds();
        $user_list = UserModel::where('role', 'in', $role_ids)->column('id');
        return in_array($id, $user_list);
    }

    /**
     * 设置用户状态
     * @param string $type 类型：delete/enable/disable
     * @param array $ids 用户ID列表
     * @return bool
     */
    public static function setStatus($type, $ids)
    {
        $role_ids = self::getMaintenanceRoleIds();
        $user_list = UserModel::where('role', 'in', $role_ids)->column('id');
        
        if (!empty($user_list)) {
            $ids = array_intersect($user_list, $ids);
        }
        
        if (!$ids) {
            throw new \Exception('权限不足，没有可操作的用户');
        }

        switch ($type) {
            case 'enable':
                if (false === UserModel::where('id', 'in', $ids)->setField('status', 1)) {
                    throw new \Exception('启用失败');
                }
                break;
            case 'disable':
                if (false === UserModel::where('id', 'in', $ids)->setField('status', 0)) {
                    throw new \Exception('禁用失败');
                }
                break;
            case 'delete':
                if (false === UserModel::where('id', 'in', $ids)->delete()) {
                    throw new \Exception('删除失败');
                }
                break;
            default:
                throw new \Exception('非法操作');
        }

        action_log('user_'.$type, 'admin_user', '', UID);
        return true;
    }

    /**
     * 获取用户信息
     * @param int $id 用户ID
     * @return array
     */
    public static function getInfo($id)
    {
        return UserModel::where('id', $id)->field('password', true)->find();
    }
}