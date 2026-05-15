<?php

namespace app\coop\action;

use app\coop\model\CooperateModel;
use app\coop\model\CooperateNoteModel;
use app\user\model\User as UserModel;

class CooperateAction
{
    public static function getList($map = [], $page = true)
    {
        $query = CooperateModel::where($map)->order('create_time desc');
        
        if ($page) {
            return $query->paginate();
        }
        
        return $query->select();
    }

    public static function getMyCooperates($user_id)
    {
        $map = [
            ['status', '=', 1],
            ['receiver_id', '=', $user_id],
        ];
        
        return CooperateModel::where($map)->order('create_time desc')->paginate();
    }

    public static function getMySentCooperates($user_id)
    {
        $map = [
            ['status', '=', 1],
            ['creator_id', '=', $user_id],
        ];
        
        return CooperateModel::where($map)->order('create_time desc')->paginate();
    }

    public static function add($data)
    {
        $data['creator_id'] = UID;
        $data['creator_name'] = get_nickname(UID);
        
        if ($cooperate = CooperateModel::create($data)) {
            action_log('cooperate_add', 'mt_cooperate', $cooperate['id'], UID);
            return $cooperate;
        }
        
        throw new \Exception('创建协办单失败');
    }

    public static function edit($data)
    {
        $cooperate = CooperateModel::where('id', $data['id'])->find();
        if (!$cooperate) {
            throw new \Exception('协办单不存在');
        }

        if ($cooperate['creator_id'] != UID) {
            throw new \Exception('只能修改自己创建的协办单');
        }

        if ($cooperate['is_canceled'] == 1) {
            throw new \Exception('已作废的协办单不能修改');
        }

        if (CooperateModel::update($data)) {
            action_log('cooperate_edit', 'mt_cooperate', $data['id'], UID);
            return true;
        }
        
        throw new \Exception('编辑协办单失败');
    }

    public static function cancel($id)
    {
        $cooperate = CooperateModel::where('id', $id)->find();
        if (!$cooperate) {
            throw new \Exception('协办单不存在');
        }

        if ($cooperate['creator_id'] != UID) {
            throw new \Exception('只能作废自己创建的协办单');
        }

        $data = [
            'id' => $id,
            'is_canceled' => 1,
        ];
        
        if (CooperateModel::update($data)) {
            action_log('cooperate_cancel', 'mt_cooperate', $id, UID);
            return true;
        }
        
        throw new \Exception('作废失败');
    }

    public static function push($cooperate_id, $to_user_id)
    {
        $cooperate = CooperateModel::where('id', $cooperate_id)->find();
        if (!$cooperate) {
            throw new \Exception('协办单不存在');
        }

        if ($cooperate['is_canceled'] == 1) {
            throw new \Exception('已作废的协办单无法推送');
        }

        if ($cooperate['is_closed'] == 1) {
            throw new \Exception('已关闭的协办单无法推送');
        }

        if ($cooperate['creator_id'] != UID && $cooperate['receiver_id'] != UID) {
            throw new \Exception('只有创建人或接收人才能甩单');
        }

        $to_user = UserModel::where('id', $to_user_id)->find();
        if (!$to_user) {
            throw new \Exception('接收人不存在');
        }

        $data = [
            'id' => $cooperate_id,
            'receiver_id' => $to_user_id,
            'receiver_name' => $to_user['nickname'],
        ];

        if (CooperateModel::update($data)) {
            action_log('cooperate_push', 'mt_cooperate', $cooperate_id, UID);
            return true;
        }
        
        throw new \Exception('推送失败');
    }

    public static function close($id)
    {
        $cooperate = CooperateModel::where('id', $id)->find();
        if (!$cooperate) {
            throw new \Exception('协办单不存在');
        }

        if ($cooperate['is_canceled'] == 1) {
            throw new \Exception('已作废的协办单不能关闭');
        }

        if ($cooperate['is_closed'] == 1) {
            throw new \Exception('协办单已关闭');
        }

        if ($cooperate['creator_id'] != UID && $cooperate['receiver_id'] != UID) {
            throw new \Exception('只有创建人或接收人才能关闭协办单');
        }

        $data = [
            'id' => $id,
            'is_closed' => 1,
            'closer_id' => UID,
            'closer_name' => get_nickname(UID),
            'close_time' => date('Y-m-d H:i:s'),
        ];
        
        if (CooperateModel::update($data)) {
            action_log('cooperate_close', 'mt_cooperate', $id, UID);
            return true;
        }
        
        throw new \Exception('关闭失败');
    }

    public static function reopen($id)
    {
        $cooperate = CooperateModel::where('id', $id)->find();
        if (!$cooperate) {
            throw new \Exception('协办单不存在');
        }

        if ($cooperate['is_canceled'] == 1) {
            throw new \Exception('已作废的协办单不能重新打开');
        }

        if ($cooperate['creator_id'] != UID) {
            throw new \Exception('只有创建人才能重新打开协办单');
        }

        $data = [
            'id' => $id,
            'is_closed' => 0,
            'closer_id' => 0,
            'closer_name' => '',
            'close_time' => null,
        ];
        
        if (CooperateModel::update($data)) {
            action_log('cooperate_reopen', 'mt_cooperate', $id, UID);
            return true;
        }
        
        throw new \Exception('重新打开失败');
    }

    public static function addNote($cooperate_id, $content)
    {
        $cooperate = CooperateModel::where('id', $cooperate_id)->find();
        if (!$cooperate) {
            throw new \Exception('协办单不存在');
        }

        if ($cooperate['is_canceled'] == 1) {
            throw new \Exception('已作废的协办单不能添加进度');
        }

        $data = [
            'cooperate_id' => $cooperate_id,
            'content' => $content,
            'user_id' => UID,
            'user_name' => get_nickname(UID),
        ];

        if (CooperateNoteModel::create($data)) {
            action_log('cooperate_note_add', 'mt_cooperate_note', $cooperate_id, UID);
            return true;
        }
        
        throw new \Exception('添加进度失败');
    }

    public static function getNotes($cooperate_id)
    {
        return CooperateNoteModel::where('cooperate_id', $cooperate_id)->order('create_time desc')->select();
    }

    public static function getInfo($id)
    {
        return CooperateModel::where('id', $id)->find();
    }

    public static function getPriorityList()
    {
        return [
            1 => '<span class="label label-default">1 - 普通</span>',
            2 => '<span class="label label-info">2 - 低</span>',
            3 => '<span class="label label-warning">3 - 中</span>',
            4 => '<span class="label label-danger">4 - 高</span>',
            5 => '<span class="label label-red">5 - 紧急</span>',
        ];
    }
}