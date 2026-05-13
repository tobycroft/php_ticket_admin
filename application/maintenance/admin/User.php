<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\action\UserAction;
use app\user\model\User as UserModel;

class User extends Admin
{
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
                ['color', '颜色', 'color'],
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

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $result = $this->validate($data, 'app\user\validate\User');
            if(true !== $result) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $result]);
                }
                $this->error($result);
            }

            try {
                UserAction::add($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '新增成功', 'url' => url('index')]);
            }
            $this->success('新增成功', url('index'));
        }

        $role_list = UserAction::getMaintenanceRoles();

        return ZBuilder::make('form')
            ->setPageTitle('新增运维人员')
            ->addFormItems([
                ['text', 'username', '用户名', '必填，可由英文字母、数字组成'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['color', 'color', '颜色', '用于排班日历中标识该用户'],
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

    public function edit($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        if (!UserAction::checkAccess($id)) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '权限不足，没有可操作的用户']);
            }
            $this->error('权限不足，没有可操作的用户');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            $result = $this->validate($data, 'app\user\validate\User.update');
            if(true !== $result) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $result]);
                }
                $this->error($result);
            }

            try {
                UserAction::edit($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '编辑成功', 'url' => cookie('__forward__')]);
            }
            $this->success('编辑成功', cookie('__forward__'));
        }

        $info = UserAction::getInfo($id);
        $role_list = UserAction::getMaintenanceRoles();

        return ZBuilder::make('form')
            ->setPageTitle('编辑运维人员')
            ->addFormItems([
                ['hidden', 'id'],
                ['static', 'username', '用户名', '不可更改'],
                ['text', 'nickname', '昵称', '可以是中文'],
                ['color', 'color', '颜色', '用于排班日历中标识该用户'],
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

        try {
            UserAction::setStatus($type, $ids);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '操作成功']);
        }
        $this->success('操作成功');
    }

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