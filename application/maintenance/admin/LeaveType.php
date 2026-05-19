<?php

namespace app\maintenance\admin;

use app\common\builder\ZBuilder;
use app\maintenance\model\LeaveTypeModel;
use app\admin\controller\Admin;
use app\user\model\User as UserModel;

class LeaveType extends Admin
{
    /**
     * 检查是否为运维主管或超级管理员
     */
    private function checkPermission()
    {
        $user = UserModel::get(UID);
        if ($user['role'] != 1 && $user['role'] != 4) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '权限不足，只有运维主管可以访问']);
            }
            $this->error('权限不足，只有运维主管可以访问');
        }
        return true;
    }

    public function index()
    {
        $this->checkPermission();
        
        $data_list = LeaveTypeModel::order('sort', 'asc')->select();

        return ZBuilder::make('table')
            ->setPageTitle('请假类型配置')
            ->addColumns([
                ['id', 'ID'],
                ['name', '类型名称'],
                ['sort', '排序'],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间', 'datetime'],
            ])
            ->addTopButtons('add,delete')
            ->addRightButtons('edit,delete')
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        $this->checkPermission();
        
        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (empty($data['name'])) {
                $this->error('请输入类型名称');
            }

            if (LeaveTypeModel::create($data)) {
                $this->success('添加成功', url('index'));
            } else {
                $this->error('添加失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增请假类型')
            ->addFormItems([
                ['text', 'name', '类型名称', '必填'],
                ['number', 'sort', '排序', '', 100],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1],
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        $this->checkPermission();
        
        if ($id === null) {
            $this->error('缺少参数');
        }

        $info = LeaveTypeModel::get($id);
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();
            if (empty($data['name'])) {
                $this->error('请输入类型名称');
            }

            if (LeaveTypeModel::update($data)) {
                $this->success('修改成功', url('index'));
            } else {
                $this->error('修改失败');
            }
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑请假类型')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '类型名称', '必填'],
                ['number', 'sort', '排序'],
                ['radio', 'status', '状态', '', ['禁用', '启用']],
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = null)
    {
        $this->checkPermission();
        
        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }

        if (LeaveTypeModel::destroy($ids)) {
            $this->success('删除成功');
        } else {
            $this->error('删除失败');
        }
    }
}