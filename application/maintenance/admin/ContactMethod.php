<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\ContactMethodModel;

class ContactMethod extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = ContactMethodModel::where($map)->order('sort asc, id asc')->paginate();

        return ZBuilder::make('table')
            ->setPageTitle('对接方式管理')
            ->setTableName('mt_contact_method')
            ->setSearch(['name' => '对接方式名称'])
            ->addTopButtons('add')
            ->addColumns([
                ['id', 'ID'],
                ['name', '对接方式名称'],
                ['description', '描述'],
                ['status', '状态', 'switch'],
                ['sort', '排序'],
                ['right_button', '操作', 'btn']
            ])
            ->addRightButtons(['edit', 'delete', 'enable', 'disable'])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['name'])) {
                $this->error('请输入对接方式名称');
            }

            if (!isset($data['status'])) {
                $data['status'] = 1;
            }

            try {
                ContactMethodModel::create($data);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            
            $this->success('添加成功', url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增对接方式')
            ->addFormItems([
                ['text', 'name', '对接方式名称', '必填'],
                ['textarea', 'description', '描述'],
                ['switch', 'status', '状态', '', 1],
                ['number', 'sort', '排序', '', 0]
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = ContactMethodModel::where('id', $id)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['name'])) {
                $this->error('请输入对接方式名称');
            }

            try {
                ContactMethodModel::update($data);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            
            $this->success('修改成功', url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑对接方式')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '对接方式名称', '必填'],
                ['textarea', 'description', '描述'],
                ['switch', 'status', '状态'],
                ['number', 'sort', '排序']
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
            ContactMethodModel::whereIn('id', $ids)->delete();
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('删除成功');
    }

    public function enable($ids = null)
    {
        if (empty($ids)) {
            $this->error('请选择要启用的记录');
        }

        try {
            ContactMethodModel::whereIn('id', $ids)->update(['status' => 1]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('启用成功');
    }

    public function disable($ids = null)
    {
        if (empty($ids)) {
            $this->error('请选择要禁用的记录');
        }

        try {
            ContactMethodModel::whereIn('id', $ids)->update(['status' => 0]);
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }
        
        $this->success('禁用成功');
    }
}