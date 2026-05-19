<?php

namespace app\maintenance\action;

use app\maintenance\model\EventFlowModel;
use app\maintenance\model\EventModel;
use app\maintenance\model\EventNoteModel;
use app\user\model\User as UserModel;
use think\Db;

class EventAction
{
    public static function getList($map = [], $page = true)
    {
        $query = EventModel::where($map)->order('create_time desc');
        
        if ($page) {
            return $query->paginate();
        }
        
        return $query->select();
    }

    public static function getMyEvents($user_id)
    {
        $map = [
            ['status', '=', 1],
            ['is_closed', '=', 0],
            ['receiver_id', '=', $user_id],
        ];
        
        return EventModel::where($map)->order('create_time desc')->paginate();
    }

    public static function getMySentEvents($user_id)
    {
        $map = [
            ['status', '=', 1],
            ['creator_id', '=', $user_id],
        ];
        
        return EventModel::where($map)->order('create_time desc')->paginate();
    }

    public static function add($data)
    {
        $data['creator_id'] = UID;
        $data['receiver_id'] = UID;
        $data['receiver_name'] = get_nickname(UID);
        
        if (isset($data['contact_method']) && is_array($data['contact_method'])) {
            $data['contact_method'] = implode(',', $data['contact_method']);
        }
        
        if ($event = EventModel::create($data)) {
            action_log('event_add', 'mt_event', $event['id'], UID);
            return $event;
        }
        
        throw new \Exception('创建工单失败');
    }

    public static function edit($data)
    {
        $oldEvent = EventModel::where('id', $data['id'])->find();
        
        if (isset($data['contact_method']) && is_array($data['contact_method'])) {
            $data['contact_method'] = implode(',', $data['contact_method']);
        }
        
        if (EventModel::update($data)) {
            action_log('event_edit', 'mt_event', $data['id'], UID);
            return true;
        }
        
        throw new \Exception('编辑工单失败');
    }

    public static function close($id)
    {
        $oldEvent = EventModel::where('id', $id)->find();
        
        $data = [
            'id' => $id,
            'is_closed' => 1,
            'closer_id' => UID,
            'closer_name' => get_nickname(UID),
            'end_time' => date('Y-m-d H:i:s'),
        ];
        
        if (EventModel::update($data)) {
            action_log('event_close', 'mt_event', $id, UID);
            return true;
        }
        
        throw new \Exception('标注完成失败');
    }

    public static function reopen($id)
    {
        $oldEvent = EventModel::where('id', $id)->find();
        
        $data = [
            'id' => $id,
            'is_closed' => 0,
            'closer_id' => 0,
            'closer_name' => '',
            'end_time' => null,
        ];
        
        if (EventModel::update($data)) {
            action_log('event_reopen', 'mt_event', $id, UID);
            return true;
        }
        
        throw new \Exception('标注未完成失败');
    }

    public static function receive($id)
    {
        $oldEvent = EventModel::where('id', $id)->find();
        
        $data = [
            'id' => $id,
            'receiver_id' => UID,
            'receiver_name' => get_nickname(UID),
            'receive_type' => 1,
        ];
        
        if (EventModel::update($data)) {
            action_log('event_receive', 'mt_event', $id, UID);
            return true;
        }
        
        throw new \Exception('接单失败');
    }

    public static function push($event_id, $to_user_id, $reason = '')
    {
        $event = EventModel::where('id', $event_id)->find();
        if (!$event) {
            throw new \Exception('工单不存在');
        }

        if ($event['is_closed'] == 1) {
            throw new \Exception('工单已结单，无法推送');
        }

        $to_user = UserModel::where('id', $to_user_id)->find();
        if (!$to_user) {
            throw new \Exception('接收人不存在');
        }

        $flow_data = [
            'event_id' => $event_id,
            'from_user_id' => UID,
            'from_user_name' => get_nickname(UID),
            'to_user_id' => $to_user_id,
            'to_user_name' => $to_user['nickname'],
            'reason' => $reason,
            'status' => 0,
        ];

        $event_data = [
            'id' => $event_id,
            'receiver_id' => $to_user_id,
            'receiver_name' => $to_user['nickname'],
        ];

        Db::startTrans();
        try {
            EventFlowModel::create($flow_data);
            EventModel::update($event_data);
            Db::commit();
            
            action_log('event_push', 'mt_event', $event_id, UID);
            return true;
        } catch (\ErrorException $e) {
            Db::rollback();
            throw $e;
        }
    }

    public static function handleFlow($flow_id, $status, $reason = '')
    {
        $flow = EventFlowModel::where('id', $flow_id)->find();
        if (!$flow) {
            throw new \Exception('流转记录不存在');
        }

        if ($flow['status'] != 0) {
            throw new \Exception('该流转记录已处理');
        }

        $update_data = [
            'id' => $flow_id,
            'status' => $status,
            'handle_time' => date('Y-m-d H:i:s'),
        ];

        if ($status == 2 && $reason) {
            $update_data['reason'] = $flow['reason'] . "\n退回理由: " . $reason;
        }

        EventFlowModel::update($update_data);
        action_log('event_flow_handle', 'mt_event_flow', $flow_id, UID);

        if ($status == 2) {
            EventModel::update([
                'id' => $flow['event_id'],
                'receiver_id' => $flow['from_user_id'],
                'receiver_name' => $flow['from_user_name'],
            ]);
        }

        return true;
    }

    public static function addNote($event_id, $content)
    {
        $event = EventModel::where('id', $event_id)->find();
        if (!$event) {
            throw new \Exception('工单不存在');
        }

        $data = [
            'event_id' => $event_id,
            'content' => $content,
            'user_id' => UID,
            'user_name' => get_nickname(UID),
        ];

        if ($note = EventNoteModel::create($data)) {
            action_log('event_note_add', 'mt_event_note', $event_id, UID);
            return true;
        }
        
        throw new \Exception('添加备注失败');
    }

    public static function getNotes($event_id)
    {
        return EventNoteModel::where('event_id', $event_id)->order('create_time desc')->select();
    }

    public static function getFlows($event_id)
    {
        return EventFlowModel::where('event_id', $event_id)->order('create_time desc')->select();
    }

    public static function getInfo($id)
    {
        return EventModel::where('id', $id)->find();
    }

    public static function delete($id)
    {
        return self::cancel($id);
    }

    public static function cancel($id)
    {
        $oldEvent = EventModel::where('id', $id)->find();
        
        $data = [
            'id' => $id,
            'is_canceled' => 1,
        ];
        
        if (EventModel::update($data)) {
            action_log('event_cancel', 'mt_event', $id, UID);
            return true;
        }
        
        throw new \Exception('作废失败');
    }

    public static function getIsCanceledList()
    {
        return [
            0 => '正常',
            1 => '已作废',
        ];
    }

    public static function active($id)
    {
        $oldEvent = EventModel::where('id', $id)->find();
        
        $data = [
            'id' => $id,
            'is_canceled' => 0,
        ];
        
        if (EventModel::update($data)) {
            action_log('event_active', 'mt_event', $id, UID);
            return true;
        }
        
        throw new \Exception('激活失败');
    }

    public static function getStatusList()
    {
        return EventModel::getStatusList();
    }

    public static function getIsClosedList()
    {
        return EventModel::getIsClosedList();
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

    public static function getUnclosedEventCount()
    {
        $map = [
            ['is_closed', '=', 0],
            ['is_canceled', '=', 0],
        ];
        
        return EventModel::where($map)->count();
    }

    public static function getReceiveTypeList()
    {
        return EventModel::getReceiveTypeList();
    }

    public static function getFlowStatusList()
    {
        return EventFlowModel::getStatusList();
    }
}