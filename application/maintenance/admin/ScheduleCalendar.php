<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\ShiftPatternModel;
use app\maintenance\model\DailyScheduleModel;
use app\user\model\User as UserModel;

class ScheduleCalendar extends Admin
{
    public function index($year = null, $month = null)
    {
        if ($year === null) {
            $year = date('Y');
        }
        if ($month === null) {
            $month = date('m');
        }

        $shift_list = ShiftPatternModel::getActiveList();
        $user_list = UserModel::where('status', 1)->whereIn('role', [3, 4, 5, 6, 7])->field('id, nickname, color')->select();

        $schedules = DailyScheduleModel::getByMonth($year, $month);

        $schedule_map = [];
        foreach ($schedules as $schedule) {
            $key = $schedule['schedule_date'] . '_' . $schedule['shift_id'];
            if (!isset($schedule_map[$key])) {
                $schedule_map[$key] = [];
            }
            $schedule_map[$key][$schedule['user_id']] = $schedule;
        }

        $first_day = strtotime("$year-$month-01");
        $last_day = strtotime(date('Y-m-t', $first_day));
        
        $days_in_month = date('t', $first_day);
        
        $calendar_days = [];
        
        for ($day = 1; $day <= $days_in_month; $day++) {
            $date = date('Y-m-d', strtotime("$year-$month-$day"));
            $weekday = date('w', strtotime($date));
            // 将0（星期日）转换为7，其他不变
            $weekday = $weekday == 0 ? 7 : $weekday;
            $calendar_days[] = ['day' => $day, 'date' => $date, 'weekday' => $weekday];
        }
        
        $calendar_weeks = [];
        $current_week = [];
        
        $first_weekday = date('w', $first_day);
        // 将0（星期日）转换为7，其他不变
        $first_weekday = $first_weekday == 0 ? 7 : $first_weekday;
        // 填充月初空白（1表示星期一）
        for ($i = 1; $i < $first_weekday; $i++) {
            $current_week[] = ['day' => 0, 'date' => '', 'weekday' => $i];
        }
        
        foreach ($calendar_days as $day) {
            $current_week[] = $day;
            if (count($current_week) == 7) {
                $calendar_weeks[] = $current_week;
                $current_week = [];
            }
        }
        
        if (!empty($current_week)) {
            $next_weekday = count($current_week) + 1;
            while (count($current_week) < 7) {
                $current_week[] = ['day' => 0, 'date' => '', 'weekday' => $next_weekday];
                $next_weekday++;
            }
            $calendar_weeks[] = $current_week;
        }

        $prev_year = $year;
        $prev_month = $month - 1;
        if ($prev_month < 1) {
            $prev_month = 12;
            $prev_year--;
        }

        $next_year = $year;
        $next_month = $month + 1;
        if ($next_month > 12) {
            $next_month = 1;
            $next_year++;
        }

        $weekdays = [1 => '星期一', 2 => '星期二', 3 => '星期三', 4 => '星期四', 5 => '星期五', 6 => '星期六', 7 => '星期日'];

        $this->assign('year', $year);
        $this->assign('month', $month);
        $this->assign('prev_year', $prev_year);
        $this->assign('prev_month', $prev_month);
        $this->assign('next_year', $next_year);
        $this->assign('next_month', $next_month);
        $this->assign('shift_list', $shift_list);
        $this->assign('user_list', $user_list);
        $this->assign('calendar_days', $calendar_days);
        $this->assign('calendar_weeks', $calendar_weeks);
        $this->assign('schedule_map', $schedule_map);
        $this->assign('schedule_list', $schedules);
        $this->assign('weekdays', $weekdays);

        return $this->fetch();
    }

    public function saveSchedule()
    {
        if (!$this->request->isAjax()) {
            return json(['code' => 0, 'msg' => '非法请求']);
        }

        $data = $this->request->post();

        if (empty($data['date'])) {
            return json(['code' => 0, 'msg' => '请选择日期']);
        }

        if (empty($data['shift_id'])) {
            return json(['code' => 0, 'msg' => '请选择班次']);
        }

        $shift = ShiftPatternModel::where('id', $data['shift_id'])->find();
        if (!$shift) {
            return json(['code' => 0, 'msg' => '班次不存在']);
        }

        try {
            DailyScheduleModel::where('schedule_date', $data['date'])
                ->where('shift_id', $data['shift_id'])
                ->delete();

            if (!empty($data['user_ids'])) {
                foreach ($data['user_ids'] as $user_id) {
                    $user = UserModel::where('id', $user_id)->find();
                    if ($user) {
                        $schedule_data = [
                            'user_id' => $user_id,
                            'user_name' => $user['nickname'],
                            'schedule_date' => $data['date'],
                            'shift_id' => $data['shift_id'],
                            'shift_name' => $shift['name'],
                            'status' => 1,
                        ];
                        DailyScheduleModel::create($schedule_data);
                    }
                }
            }
        } catch (\Exception $e) {
            return json(['code' => 0, 'msg' => $e->getMessage()]);
        }
        
        return json(['code' => 1, 'msg' => '排班成功']);
    }

    public function getSchedule($date = '', $shift_id = '')
    {
        if (empty($date) || empty($shift_id)) {
            return json(['code' => 0, 'msg' => '参数错误']);
        }

        $schedules = DailyScheduleModel::where('schedule_date', $date)
            ->where('shift_id', $shift_id)
            ->where('status', 1)
            ->select();

        $user_ids = [];
        foreach ($schedules as $schedule) {
            $user_ids[] = $schedule['user_id'];
        }

        return json(['code' => 1, 'data' => $user_ids]);
    }
}