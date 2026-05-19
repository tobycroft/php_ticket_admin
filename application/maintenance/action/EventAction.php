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
            $details = json_encode([
                'title' => $data['title'] ?? '',
                'subtitle' => $data['subtitle'] ?? '',
                'creator_name' => $data['creator_name'] ?? '',
                'customer_name' => $data['customer_name'] ?? '',
                'priority' => $data['priority'] ?? 1,
                'start_time' => $data['start_time'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_add', 'mt_event', $event['id'], UID, $details);
            return $event;
        }
        
        throw new \Exception('创建工单失败');
    }

    public static function edit($data)
    {
        $oldEvent = EventModel::where('id', $data['id'])->find();
        
        // 处理结单状态
        if (isset($data['status_type'])) {
            $data['is_closed'] = 0;
            $data['is_no_feedback'] = 0;
            
            if ($data['status_type'] == 1) {
                $data['is_closed'] = 1;
                $data['closer_id'] = UID;
                $data['closer_name'] = get_nickname(UID);
                $data['end_time'] = date('Y-m-d H:i:s');
            } elseif ($data['status_type'] == 2) {
                $data['is_no_feedback'] = 1;
            }
            
            unset($data['status_type']);
        }
        
        if (isset($data['contact_method']) && is_array($data['contact_method'])) {
            $data['contact_method'] = implode(',', $data['contact_method']);
        }
        
        if (EventModel::update($data)) {
            $newEvent = EventModel::where('id', $data['id'])->find();
            $details = json_encode([
                'old' => [
                    'title' => $oldEvent['title'] ?? '',
                    'subtitle' => $oldEvent['subtitle'] ?? '',
                    'creator_name' => $oldEvent['creator_name'] ?? '',
                    'customer_name' => $oldEvent['customer_name'] ?? '',
                    'priority' => $oldEvent['priority'] ?? 1,
                    'is_closed' => $oldEvent['is_closed'] ?? 0,
                    'is_no_feedback' => $oldEvent['is_no_feedback'] ?? 0,
                ],
                'new' => [
                    'title' => $newEvent['title'] ?? '',
                    'subtitle' => $newEvent['subtitle'] ?? '',
                    'creator_name' => $newEvent['creator_name'] ?? '',
                    'customer_name' => $newEvent['customer_name'] ?? '',
                    'priority' => $newEvent['priority'] ?? 1,
                    'is_closed' => $newEvent['is_closed'] ?? 0,
                    'is_no_feedback' => $newEvent['is_no_feedback'] ?? 0,
                ],
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_edit', 'mt_event', $data['id'], UID, $details);
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
            $details = json_encode([
                'title' => $oldEvent['title'] ?? '',
                'subtitle' => $oldEvent['subtitle'] ?? '',
                'creator_name' => $oldEvent['creator_name'] ?? '',
                'customer_name' => $oldEvent['customer_name'] ?? '',
                'priority' => $oldEvent['priority'] ?? 1,
                'start_time' => $oldEvent['start_time'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_close', 'mt_event', $id, UID, $details);
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
            $details = json_encode([
                'title' => $oldEvent['title'] ?? '',
                'subtitle' => $oldEvent['subtitle'] ?? '',
                'creator_name' => $oldEvent['creator_name'] ?? '',
                'customer_name' => $oldEvent['customer_name'] ?? '',
                'priority' => $oldEvent['priority'] ?? 1,
                'start_time' => $oldEvent['start_time'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_reopen', 'mt_event', $id, UID, $details);
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
            $details = json_encode([
                'title' => $oldEvent['title'] ?? '',
                'subtitle' => $oldEvent['subtitle'] ?? '',
                'creator_name' => $oldEvent['creator_name'] ?? '',
                'customer_name' => $oldEvent['customer_name'] ?? '',
                'priority' => $oldEvent['priority'] ?? 1,
                'start_time' => $oldEvent['start_time'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_receive', 'mt_event', $id, UID, $details);
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
            
            $details = json_encode([
                'title' => $event['title'] ?? '',
                'subtitle' => $event['subtitle'] ?? '',
                'creator_name' => $event['creator_name'] ?? '',
                'customer_name' => $event['customer_name'] ?? '',
                'priority' => $event['priority'] ?? 1,
                'from_user_name' => $flow_data['from_user_name'] ?? '',
                'to_user_name' => $flow_data['to_user_name'] ?? '',
                'reason' => $reason ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_push', 'mt_event', $event_id, UID, $details);
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

        $event = EventModel::where('id', $flow['event_id'])->find();
        $details = json_encode([
            'title' => $event['title'] ?? '',
            'subtitle' => $event['subtitle'] ?? '',
            'from_user_name' => $flow['from_user_name'] ?? '',
            'to_user_name' => $flow['to_user_name'] ?? '',
            'flow_reason' => $flow['reason'] ?? '',
            'handle_status' => $status,
            'handle_reason' => $reason ?? '',
        ], JSON_UNESCAPED_UNICODE);
        EventFlowModel::update($update_data);
        action_log('event_flow_handle', 'mt_event_flow', $flow_id, UID, $details);

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
            $details = json_encode([
                'title' => $event['title'] ?? '',
                'subtitle' => $event['subtitle'] ?? '',
                'creator_name' => $event['creator_name'] ?? '',
                'customer_name' => $event['customer_name'] ?? '',
                'note_content' => $content ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_note_add', 'mt_event_note', $event_id, UID, $details);
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
            $details = json_encode([
                'title' => $oldEvent['title'] ?? '',
                'subtitle' => $oldEvent['subtitle'] ?? '',
                'creator_name' => $oldEvent['creator_name'] ?? '',
                'customer_name' => $oldEvent['customer_name'] ?? '',
                'priority' => $oldEvent['priority'] ?? 1,
                'start_time' => $oldEvent['start_time'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_cancel', 'mt_event', $id, UID, $details);
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
            $details = json_encode([
                'title' => $oldEvent['title'] ?? '',
                'subtitle' => $oldEvent['subtitle'] ?? '',
                'creator_name' => $oldEvent['creator_name'] ?? '',
                'customer_name' => $oldEvent['customer_name'] ?? '',
                'priority' => $oldEvent['priority'] ?? 1,
                'start_time' => $oldEvent['start_time'] ?? '',
            ], JSON_UNESCAPED_UNICODE);
            action_log('event_active', 'mt_event', $id, UID, $details);
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



    public static function getIsNoFeedbackList()
    {
        return [
            0 => '正常',
            1 => '已解决，客户无反馈',
        ];
    }
}