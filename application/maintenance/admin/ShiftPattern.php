<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\ShiftPatternModel;

class ShiftPattern extends Admin
{
    public function index()
    {
        $map = $this->getMap();
        $data_list = ShiftPatternModel::where($map)->order('id')->paginate();

        $status_list = ShiftPatternModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
            $item['color_preview'] = '<span style="display:inline-block;width:20px;height:20px;border-radius:3px;background-color:' . $item['color'] . '"></span>';
        }

        return ZBuilder::make('table')
            ->setPageTitle('班次管理')
            ->setTableName('mt_shift_pattern')
            ->setSearch(['name' => '班次名称'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '班次名称'],
                ['start_time', '开始时间'],
                ['end_time', '结束时间'],
                ['color_preview', '颜色标识'],
                ['description', '描述'],
                ['status_text', '状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButtons(['edit', 'delete'])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['name'])) {
                $this->error('请输入班次名称');
            }

            if (empty($data['start_time'])) {
                $this->error('请输入开始时间');
            }

            if (empty($data['end_time'])) {
                $this->error('请输入结束时间');
            }

            try {
                ShiftPatternModel::create($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '创建成功', 'url' => url('index')]);
            }
            $this->success('创建成功', url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增班次')
            ->addFormItems([
                ['text', 'name', '班次名称', '必填'],
                ['time', 'start_time', '开始时间', '必填'],
                ['time', 'end_time', '结束时间', '必填'],
                ['colorpicker', 'color', '颜色标识', '', '#337ab7'],
                ['textarea', 'description', '描述'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = ShiftPatternModel::where('id', $id)->find();
        if (!$info) {
            $this->error('班次不存在');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['name'])) {
                $this->error('请输入班次名称');
            }

            if (empty($data['start_time'])) {
                $this->error('请输入开始时间');
            }

            if (empty($data['end_time'])) {
                $this->error('请输入结束时间');
            }

            try {
                ShiftPatternModel::update($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '修改成功', 'url' => url('index')]);
            }
            $this->success('修改成功', url('index'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('编辑班次')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '班次名称', '必填'],
                ['time', 'start_time', '开始时间', '必填'],
                ['time', 'end_time', '结束时间', '必填'],
                ['colorpicker', 'color', '颜色标识'],
                ['textarea', 'description', '描述'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = null)
    {
        if (empty($ids)) {
            $this->error('请选择要删除的班次');
        }

        try {
            ShiftPatternModel::destroy($ids);
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
        
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '删除成功']);
        }
        $this->success('删除成功');
    }
}