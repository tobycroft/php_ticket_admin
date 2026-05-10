<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\user\model\Role as RoleModel;
use app\user\model\User as UserModel;
use think\Db;
use think\facade\Hook;

/**
 * 运维人员管理
 * @package app\maintenance\admin
 */
class User extends Admin
{
    /**
     * 获取运维相关角色列表
     * @return array
     */
    private function getMaintenanceRoles()
    {
        return RoleModel::where('name', 'like', '%运维%')
            ->whereOr('name', 'like', '%维护%')
            ->column('id,name');
    }

    /**
     * 获取运维相关角色ID列表
     * @return array
     */
    private function getMaintenanceRoleIds()
    {
        return RoleModel::where('name', 'like', '%运维%')
            ->whereOr('name', 'like', '%维护%')
            ->column('id');
    }

    /**
     * 用户首页
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();
        $role_ids = $this->getMaintenanceRoleIds();

        if (!empty($role_ids)) {
            $map[] = ['role', 'in', $role_ids];
        } else {
            $map[] = ['role', '=', 0];
        }

        $data_list = UserModel::where($map)->order('sort,role,id desc')->paginate();

        $role_list = $this->getMaintenanceRoles();

        return ZBuilder::make('table')
            ->setPageTitle('运维人员')
            ->setTableName('admin_user')
            ->setSearch(['id' => 'ID', 'username' => '用户名', 'email' => '邮箱', 'nickname' => '昵称'])
            ->addColumns([
                ['id', 'ID'],
                ['username', '用户名'],
                ['nickname', '昵称'],
                ['role', '角色', $role_list],
                ['email', '邮箱'],
                ['mobile', '手机号'],
                ['create_time', '创建时间', 'datetime'],
                ['status', '状态', 'switch'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')
            ->addRightButtons('edit,delete')
            ->setRowList($data_list)
            ->fetch();
    }

    /**
     * 新增
     * @return mixed
     * @throws \think\Exception
     */
    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $result = $this->validate($data, 'app\user\validate\User');
            if(true !== $result) $this->error($result);

            $role_ids = $this->getMaintenanceRoleIds();
            if (!in_array($data['role'], $role_ids)) {
                $this->error('权限不足，禁止创建非法角色的用户');
            }

            if (isset($data['roles'])) {
                $deny_role = array_diff($data['roles'], $role_ids);
                if ($deny_role) {
                    $this->error('权限不足，附加角色设置错误');
                }
            }

            $data['roles'] = isset($data['roles']) ? implode(',', $data['roles']) : '';

            if ($user = UserModel::create($data)) {
                Hook::listen('user_add', $user);
                action_log('user_add', 'admin_user', $user['id'], UID);
                $this->success('新增成功', url('index'));
            } else {
                $this->error('新增失败');
            }
        }

        $role_list = $this->getMaintenanceRoles();

        return ZBuilder::make('form')
            ->setPageTitle('新增运维人员')
            ->addFormItems([
                ['text', 'username', '用户名', '必填，可由英文字母、数字组成'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['select', 'role', '主角色', '', $role_list],
                ['select', 'roles', '副角色', '可多选', $role_list, '', 'multiple'],
                ['text', 'email', '邮箱', ''],
                ['password', 'password', '密码', '必填，6-20位'],
                ['text', 'mobile', '手机号'],
                ['image', 'avatar', '头像'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }

    /**
     * 编辑
     * @param null $id 用户id
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function edit($id = null)
    {
        if ($id === null) $this->error('缺少参数');

        $role_ids = $this->getMaintenanceRoleIds();
        $user_list = UserModel::where('role', 'in', $role_ids)->column('id');
        if (!in_array($id, $user_list)) {
            $this->error('权限不足，没有可操作的用户');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if ($data['id'] == 1 && $data['role'] != 1) {
                $this->error('禁止修改超级管理员角色');
            }

            if ($data['id'] == 1 && $data['status'] != 1) {
                $this->error('禁止修改超级管理员状态');
            }

            $result = $this->validate($data, 'app\user\validate\User.update');
            if(true !== $result) $this->error($result);

            if ($data['password'] == '') {
                unset($data['password']);
            }

            if (!in_array($data['role'], $role_ids)) {
                $this->error('权限不足，禁止修改为非法角色的用户');
            }

            if (isset($data['roles'])) {
                $deny_role = array_diff($data['roles'], $role_ids);
                if ($deny_role) {
                    $this->error('权限不足，附加角色设置错误');
                }
            }

            $data['roles'] = isset($data['roles']) ? implode(',', $data['roles']) : '';

            if (UserModel::update($data)) {
                $user = UserModel::get($data['id']);
                Hook::listen('user_edit', $user);
                action_log('user_edit', 'admin_user', $user['id'], UID, get_nickname($user['id']));
                $this->success('编辑成功', cookie('__forward__'));
            } else {
                $this->error('编辑失败');
            }
        }

        $info = UserModel::where('id', $id)->field('password', true)->find();
        $role_list = $this->getMaintenanceRoles();

        return ZBuilder::make('form')
            ->setPageTitle('编辑运维人员')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'username', '用户名', '不可更改'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['select', 'role', '主角色', '', $role_list],
                ['select', 'roles', '副角色', '可多选', $role_list, '', 'multiple'],
                ['text', 'email', '邮箱', ''],
                ['password', 'password', '密码', '必填，6-20位'],
                ['text', 'mobile', '手机号'],
                ['image', 'avatar', '头像'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 删除用户
     * @param array $ids 用户id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($ids = [])
    {
        Hook::listen('user_delete', $ids);
        return $this->setStatus('delete');
    }

    /**
     * 启用用户
     * @param array $ids 用户id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($ids = [])
    {
        Hook::listen('user_enable', $ids);
        return $this->setStatus('enable');
    }

    /**
     * 禁用用户
     * @param array $ids 用户id
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function disable($ids = [])
    {
        Hook::listen('user_disable', $ids);
        return $this->setStatus('disable');
    }

    /**
     * 设置用户状态：删除、禁用、启用
     * @param string $type 类型：delete/enable/disable
     * @param array $record
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        $role_ids = $this->getMaintenanceRoleIds();
        $user_list = UserModel::where('role', 'in', $role_ids)->column('id');

        if (!empty($user_list)) {
            $ids = array_intersect($user_list, $ids);
        }

        if (!$ids) {
            $this->error('权限不足，没有可操作的用户');
        }

        switch ($type) {
            case 'enable':
                if (false === UserModel::where('id', 'in', $ids)->setField('status', 1)) {
                    $this->error('启用失败');
                }
                break;
            case 'disable':
                if (false === UserModel::where('id', 'in', $ids)->setField('status', 0)) {
                    $this->error('禁用失败');
                }
                break;
            case 'delete':
                if (false === UserModel::where('id', 'in', $ids)->delete()) {
                    $this->error('删除失败');
                }
                break;
            default:
                $this->error('非法操作');
        }

        action_log('user_'.$type, 'admin_user', '', UID);
        $this->success('操作成功');
    }

    /**
     * 快速编辑
     * @param array $record 行为日志
     * @return mixed
     */
    public function quickEdit($record = [])
    {
        $id    = input('post.pk', '');
        $id == UID && $this->error('禁止操作当前账号');
        $field = input('post.name', '');
        $value = input('post.value', '');

        $role_ids = $this->getMaintenanceRoleIds();
        $user_list = UserModel::where('role', 'in', $role_ids)->column('id');
        if (!in_array($id, $user_list)) {
            $this->error('权限不足，没有可操作的用户');
        }

        $config  = UserModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $config . ')，新值：(' . $value . ')';
        return parent::quickEdit(['user_edit', 'admin_user', $id, UID, $details]);
    }
}