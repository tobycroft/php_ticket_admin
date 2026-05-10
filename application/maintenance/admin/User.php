<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\UserAction;
use app\user\model\User as UserModel;
use think\facade\Hook;

/**
 * 运维人员管理
 * @package app\maintenance\admin
 */
class User extends Admin
{
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
        $data_list = UserAction::getList($map);

        $role_list = UserAction::getMaintenanceRoles();

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

            $result = $this->validate($data, 'User');
            if(true !== $result) $this->error($result);

            try {
                UserAction::add($data);
                $this->success('新增成功', url('index'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $role_list = UserAction::getMaintenanceRoles();

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

        if (!UserAction::checkAccess($id)) {
            $this->error('权限不足，没有可操作的用户');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            $result = $this->validate($data, 'User.update');
            if(true !== $result) $this->error($result);

            try {
                UserAction::edit($data);
                $this->success('编辑成功', cookie('__forward__'));
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        $info = UserAction::getInfo($id);
        $role_list = UserAction::getMaintenanceRoles();

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

        try {
            UserAction::setStatus($type, $ids);
            $this->success('操作成功');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
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

        if (!UserAction::checkAccess($id)) {
            $this->error('权限不足，没有可操作的用户');
        }

        $config  = UserModel::where('id', $id)->value($field);
        $details = '字段(' . $field . ')，原值(' . $config . ')，新值：(' . $value . ')';
        return parent::quickEdit(['user_edit', 'admin_user', $id, UID, $details]);
    }
}