<?php

namespace app\maintenance\action;

use app\maintenance\model\HandoverModel;
use app\maintenance\model\EventModel;
use app\user\model\User as UserModel;
use think\Db;

class HandoverAction
{
    public static function getList($map = [], $page = true)
    {
        $query = HandoverModel::where($map)->order('create_time desc');
        
        if ($page) {
            return $query->paginate();
        }
        
        return $query->select();
    }

    public static function getInfo($id)
    {
        return HandoverModel::where('id', $id)->find();
    }

    public static function add($data)
    {
        $data['creator_id'] = UID;
        $data['creator_name'] = get_nickname(UID);
        
        if (isset($data['event_ids']) && is_array($data['event_ids'])) {
            $data['event_ids'] = implode(',', $data['event_ids']);
        }
        
        if (!empty($data['default_receiver_id'])) {
            $receiver = UserModel::where('id', $data['default_receiver_id'])->find();
            if ($receiver) {
                $data['default_receiver_name'] = $receiver['nickname'];
            }
        }
        
        if ($handover = HandoverModel::create($data)) {
            action_log('handover_add', 'mt_handover', $handover['id'], UID);
            return $handover;
        }
        
        throw new \Exception('创建交接失败');
    }

    public static function receive($id)
    {
        $handover = HandoverModel::where('id', $id)->find();
        if (!$handover) {
            throw new \Exception('交接记录不存在');
        }

        if ($handover['status'] != 0) {
            $status_list = self::getStatusList();
            $status_text = isset($status_list[$handover['status']]) ? $status_list[$handover['status']] : '未知状态';
            throw new \Exception('该交接已被处理，当前状态：' . $status_text);
        }

        $is_forced = 0;
        if ($handover['default_receiver_id'] > 0 && $handover['default_receiver_id'] != UID) {
            $is_forced = 1;
        }

        $data = [
            'id' => $id,
            'status' => 1,
            'actual_receiver_id' => UID,
            'actual_receiver_name' => get_nickname(UID),
            'is_forced' => $is_forced,
            'receive_time' => date('Y-m-d H:i:s'),
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if (HandoverModel::update($data)) {
            $event_ids = explode(',', $handover['event_ids']);
            foreach ($event_ids as $event_id) {
                if ($event_id) {
                    EventModel::update([
                        'id' => $event_id,
                        'receiver_id' => UID,
                        'receiver_name' => get_nickname(UID),
                    ]);
                }
            }
            action_log('handover_receive', 'mt_handover', $id, UID);
            return true;
        }

        throw new \Exception('接收交接失败');
    }

    public static function cancel($id)
    {
        $handover = HandoverModel::where('id', $id)->find();
        if (!$handover) {
            throw new \Exception('交接记录不存在');
        }

        if ($handover['status'] == 2) {
            throw new \Exception('交接已作废');
        }

        $data = [
            'id' => $id,
            'status' => 2,
            'update_time' => date('Y-m-d H:i:s'),
        ];

        if (HandoverModel::update($data)) {
            action_log('handover_cancel', 'mt_handover', $id, UID);
            return true;
        }

        throw new \Exception('作废交接失败');
    }

    public static function delete($id)
    {
        $handover = HandoverModel::where('id', $id)->find();
        if (!$handover) {
            throw new \Exception('交接记录不存在');
        }

        if ($handover['status'] != 0) {
            throw new \Exception('已处理的交接不能删除');
        }

        if (HandoverModel::destroy($id)) {
            action_log('handover_delete', 'mt_handover', $id, UID);
            return true;
        }

        throw new \Exception('删除交接失败');
    }

    public static function getAvailableEvents($user_id = null)
    {
        if ($user_id === null) {
            $user_id = UID;
        }
        
        $events = EventModel::where(function($query) use ($user_id) {
            $query->where('is_closed', 0)
                  ->where('is_canceled', 0)
                  ->where(function($q) use ($user_id) {
                      $q->where('receiver_id', $user_id)
                        ->whereOr('creator_id', $user_id);
                  });
        })->order('create_time desc')->select();
        
        foreach ($events as &$event) {
            $last_handover = HandoverModel::where(function($query) use ($event) {
                $query->where('event_ids', '=', $event['id'])
                      ->whereOr('event_ids', 'like', $event['id'] . ',%')
                      ->whereOr('event_ids', 'like', '%,' . $event['id'])
                      ->whereOr('event_ids', 'like', '%,' . $event['id'] . ',%');
            })->order('create_time desc')->find();
            if ($last_handover) {
                $event['last_handover'] = $last_handover['creator_name'] . ' ' . $last_handover['create_time'];
            } else {
                $event['last_handover'] = '';
            }
        }
        
        return $events;
    }

    public static function getAllAvailableEvents()
    {
        $events = EventModel::where([
            ['is_closed', '=', 0],
            ['is_canceled', '=', 0],
        ])->order('create_time desc')->select();
        
        foreach ($events as &$event) {
            $last_handover = HandoverModel::where(function($query) use ($event) {
                $query->where('event_ids', '=', $event['id'])
                      ->whereOr('event_ids', 'like', $event['id'] . ',%')
                      ->whereOr('event_ids', 'like', '%,' . $event['id'])
                      ->whereOr('event_ids', 'like', '%,' . $event['id'] . ',%');
            })->order('create_time desc')->find();
            if ($last_handover) {
                $event['last_handover'] = $last_handover['creator_name'] . ' ' . $last_handover['create_time'];
            } else {
                $event['last_handover'] = '';
            }
        }
        
        return $events;
    }

    public static function getUnclaimedHandovers()
    {
        $map = [
            ['status', '=', 0],
        ];
        
        return HandoverModel::where($map)->order('create_time desc')->select();
    }

    public static function getUserHandoverStats($user_id, $month = null)
    {
        if ($month === null) {
            $month = date('Y-m');
        }
        
        $start_time = $month . '-01 00:00:00';
        $end_time = date('Y-m-t', strtotime($month)) . ' 23:59:59';
        
        return HandoverModel::where([
            ['creator_id', '=', $user_id],
            ['create_time', 'between', [$start_time, $end_time]],
        ])->count();
    }

    public static function getHandoverStatsByMonth($month)
    {
        $start_time = $month . '-01 00:00:00';
        $end_time = date('Y-m-t', strtotime($month)) . ' 23:59:59';
        
        $maintenance_role_ids = [4, 5, 6, 7];
        $maintenance_user_ids = UserModel::where('role', 'in', $maintenance_role_ids)->column('id');
        
        return HandoverModel::where([
            ['create_time', 'between', [$start_time, $end_time]],
            ['creator_id', 'in', $maintenance_user_ids],
        ])->count();
    }

    public static function getStatusList()
    {
        return HandoverModel::getStatusList();
    }

    public static function getIsForcedList()
    {
        return HandoverModel::getIsForcedList();
    }

    public static function getTodayUnclaimedHandovers()
    {
        $today_start = date('Y-m-d') . ' 00:00:00';
        $today_end = date('Y-m-d') . ' 23:59:59';
        
        return HandoverModel::where([
            ['status', '=', 0],
            ['create_time', 'between', [$today_start, $today_end]],
        ])->order('create_time desc')->select();
    }

    public static function getCanceledHandovers()
    {
        return HandoverModel::where([
            ['status', '=', 2],
        ])->order('update_time desc')->select();
    }

    public static function getMySentHandovers($user_id = null)
    {
        if ($user_id === null) {
            $user_id = UID;
        }
        
        return HandoverModel::where([
            ['creator_id', '=', $user_id],
            ['status', '<>', 2],
        ])->order('create_time desc')->select();
    }

    public static function getMyReceivedHandovers($user_id = null)
    {
        if ($user_id === null) {
            $user_id = UID;
        }
        
        return HandoverModel::where([
            ['actual_receiver_id', '=', $user_id],
            ['status', '<>', 2],
        ])->order('create_time desc')->select();
    }

    public static function getMyUnclaimedHandovers($user_id = null)
    {
        if ($user_id === null) {
            $user_id = UID;
        }
        
        return HandoverModel::where([
            ['default_receiver_id', '=', $user_id],
            ['status', '=', 0],
        ])->order('create_time desc')->select();
    }

    public static function getMyUnfinishedHandoverCount($user_id = null)
    {
        if ($user_id === null) {
            $user_id = UID;
        }
        
        return HandoverModel::where([
            ['default_receiver_id', '=', $user_id],
            ['status', '=', 0],
        ])->count();
    }

    public static function getUnassignedHandoverCount()
    {
        return HandoverModel::where([
            ['default_receiver_id', '=', 0],
            ['status', '=', 0],
        ])->count();
    }

    public static function hasHandoverToday($user_id = null)
    {
        if ($user_id === null) {
            $user_id = UID;
        }
        
        $today = date('Y-m-d');
        return HandoverModel::where([
            ['creator_id', '=', $user_id],
            ['create_time', 'like', $today . '%'],
        ])->count() > 0;
    }

    public static function edit($id, $data)
    {
        $handover = HandoverModel::where('id', $id)->find();
        if (!$handover) {
            throw new \Exception('交接记录不存在');
        }

        if ($handover['status'] != 0) {
            throw new \Exception('已处理的交接不能修改');
        }

        if (!empty($data['default_receiver_id'])) {
            $receiver = UserModel::where('id', $data['default_receiver_id'])->find();
            if ($receiver) {
                $data['default_receiver_name'] = $receiver['nickname'];
            }
        }

        if (HandoverModel::update($data)) {
            action_log('handover_edit', 'mt_handover', $id, UID);
            return true;
        }

        throw new \Exception('修改交接失败');
    }
}