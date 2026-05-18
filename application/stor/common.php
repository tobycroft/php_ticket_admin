<?php

namespace app\stor\common;

use app\admin\model\Action;

class StorAction
{
    public static function registerActions()
    {
        $actions = [
            // 分类管理
            ['module' => 'stor', 'name' => 'category_add', 'title' => '添加分类', 'remark' => '添加分类', 'log' => '[user|get_nickname] 添加了分类：[details]'],
            ['module' => 'stor', 'name' => 'category_edit', 'title' => '编辑分类', 'remark' => '编辑分类', 'log' => '[user|get_nickname] 编辑了分类：[details]'],
            ['module' => 'stor', 'name' => 'category_scrap', 'title' => '作废分类', 'remark' => '作废分类', 'log' => '[user|get_nickname] 作废了分类：[details]'],
            ['module' => 'stor', 'name' => 'category_enable', 'title' => '启用分类', 'remark' => '启用分类', 'log' => '[user|get_nickname] 启用了分类：[details]'],
            ['module' => 'stor', 'name' => 'category_disable', 'title' => '禁用分类', 'remark' => '禁用分类', 'log' => '[user|get_nickname] 禁用了分类：[details]'],
            
            // 物料管理
            ['module' => 'stor', 'name' => 'material_add', 'title' => '添加物料', 'remark' => '添加物料', 'log' => '[user|get_nickname] 添加了物料：[details]'],
            ['module' => 'stor', 'name' => 'material_edit', 'title' => '编辑物料', 'remark' => '编辑物料', 'log' => '[user|get_nickname] 编辑了物料：[details]'],
            ['module' => 'stor', 'name' => 'material_scrap', 'title' => '作废物料', 'remark' => '作废物料', 'log' => '[user|get_nickname] 作废了物料：[details]'],
            ['module' => 'stor', 'name' => 'material_enable', 'title' => '启用物料', 'remark' => '启用物料', 'log' => '[user|get_nickname] 启用了物料：[details]'],
            ['module' => 'stor', 'name' => 'material_disable', 'title' => '禁用物料', 'remark' => '禁用物料', 'log' => '[user|get_nickname] 禁用了物料：[details]'],
            
            // 物料SN管理
            ['module' => 'stor', 'name' => 'material_sn_add', 'title' => '添加物料SN', 'remark' => '添加物料SN', 'log' => '[user|get_nickname] 添加了物料SN：[details]'],
            ['module' => 'stor', 'name' => 'material_sn_edit', 'title' => '编辑物料SN', 'remark' => '编辑物料SN', 'log' => '[user|get_nickname] 编辑了物料SN：[details]'],
            ['module' => 'stor', 'name' => 'material_sn_scrap', 'title' => '作废物料SN', 'remark' => '作废物料SN', 'log' => '[user|get_nickname] 作废了物料SN：[details]'],
            ['module' => 'stor', 'name' => 'material_sn_allocate', 'title' => '分配物料SN', 'remark' => '分配物料SN', 'log' => '[user|get_nickname] 分配了物料SN：[details]'],
            
            // 入库管理
            ['module' => 'stor', 'name' => 'inbound_add', 'title' => '添加入库单', 'remark' => '添加入库单', 'log' => '[user|get_nickname] 添加了入库单：[details]'],
            ['module' => 'stor', 'name' => 'inbound_edit', 'title' => '编辑入库单', 'remark' => '编辑入库单', 'log' => '[user|get_nickname] 编辑了入库单：[details]'],
            
            // 出库管理
            ['module' => 'stor', 'name' => 'outbound_add', 'title' => '添加出库单', 'remark' => '添加出库单', 'log' => '[user|get_nickname] 添加了出库单：[details]'],
            ['module' => 'stor', 'name' => 'outbound_edit', 'title' => '编辑出库单', 'remark' => '编辑出库单', 'log' => '[user|get_nickname] 编辑了出库单：[details]'],
            
            // 维修管理
            ['module' => 'stor', 'name' => 'repair_add', 'title' => '添加维修单', 'remark' => '添加维修单', 'log' => '[user|get_nickname] 添加了维修单：[details]'],
            ['module' => 'stor', 'name' => 'repair_edit', 'title' => '编辑维修单', 'remark' => '编辑维修单', 'log' => '[user|get_nickname] 编辑了维修单：[details]'],
            ['module' => 'stor', 'name' => 'repair_complete', 'title' => '维修完成', 'remark' => '维修完成', 'log' => '[user|get_nickname] 完成了维修单：[details]'],
            ['module' => 'stor', 'name' => 'repair_scrap', 'title' => '维修作废', 'remark' => '维修作废', 'log' => '[user|get_nickname] 作废了维修单：[details]'],
            
            // 项目管理
            ['module' => 'stor', 'name' => 'project_add', 'title' => '添加项目', 'remark' => '添加项目', 'log' => '[user|get_nickname] 添加了项目：[details]'],
            ['module' => 'stor', 'name' => 'project_edit', 'title' => '编辑项目', 'remark' => '编辑项目', 'log' => '[user|get_nickname] 编辑了项目：[details]'],
            ['module' => 'stor', 'name' => 'project_delete', 'title' => '删除项目', 'remark' => '删除项目', 'log' => '[user|get_nickname] 删除了项目：[details]'],
        ];
        
        foreach ($actions as $action) {
            $exist = Action::where('module', $action['module'])->where('name', $action['name'])->find();
            if (!$exist) {
                Action::create([
                    'module' => $action['module'],
                    'name' => $action['name'],
                    'title' => $action['title'],
                    'remark' => $action['remark'],
                    'type' => '',
                    'log' => $action['log'],
                    'status' => 1,
                ]);
            }
        }
    }
}
