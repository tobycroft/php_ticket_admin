<?php

namespace app\admin\model;

use think\Model;

class ActionLog extends Model
{
    protected $name = 'action_log';
    
    protected $autoWriteTimestamp = true;
    
    protected $createTime = 'create_time';
    protected $updateTime = false;

    public static function record($config = [])
    {
        $data = [
            'user_id' => isset($config['user_id']) ? $config['user_id'] : (defined('UID') ? UID : 0),
            'username' => isset($config['username']) ? $config['username'] : (defined('USERNAME') ? USERNAME : ''),
            'nickname' => isset($config['nickname']) ? $config['nickname'] : (defined('NICKNAME') ? NICKNAME : ''),
            'action_ip' => request()->ip(),
            'module' => isset($config['module']) ? $config['module'] : '',
            'table_name' => isset($config['table_name']) ? $config['table_name'] : '',
            'action_type' => isset($config['action_type']) ? $config['action_type'] : '',
            'record_id' => isset($config['record_id']) ? $config['record_id'] : 0,
            'record_title' => isset($config['record_title']) ? $config['record_title'] : '',
            'old_data' => isset($config['old_data']) ? json_encode($config['old_data'], JSON_UNESCAPED_UNICODE) : null,
            'new_data' => isset($config['new_data']) ? json_encode($config['new_data'], JSON_UNESCAPED_UNICODE) : null,
            'diff_data' => isset($config['diff_data']) ? json_encode($config['diff_data'], JSON_UNESCAPED_UNICODE) : null,
            'remark' => isset($config['remark']) ? $config['remark'] : '',
        ];
        
        return self::create($data);
    }

    public static function addRecord($module, $tableName, $actionType, $recordId, $recordTitle, $newData, $remark = '')
    {
        return self::record([
            'module' => $module,
            'table_name' => $tableName,
            'action_type' => $actionType,
            'record_id' => $recordId,
            'record_title' => $recordTitle,
            'new_data' => $newData,
            'remark' => $remark,
        ]);
    }

    public static function editRecord($module, $tableName, $recordId, $recordTitle, $oldData, $newData, $remark = '')
    {
        $diff = self::computeDiff($oldData, $newData);
        
        return self::record([
            'module' => $module,
            'table_name' => $tableName,
            'action_type' => 'edit',
            'record_id' => $recordId,
            'record_title' => $recordTitle,
            'old_data' => $oldData,
            'new_data' => $newData,
            'diff_data' => $diff,
            'remark' => $remark,
        ]);
    }

    public static function deleteRecord($module, $tableName, $recordId, $recordTitle, $oldData, $remark = '')
    {
        return self::record([
            'module' => $module,
            'table_name' => $tableName,
            'action_type' => 'delete',
            'record_id' => $recordId,
            'record_title' => $recordTitle,
            'old_data' => $oldData,
            'remark' => $remark,
        ]);
    }

    private static function computeDiff($oldData, $newData)
    {
        if (!is_array($oldData) || !is_array($newData)) {
            return [];
        }
        
        $diff = [];
        
        foreach ($newData as $key => $value) {
            if (!isset($oldData[$key])) {
                $diff[$key] = ['type' => 'add', 'old' => null, 'new' => $value];
            } elseif ($oldData[$key] != $value) {
                $diff[$key] = ['type' => 'modify', 'old' => $oldData[$key], 'new' => $value];
            }
        }
        
        foreach ($oldData as $key => $value) {
            if (!isset($newData[$key])) {
                $diff[$key] = ['type' => 'delete', 'old' => $value, 'new' => null];
            }
        }
        
        return $diff;
    }

    public function getModuleTextAttr($value, $data)
    {
        $list = [
            'maintenance' => '运维系统',
            'stor' => '仓管系统',
            'admin' => '系统管理',
        ];
        return isset($list[$data['module']]) ? $list[$data['module']] : $data['module'];
    }

    public function getActionTypeTextAttr($value, $data)
    {
        $list = [
            'add' => '新增',
            'edit' => '编辑',
            'delete' => '删除',
            'enable' => '启用',
            'disable' => '禁用',
            'scrap' => '作废',
            'receive' => '接单',
            'close' => '关闭',
            'reopen' => '重开',
            'cancel' => '取消',
            'push' => '推送',
            'complete' => '完成',
        ];
        return isset($list[$data['action_type']]) ? $list[$data['action_type']] : $data['action_type'];
    }
}
