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
            throw new \Exception('该交接已被处理');
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

    public static function complete($id)
    {
        $handover = HandoverModel::where('id', $id)->find();
        if (!$handover) {
            throw new \Exception('交接记录不存在');
        }

        if ($handover['status'] != 1) {
            throw new \Exception('交接未被接收，无法完成');
        }

        $data = [
            'id' => $id,
            'status' => 2,
        ];

        if (HandoverModel::update($data)) {
            action_log('handover_complete', 'mt_handover', $id, UID);
            return true;
        }

        throw new \Exception('完成交接失败');
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
        
        $map = [
            ['is_closed', '=', 0],
            ['is_canceled', '=', 0],
            ['receiver_id', '=', $user_id],
        ];
        
        return EventModel::where($map)->order('create_time desc')->select();
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
}