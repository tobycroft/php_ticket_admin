<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\InboundTypeModel;
use app\stor\model\OutboundTypeModel;

class InboundOutboundType extends Admin
{
    public function inboundTypeIndex()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = InboundTypeModel::getList($map);

        return ZBuilder::make('table')
            ->setPageTitle('入库类型管理')
            ->setTableName('stor_inbound_type')
            ->setSearch(['name' => '类型名称'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '类型名称'],
                ['remark', '备注'],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')
            ->addRightButtons('edit,delete')
            ->setRowList($data_list)
            ->fetch();
    }

    public function inboundTypeAdd()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                InboundTypeModel::add($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '新增成功', 'url' => url('inboundTypeIndex')]);
            }
            $this->success('新增成功', url('inboundTypeIndex'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增入库类型')
            ->addFormItems([
                ['text', 'name', '类型名称', '必填'],
                ['textarea', 'remark', '备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }

    public function inboundTypeEdit($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                InboundTypeModel::edit($data);
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

        $info = InboundTypeModel::getInfo($id);

        return ZBuilder::make('form')
            ->setPageTitle('编辑入库类型')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '类型名称', '必填'],
                ['textarea', 'remark', '备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function inboundTypeDelete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                InboundTypeModel::deleteById($id);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '删除成功']);
        }
        $this->success('删除成功');
    }

    public function outboundTypeIndex()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = $this->getMap();

        $data_list = OutboundTypeModel::getList($map);

        return ZBuilder::make('table')
            ->setPageTitle('出库类型管理')
            ->setTableName('stor_outbound_type')
            ->setSearch(['name' => '类型名称'])
            ->addColumns([
                ['id', 'ID'],
                ['name', '类型名称'],
                ['remark', '备注'],
                ['status', '状态', 'switch'],
                ['create_time', '创建时间'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add,enable,disable,delete')
            ->addRightButtons('edit,delete')
            ->setRowList($data_list)
            ->fetch();
    }

    public function outboundTypeAdd()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                OutboundTypeModel::add($data);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
            
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '新增成功', 'url' => url('outboundTypeIndex')]);
            }
            $this->success('新增成功', url('outboundTypeIndex'));
        }

        return ZBuilder::make('form')
            ->setPageTitle('新增出库类型')
            ->addFormItems([
                ['text', 'name', '类型名称', '必填'],
                ['textarea', 'remark', '备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用'], 1]
            ])
            ->fetch();
    }

    public function outboundTypeEdit($id = null)
    {
        if ($id === null) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => '缺少参数']);
            }
            $this->error('缺少参数');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            try {
                OutboundTypeModel::edit($data);
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

        $info = OutboundTypeModel::getInfo($id);

        return ZBuilder::make('form')
            ->setPageTitle('编辑出库类型')
            ->addFormItems([
                ['hidden', 'id'],
                ['text', 'name', '类型名称', '必填'],
                ['textarea', 'remark', '备注'],
                ['radio', 'status', '状态', '', ['禁用', '启用']]
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function outboundTypeDelete($ids = [])
    {
        $ids = (array)$ids;
        foreach ($ids as $id) {
            try {
                OutboundTypeModel::deleteById($id);
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }
        if ($this->request->isAjax()) {
            return json(['code' => 1, 'msg' => '删除成功']);
        }
        $this->success('删除成功');
    }

    public function inboundTypeEnable($ids = [])
    {
        return $this->setInboundStatus('enable');
    }

    public function inboundTypeDisable($ids = [])
    {
        return $this->setInboundStatus('disable');
    }

    private function setInboundStatus($type = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        try {
            InboundTypeModel::setStatus($type, $ids);
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

    public function outboundTypeEnable($ids = [])
    {
        return $this->setOutboundStatus('enable');
    }

    public function outboundTypeDisable($ids = [])
    {
        return $this->setOutboundStatus('disable');
    }

    private function setOutboundStatus($type = '')
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;

        try {
            OutboundTypeModel::setStatus($type, $ids);
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
}