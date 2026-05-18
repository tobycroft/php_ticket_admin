<?php


namespace app\admin\controller;

use app\admin\model\Icon as IconModel;
use app\admin\model\Menu as MenuModel;
use app\admin\model\Module as ModuleModel;
use app\common\builder\ZBuilder;
use app\common\controller\Common;
use app\user\model\Message as MessageModel;
use app\user\model\Role as RoleModel;
use think\Db;
use think\facade\App;
use think\facade\Cache;
use think\helper\Hash;

/**
 * 后台公共控制器
 * @package app\admin\controller
 */
class Admin extends Common
{
    /**
     * 禁用
     * @param array $record 行为日志内容
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function disable($record = [])
    {
        return $this->setStatus('disable', $record);
    }

    /**
     * 设置状态
     * 禁用、启用、删除都是调用这个内部方法
     * @param string $type 操作类型：enable,disable,delete
     * @param array $record 行为日志内容
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function setStatus($type = '', $record = [])
    {
        $ids = $this->request->isPost() ? input('post.ids/a') : input('param.ids');
        $ids = (array)$ids;
        $field = input('param.field', 'status');

        empty($ids) && $this->error('缺少主键');

        $Model = $this->getCurrModel();
        $protect_table = [
            '__ADMIN_USER__',
            '__ADMIN_ROLE__',
            '__ADMIN_MODULE__',
            config('database.prefix') . 'admin_user',
            config('database.prefix') . 'admin_role',
            config('database.prefix') . 'admin_module',
        ];

        // 禁止操作核心表的主要数据
        if (in_array($Model->getTable(), $protect_table) && in_array('1', $ids)) {
            $this->error('禁止操作');
        }

        // 主键名称
        $pk = $Model->getPk();
        $map = [
            [$pk, 'in', $ids]
        ];

        $result = false;
        switch ($type) {
            case 'disable': // 禁用
                $result = $Model->where($map)
                    ->setField($field, 0);
                break;
            case 'enable': // 启用
                $result = $Model->where($map)
                    ->setField($field, 1);
                break;
            case 'delete': // 删除
                $result = $Model->where($map)
                    ->delete();
                break;
            default:
                $this->error('非法操作');
                break;
        }

        if (false !== $result) {
            Cache::clear();
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 获取当前操作模型
     * @return object|\think\db\Query
     */
    final protected function getCurrModel()
    {
        $table_token = input('param._t', '');
        $module = $this->request->module();
        $controller = parse_name($this->request->controller());

        $table_token == '' && $this->error('缺少参数');
        !session('?' . $table_token) && $this->error('参数错误');

        $table_data = session($table_token);
        $table = $table_data['table'];

        $Model = null;
        if ($table_data['prefix'] == 2) {
            // 使用模型
            try {
                $Model = App::model($table);
            } catch (\Exception $e) {
                $this->error('找不到模型：' . $table);
            }
        } else {
            // 使用DB类
            $table == '' && $this->error('缺少表名');
            if ($table_data['module'] != $module || $table_data['controller'] != $controller) {
                $this->error('非法操作');
            }

            $Model = $table_data['prefix'] == 0 ? Db::table($table) : Db::name($table);
        }

        return $Model;
    }

    /**
     * 启用
     * @param array $record 行为日志内容
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function delete($record = [])
    {
        return $this->setStatus('delete', $record);
    }

    /**
     * 启用
     * @param array $record 行为日志内容
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function enable($record = [])
    {
        return $this->setStatus('enable', $record);
    }

    /**
     * 快速编辑
     * @param array $record 行为日志内容
     */
    public function quickEdit($record = [])
    {
        $field = input('post.name', '');
        $value = input('post.value', '');
        $type = input('post.type', '');
        $id = input('post.pk', '');
        $validate = input('post.validate', '');
        $validate_fields = input('post.validate_fields', '');

        $field == '' && $this->error('缺少字段名');
        $id == '' && $this->error('缺少主键值');

        $Model = $this->getCurrModel();
        $protect_table = [
            '__ADMIN_USER__',
            '__ADMIN_ROLE__',
            config('database.prefix') . 'admin_user',
            config('database.prefix') . 'admin_role',
        ];

        // 验证是否操作管理员
        if (in_array($Model->getTable(), $protect_table) && $id == 1) {
            $this->error('禁止操作超级管理员');
        }

        // 验证器
        if ($validate != '') {
            $validate_fields = array_flip(explode(',', $validate_fields));
            if (isset($validate_fields[$field])) {
                $result = $this->validate([$field => $value], $validate . '.' . $field);
                if (true !== $result)
                    $this->error($result);
            }
        }

        switch ($type) {
            // 日期时间需要转为时间戳
            case 'combodate':
                $value = strtotime($value);
                break;
            // 开关
            case 'switch':
                $value = $value == 'true' ? 1 : 0;
                break;
            // 开关
            case 'password':
                $value = Hash::make((string)$value);
                break;
        }

        // 主键名
        $pk = $Model->getPk();
        $result = $Model->where($pk, $id)
            ->setField($field, $value);

        cache('hook_plugins', null);
        cache('system_config', null);
        cache('access_menus', null);
        if (false !== $result) {
            // 记录行为日志
            if (!empty($record)) {
                call_user_func_array('action_log', $record);
            }
            $this->success('操作成功');
        } else {
            $this->error('操作失败');
        }
    }

    /**
     * 自动创建添加页面
     * @return mixed
     * @throws \think\Exception
     */
    public function add()
    {
        // 获取表单项
        $cache_name = $this->request->module() . '/' . parse_name($this->request->controller()) . '/add';
        $cache_name = strtolower($cache_name);
        $form = Cache::get($cache_name, []);
        if (!$form) {
            $this->error('自动新增数据不存在，请重新打开此页面');
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $_pop = $this->request->get('_pop');

            // 验证
            if ($form['validate'] != '') {
                $result = $this->validate($data, $form['validate']);
                if (true !== $result)
                    $this->error($result);
            }

            // 是否需要自动插入时间
            if ($form['auto_time'] != '') {
                $now_time = $this->request->time();
                foreach ($form['auto_time'] as $item) {
                    if (strpos($item, '|')) {
                        list($item, $format) = explode('|', $item);
                        $data[$item] = date($format, $now_time);
                    } else {
                        $data[$item] = $form['format'] != '' ? date($form['format'], $now_time) : $now_time;
                    }
                }
            }

            // 插入数据
            if (Db::name($form['table'])
                ->insert($data)) {
                if ($_pop == 1) {
                    $this->success('新增成功', null, '_parent_reload');
                } else {
                    $this->success('新增成功', $form['go_back']);
                }
            } else {
                $this->error('新增失败');
            }
        }

        // 显示添加页面
        return ZBuilder::make('form')
            ->addFormItems($form['items'])
            ->fetch();
    }

    /**
     * 自动创建编辑页面
     * @param string $id 主键值
     * @return mixed
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @throws \think\exception\PDOException
     */
    public function edit($id = '')
    {
        if ($id === '')
            $this->error('参数错误');

        // 获取表单项
        $cache_name = $this->request->module() . '/' . parse_name($this->request->controller()) . '/edit';
        $cache_name = strtolower($cache_name);
        $form = Cache::get($cache_name, []);
        if (!$form) {
            $this->error('自动编辑数据不存在，请重新打开此页面');
        }

        // 保存数据
        if ($this->request->isPost()) {
            // 表单数据
            $data = $this->request->post();
            $_pop = $this->request->get('_pop');

            // 验证
            if ($form['validate'] != '') {
                $result = $this->validate($data, $form['validate']);
                if (true !== $result)
                    $this->error($result);
            }

            // 是否需要自动插入时间
            if ($form['auto_time'] != '') {
                $now_time = $this->request->time();
                foreach ($form['auto_time'] as $item) {
                    if (strpos($item, '|')) {
                        list($item, $format) = explode('|', $item);
                        $data[$item] = date($format, $now_time);
                    } else {
                        $data[$item] = $form['format'] != '' ? date($form['format'], $now_time) : $now_time;
                    }
                }
            }

            // 更新数据
            if (false !== Db::name($form['table'])
                    ->update($data)) {
                if ($_pop == 1) {
                    $this->success('编辑成功', null, '_parent_reload');
                } else {
                    $this->success('编辑成功', $form['go_back']);
                }
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = Db::name($form['table'])
            ->find($id);

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('编辑')
            ->addFormItems($form['items'])
            ->setFormData($info)
            ->fetch();
    }

    /**
     * 模块设置
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\PDOException
     */
    public function moduleConfig()
    {
        // 当前模块名
        $module = $this->request->module();

        // 保存
        if ($this->request->isPost()) {
            $data = $this->request->post();
            $data = json_encode($data);

            if (false !== ModuleModel::where('name', $module)
                    ->update(['config' => $data])) {
                cache('module_config_' . $module, null);
                $this->success('更新成功');
            } else {
                $this->error('更新失败');
            }
        }

        // 模块配置信息
        $module_info = ModuleModel::getInfoFromFile($module);
        $config = $module_info['config'];
        $trigger = isset($module_info['trigger']) ? $module_info['trigger'] : [];

        // 数据库内的模块信息
        $db_config = ModuleModel::where('name', $module)
            ->value('config');
        $db_config = json_decode($db_config, true);

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->setPageTitle('模块设置')
            ->addFormItems($config)
            ->setFormdata($db_config)
            ->setTrigger($trigger)
            ->fetch();
    }

    /**
     * 自动注册各模块的action到dp_admin_action表
     */
    protected static function registerModuleActions()
    {
        $actions = [
            // 运维模块 actions
            'maintenance' => [
                ['name' => 'event_add', 'title' => '添加工单', 'remark' => '添加工单', 'log' => '[user|get_nickname] 添加了工单：[details]'],
                ['name' => 'event_edit', 'title' => '编辑工单', 'remark' => '编辑工单', 'log' => '[user|get_nickname] 编辑了工单：[details]'],
                ['name' => 'event_delete', 'title' => '删除工单', 'remark' => '删除工单', 'log' => '[user|get_nickname] 删除了工单：[details]'],
                ['name' => 'event_receive', 'title' => '接单', 'remark' => '接单', 'log' => '[user|get_nickname] 接单了工单：[details]'],
                ['name' => 'event_close', 'title' => '关闭工单', 'remark' => '关闭工单', 'log' => '[user|get_nickname] 关闭了工单：[details]'],
                ['name' => 'event_reopen', 'title' => '重开工单', 'remark' => '重开工单', 'log' => '[user|get_nickname] 重开了工单：[details]'],
                ['name' => 'event_cancel', 'title' => '作废工单', 'remark' => '作废工单', 'log' => '[user|get_nickname] 作废了工单：[details]'],
                ['name' => 'event_active', 'title' => '激活工单', 'remark' => '激活工单', 'log' => '[user|get_nickname] 激活了工单：[details]'],
                ['name' => 'event_push', 'title' => '推送工单', 'remark' => '推送工单', 'log' => '[user|get_nickname] 推送了工单：[details]'],
                ['name' => 'event_note_add', 'title' => '添加工单备注', 'remark' => '添加工单备注', 'log' => '[user|get_nickname] 添加了工单备注：[details]'],
                ['name' => 'event_flow_handle', 'title' => '处理工单流程', 'remark' => '处理工单流程', 'log' => '[user|get_nickname] 处理了工单流程：[details]'],
                ['name' => 'user_add', 'title' => '添加用户', 'remark' => '添加用户', 'log' => '[user|get_nickname] 添加了用户：[details]'],
                ['name' => 'user_edit', 'title' => '编辑用户', 'remark' => '编辑用户', 'log' => '[user|get_nickname] 编辑了用户：[details]'],
                ['name' => 'user_delete', 'title' => '删除用户', 'remark' => '删除用户', 'log' => '[user|get_nickname] 删除了用户：[details]'],
                ['name' => 'user_enable', 'title' => '启用用户', 'remark' => '启用用户', 'log' => '[user|get_nickname] 启用了用户：[details]'],
                ['name' => 'user_disable', 'title' => '禁用用户', 'remark' => '禁用用户', 'log' => '[user|get_nickname] 禁用了用户：[details]'],
                ['name' => 'schedule_add', 'title' => '添加排班', 'remark' => '添加排班', 'log' => '[user|get_nickname] 添加了排班：[details]'],
                ['name' => 'schedule_edit', 'title' => '编辑排班', 'remark' => '编辑排班', 'log' => '[user|get_nickname] 编辑了排班：[details]'],
                ['name' => 'schedule_delete', 'title' => '删除排班', 'remark' => '删除排班', 'log' => '[user|get_nickname] 删除了排班：[details]'],
                ['name' => 'schedule_enable', 'title' => '启用排班', 'remark' => '启用排班', 'log' => '[user|get_nickname] 启用了排班：[details]'],
                ['name' => 'schedule_disable', 'title' => '禁用排班', 'remark' => '禁用排班', 'log' => '[user|get_nickname] 禁用了排班：[details]'],
                ['name' => 'shift_pattern_add', 'title' => '添加班次', 'remark' => '添加班次', 'log' => '[user|get_nickname] 添加了班次：[details]'],
                ['name' => 'shift_pattern_edit', 'title' => '编辑班次', 'remark' => '编辑班次', 'log' => '[user|get_nickname] 编辑了班次：[details]'],
                ['name' => 'shift_pattern_delete', 'title' => '删除班次', 'remark' => '删除班次', 'log' => '[user|get_nickname] 删除了班次：[details]'],
                ['name' => 'shift_pattern_enable', 'title' => '启用班次', 'remark' => '启用班次', 'log' => '[user|get_nickname] 启用了班次：[details]'],
                ['name' => 'shift_pattern_disable', 'title' => '禁用班次', 'remark' => '禁用班次', 'log' => '[user|get_nickname] 禁用了班次：[details]'],
                ['name' => 'leave_add', 'title' => '添加请假', 'remark' => '添加请假', 'log' => '[user|get_nickname] 添加了请假：[details]'],
                ['name' => 'leave_edit', 'title' => '编辑请假', 'remark' => '编辑请假', 'log' => '[user|get_nickname] 编辑了请假：[details]'],
                ['name' => 'leave_delete', 'title' => '删除请假', 'remark' => '删除请假', 'log' => '[user|get_nickname] 删除了请假：[details]'],
                ['name' => 'leave_approve', 'title' => '审批请假', 'remark' => '审批请假', 'log' => '[user|get_nickname] 审批了请假：[details]'],
                ['name' => 'leave_reject', 'title' => '驳回请假', 'remark' => '驳回请假', 'log' => '[user|get_nickname] 驳回了请假：[details]'],
                ['name' => 'swap_add', 'title' => '添加换班', 'remark' => '添加换班', 'log' => '[user|get_nickname] 添加了换班：[details]'],
                ['name' => 'swap_edit', 'title' => '编辑换班', 'remark' => '编辑换班', 'log' => '[user|get_nickname] 编辑了换班：[details]'],
                ['name' => 'swap_delete', 'title' => '删除换班', 'remark' => '删除换班', 'log' => '[user|get_nickname] 删除了换班：[details]'],
                ['name' => 'swap_approve', 'title' => '审批换班', 'remark' => '审批换班', 'log' => '[user|get_nickname] 审批了换班：[details]'],
                ['name' => 'swap_reject', 'title' => '驳回换班', 'remark' => '驳回换班', 'log' => '[user|get_nickname] 驳回了换班：[details]'],
                ['name' => 'handover_add', 'title' => '添加交接', 'remark' => '添加交接', 'log' => '[user|get_nickname] 添加了交接：[details]'],
                ['name' => 'handover_edit', 'title' => '编辑交接', 'remark' => '编辑交接', 'log' => '[user|get_nickname] 编辑了交接：[details]'],
                ['name' => 'handover_delete', 'title' => '删除交接', 'remark' => '删除交接', 'log' => '[user|get_nickname] 删除了交接：[details]'],
                ['name' => 'handover_receive', 'title' => '接收交接', 'remark' => '接收交接', 'log' => '[user|get_nickname] 接收了交接：[details]'],
                ['name' => 'handover_cancel', 'title' => '取消交接', 'remark' => '取消交接', 'log' => '[user|get_nickname] 取消了交接：[details]'],
                ['name' => 'contact_method_add', 'title' => '添加联系方式', 'remark' => '添加联系方式', 'log' => '[user|get_nickname] 添加了联系方式：[details]'],
                ['name' => 'contact_method_edit', 'title' => '编辑联系方式', 'remark' => '编辑联系方式', 'log' => '[user|get_nickname] 编辑了联系方式：[details]'],
                ['name' => 'contact_method_delete', 'title' => '删除联系方式', 'remark' => '删除联系方式', 'log' => '[user|get_nickname] 删除了联系方式：[details]'],
                ['name' => 'leave_type_add', 'title' => '添加请假类型', 'remark' => '添加请假类型', 'log' => '[user|get_nickname] 添加了请假类型：[details]'],
                ['name' => 'leave_type_edit', 'title' => '编辑请假类型', 'remark' => '编辑请假类型', 'log' => '[user|get_nickname] 编辑了请假类型：[details]'],
                ['name' => 'leave_type_delete', 'title' => '删除请假类型', 'remark' => '删除请假类型', 'log' => '[user|get_nickname] 删除了请假类型：[details]'],
            ],
            // 仓管模块 actions
            'stor' => [
                ['name' => 'category_add', 'title' => '添加分类', 'remark' => '添加分类', 'log' => '[user|get_nickname] 添加了分类：[details]'],
                ['name' => 'category_edit', 'title' => '编辑分类', 'remark' => '编辑分类', 'log' => '[user|get_nickname] 编辑了分类：[details]'],
                ['name' => 'category_delete', 'title' => '删除分类', 'remark' => '删除分类', 'log' => '[user|get_nickname] 删除了分类：[details]'],
                ['name' => 'category_scrap', 'title' => '作废分类', 'remark' => '作废分类', 'log' => '[user|get_nickname] 作废了分类：[details]'],
                ['name' => 'category_restore', 'title' => '恢复分类', 'remark' => '恢复分类', 'log' => '[user|get_nickname] 恢复了分类：[details]'],
                ['name' => 'category_enable', 'title' => '启用分类', 'remark' => '启用分类', 'log' => '[user|get_nickname] 启用了分类：[details]'],
                ['name' => 'category_disable', 'title' => '禁用分类', 'remark' => '禁用分类', 'log' => '[user|get_nickname] 禁用了分类：[details]'],
                ['name' => 'material_add', 'title' => '添加物料', 'remark' => '添加物料', 'log' => '[user|get_nickname] 添加了物料：[details]'],
                ['name' => 'material_edit', 'title' => '编辑物料', 'remark' => '编辑物料', 'log' => '[user|get_nickname] 编辑了物料：[details]'],
                ['name' => 'material_delete', 'title' => '删除物料', 'remark' => '删除物料', 'log' => '[user|get_nickname] 删除了物料：[details]'],
                ['name' => 'material_scrap', 'title' => '作废物料', 'remark' => '作废物料', 'log' => '[user|get_nickname] 作废了物料：[details]'],
                ['name' => 'material_restore', 'title' => '恢复物料', 'remark' => '恢复物料', 'log' => '[user|get_nickname] 恢复了物料：[details]'],
                ['name' => 'material_enable', 'title' => '启用物料', 'remark' => '启用物料', 'log' => '[user|get_nickname] 启用了物料：[details]'],
                ['name' => 'material_disable', 'title' => '禁用物料', 'remark' => '禁用物料', 'log' => '[user|get_nickname] 禁用了物料：[details]'],
                ['name' => 'material_sn_add', 'title' => '添加物料SN', 'remark' => '添加物料SN', 'log' => '[user|get_nickname] 添加了物料SN：[details]'],
                ['name' => 'material_sn_edit', 'title' => '编辑物料SN', 'remark' => '编辑物料SN', 'log' => '[user|get_nickname] 编辑了物料SN：[details]'],
                ['name' => 'material_sn_delete', 'title' => '删除物料SN', 'remark' => '删除物料SN', 'log' => '[user|get_nickname] 删除了物料SN：[details]'],
                ['name' => 'material_sn_scrap', 'title' => '作废物料SN', 'remark' => '作废物料SN', 'log' => '[user|get_nickname] 作废了物料SN：[details]'],
                ['name' => 'material_sn_restore', 'title' => '恢复物料SN', 'remark' => '恢复物料SN', 'log' => '[user|get_nickname] 恢复了物料SN：[details]'],
                ['name' => 'material_sn_allocate', 'title' => '分配物料SN', 'remark' => '分配物料SN', 'log' => '[user|get_nickname] 分配了物料SN：[details]'],
                ['name' => 'material_sn_unallocate', 'title' => '取消分配物料SN', 'remark' => '取消分配物料SN', 'log' => '[user|get_nickname] 取消了物料SN的分配：[details]'],
                ['name' => 'inbound_add', 'title' => '添加入库单', 'remark' => '添加入库单', 'log' => '[user|get_nickname] 添加了入库单：[details]'],
                ['name' => 'inbound_edit', 'title' => '编辑入库单', 'remark' => '编辑入库单', 'log' => '[user|get_nickname] 编辑了入库单：[details]'],
                ['name' => 'inbound_delete', 'title' => '删除入库单', 'remark' => '删除入库单', 'log' => '[user|get_nickname] 删除了入库单：[details]'],
                ['name' => 'inbound_cancel', 'title' => '取消入库单', 'remark' => '取消入库单', 'log' => '[user|get_nickname] 取消了入库单：[details]'],
                ['name' => 'inbound_confirm', 'title' => '确认入库单', 'remark' => '确认入库单', 'log' => '[user|get_nickname] 确认了入库单：[details]'],
                ['name' => 'outbound_add', 'title' => '添加出库单', 'remark' => '添加出库单', 'log' => '[user|get_nickname] 添加了出库单：[details]'],
                ['name' => 'outbound_edit', 'title' => '编辑出库单', 'remark' => '编辑出库单', 'log' => '[user|get_nickname] 编辑了出库单：[details]'],
                ['name' => 'outbound_delete', 'title' => '删除出库单', 'remark' => '删除出库单', 'log' => '[user|get_nickname] 删除了出库单：[details]'],
                ['name' => 'outbound_cancel', 'title' => '取消出库单', 'remark' => '取消出库单', 'log' => '[user|get_nickname] 取消了出库单：[details]'],
                ['name' => 'outbound_confirm', 'title' => '确认出库单', 'remark' => '确认出库单', 'log' => '[user|get_nickname] 确认了出库单：[details]'],
                ['name' => 'repair_add', 'title' => '添加维修单', 'remark' => '添加维修单', 'log' => '[user|get_nickname] 添加了维修单：[details]'],
                ['name' => 'repair_edit', 'title' => '编辑维修单', 'remark' => '编辑维修单', 'log' => '[user|get_nickname] 编辑了维修单：[details]'],
                ['name' => 'repair_delete', 'title' => '删除维修单', 'remark' => '删除维修单', 'log' => '[user|get_nickname] 删除了维修单：[details]'],
                ['name' => 'repair_complete', 'title' => '维修完成', 'remark' => '维修完成', 'log' => '[user|get_nickname] 完成了维修单：[details]'],
                ['name' => 'repair_scrap', 'title' => '维修作废', 'remark' => '维修作废', 'log' => '[user|get_nickname] 作废了维修单：[details]'],
                ['name' => 'project_add', 'title' => '添加项目', 'remark' => '添加项目', 'log' => '[user|get_nickname] 添加了项目：[details]'],
                ['name' => 'project_edit', 'title' => '编辑项目', 'remark' => '编辑项目', 'log' => '[user|get_nickname] 编辑了项目：[details]'],
                ['name' => 'project_delete', 'title' => '删除项目', 'remark' => '删除项目', 'log' => '[user|get_nickname] 删除了项目：[details]'],
                ['name' => 'project_enable', 'title' => '启用项目', 'remark' => '启用项目', 'log' => '[user|get_nickname] 启用了项目：[details]'],
                ['name' => 'project_disable', 'title' => '禁用项目', 'remark' => '禁用项目', 'log' => '[user|get_nickname] 禁用了项目：[details]'],
                ['name' => 'warehouse_add', 'title' => '添加库房', 'remark' => '添加库房', 'log' => '[user|get_nickname] 添加了库房：[details]'],
                ['name' => 'warehouse_edit', 'title' => '编辑库房', 'remark' => '编辑库房', 'log' => '[user|get_nickname] 编辑了库房：[details]'],
                ['name' => 'warehouse_delete', 'title' => '删除库房', 'remark' => '删除库房', 'log' => '[user|get_nickname] 删除了库房：[details]'],
                ['name' => 'warehouse_enable', 'title' => '启用库房', 'remark' => '启用库房', 'log' => '[user|get_nickname] 启用了库房：[details]'],
                ['name' => 'warehouse_disable', 'title' => '禁用库房', 'remark' => '禁用库房', 'log' => '[user|get_nickname] 禁用了库房：[details]'],
                ['name' => 'inbound_type_add', 'title' => '添加入库类型', 'remark' => '添加入库类型', 'log' => '[user|get_nickname] 添加入库类型：[details]'],
                ['name' => 'inbound_type_edit', 'title' => '编辑入库类型', 'remark' => '编辑入库类型', 'log' => '[user|get_nickname] 编辑了入库类型：[details]'],
                ['name' => 'inbound_type_delete', 'title' => '删除入库类型', 'remark' => '删除入库类型', 'log' => '[user|get_nickname] 删除了入库类型：[details]'],
                ['name' => 'inbound_type_enable', 'title' => '启用入库类型', 'remark' => '启用入库类型', 'log' => '[user|get_nickname] 启用了入库类型：[details]'],
                ['name' => 'inbound_type_disable', 'title' => '禁用入库类型', 'remark' => '禁用入库类型', 'log' => '[user|get_nickname] 禁用了入库类型：[details]'],
                ['name' => 'outbound_type_add', 'title' => '添加出库类型', 'remark' => '添加出库类型', 'log' => '[user|get_nickname] 添加了出库类型：[details]'],
                ['name' => 'outbound_type_edit', 'title' => '编辑出库类型', 'remark' => '编辑出库类型', 'log' => '[user|get_nickname] 编辑了出库类型：[details]'],
                ['name' => 'outbound_type_delete', 'title' => '删除出库类型', 'remark' => '删除出库类型', 'log' => '[user|get_nickname] 删除了出库类型：[details]'],
                ['name' => 'outbound_type_enable', 'title' => '启用出库类型', 'remark' => '启用出库类型', 'log' => '[user|get_nickname] 启用了出库类型：[details]'],
                ['name' => 'outbound_type_disable', 'title' => '禁用出库类型', 'remark' => '禁用出库类型', 'log' => '[user|get_nickname] 禁用了出库类型：[details]'],
                ['name' => 'inventory_add', 'title' => '添加盘点', 'remark' => '添加盘点', 'log' => '[user|get_nickname] 添加了盘点：[details]'],
                ['name' => 'inventory_edit', 'title' => '编辑盘点', 'remark' => '编辑盘点', 'log' => '[user|get_nickname] 编辑了盘点：[details]'],
                ['name' => 'inventory_delete', 'title' => '删除盘点', 'remark' => '删除盘点', 'log' => '[user|get_nickname] 删除了盘点：[details]'],
                ['name' => 'inventory_confirm', 'title' => '确认盘点', 'remark' => '确认盘点', 'log' => '[user|get_nickname] 确认了盘点：[details]'],
            ],
        ];

        $ActionModel = model('admin/action');
        foreach ($actions as $module => $moduleActions) {
            foreach ($moduleActions as $action) {
                $exist = $ActionModel->where('module', $module)->where('name', $action['name'])->find();
                if (!$exist) {
                    $ActionModel->create([
                        'module' => $module,
                        'name' => $action['name'],
                        'title' => $action['title'],
                        'remark' => $action['remark'],
                        'rule' => '',
                        'type' => '',
                        'log' => $action['log'],
                        'status' => 1,
                    ]);
                }
            }
        }
    }

    /**
     * 初始化
     * @throws \think\Exception
     */
    protected function initialize()
    {
        parent::initialize();

        // 自动注册各模块的action到dp_admin_action表
        $registerCacheKey = 'registered_module_actions';
        if (!Cache::get($registerCacheKey)) {
            self::registerModuleActions();
            Cache::set($registerCacheKey, 1, 86400);
        }

        // 是否拒绝ie浏览器访问
        if (config('system.deny_ie') && get_browser_type() == 'ie') {
            $this->redirect('admin/ie/index');
        }

        // 判断是否登录，并定义用户ID常量
        defined('UID') or define('UID', $this->isLogin());

        // 设置当前角色菜单节点权限
        role_auth();

        // 检查权限
        if (!RoleModel::checkAuth())
            $this->error('权限不足！');

        // 设置分页参数
        $this->setPageParam();

        // 如果不是ajax请求，则读取菜单
        if (!$this->request->isAjax()) {
            // 读取顶部菜单
            $this->assign('_top_menus', MenuModel::getTopMenu(config('top_menu_max'), '_top_menus'));
            // 读取全部顶级菜单
            $this->assign('_top_menus_all', MenuModel::getTopMenu('', '_top_menus_all'));
            // 获取侧边栏菜单
            $this->assign('_sidebar_menus', MenuModel::getSidebarMenu());
            // 获取面包屑导航
            $this->assign('_location', MenuModel::getLocation('', true));
            // 获取当前用户未读消息数量
            $this->assign('_message', MessageModel::getMessageCount());
            // 获取自定义图标
            $this->assign('_icons', IconModel::getUrls());
            // 构建侧栏
            $data = [
                'table' => 'admin_config',
                'prefix' => 1,
                'module' => 'admin',
                'controller' => 'system',
                'action' => 'quickedit',
            ];
            $table_token = substr(sha1('_aside'), 0, 8);
            session($table_token, $data);
            $settings = [
                [
                    'title' => '站点开关',
                    'tips' => '站点关闭后将不能访问',
                    'checked' => Db::name('admin_config')
                        ->where('id', 1)
                        ->value('value'),
                    'table' => $table_token,
                    'id' => 1,
                    'field' => 'value'
                ]
            ];
            ZBuilder::make('aside')
                ->addBlock('switch', '系统设置', $settings);
        }
    }

    /**
     * 检查是否登录，没有登录则跳转到登录页面
     * @return int
     */
    final protected function isLogin()
    {
        // 判断是否登录
        if ($uid = is_signin()) {
            // 已登录
            return $uid;
        } else {
            // 未登录
            $this->redirect('user/publics/signin');
        }
    }

    /**
     * 设置分页参数
     */
    final protected function setPageParam()
    {
        _system_check();
        $list_rows = input('?param.list_rows') ? input('param.list_rows') : config('list_rows');
        config('paginate.list_rows', $list_rows);
        config('paginate.query', input('get.'));
    }
}
