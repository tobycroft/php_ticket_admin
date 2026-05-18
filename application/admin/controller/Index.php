<?php


namespace app\admin\controller;

use app\common\builder\ZBuilder;
use app\user\model\User as UserModel;
use think\Db;
use think\facade\Cache;
use think\facade\Env;
use think\helper\Hash;

/**
 * 后台默认控制器
 * @package app\admin\controller
 */
class Index extends Admin
{
    /**
     * 后台首页
     * @return string
     */
    public function index()
    {
        $admin_pass = Db::name('admin_user')
            ->where('id', 1)
            ->value('password');

        if (UID == 1 && $admin_pass && Hash::check('admin', $admin_pass)) {
            $this->assign('default_pass', 1);
        }
        
        $web_site_title = config('web_site_title') ?: 'ThinkPHP';
        $this->assign('web_site_title', $web_site_title);
        
        $modules = $this->getAccessibleModules();
        $this->assign('modules', $modules);
        
        // 获取当前值班人员
        $currentDutyUsers = $this->getCurrentDutyUsers();
        $this->assign('current_duty_users', $currentDutyUsers);
        
        // 获取运维工单统计（本月每天的工单数量）
        $eventStats = $this->getEventStats();
        $this->assign('event_stats', $eventStats);
        
        // 获取仓管物料统计
        $materialStats = $this->getMaterialStats();
        $this->assign('material_stats', $materialStats);
        
//        $this->redirect("/admin/index/profile");
        return $this->fetch();
    }
    
    /**
     * 获取当前值班人员（根据当前时间匹配）
     */
    private function getCurrentDutyUsers()
    {
        $today = date('Y-m-d');
        $currentTime = date('H:i:s');
        
        // 获取班次时间配置
        $shiftPatterns = Db::query("SELECT id, start_time, end_time FROM mt_shift_pattern WHERE status = 1");
        $shiftMap = [];
        foreach ($shiftPatterns as $pattern) {
            $shiftMap[$pattern['id']] = [
                'start_time' => $pattern['start_time'],
                'end_time' => $pattern['end_time']
            ];
        }
        
        // 获取今天所有值班人员
        $schedules = Db::query("SELECT ds.user_name, ds.shift_name, ds.shift_id, u.mobile FROM mt_daily_schedule ds LEFT JOIN dp_admin_user u ON u.id = ds.user_id WHERE ds.schedule_date = ? AND ds.status = 1", [$today]);
        
        // 根据当前时间筛选正在值班的人员
        $currentDutyUsers = [];
        foreach ($schedules as $schedule) {
            $shiftId = $schedule['shift_id'];
            if (!isset($shiftMap[$shiftId])) {
                continue;
            }
            $startTime = $shiftMap[$shiftId]['start_time'];
            $endTime = $shiftMap[$shiftId]['end_time'];
            
            // 判断当前时间是否在班次时间范围内
            // 特殊处理夜班（结束时间早于开始时间的情况，如21:00-09:00）
            if ($startTime < $endTime) {
                // 正常班次（如9:00-21:00）
                if ($currentTime >= $startTime && $currentTime < $endTime) {
                    $currentDutyUsers[] = $schedule;
                }
            } else {
                // 夜班（如21:00-09:00）
                if ($currentTime >= $startTime || $currentTime < $endTime) {
                    $currentDutyUsers[] = $schedule;
                }
            }
        }
        
        return $currentDutyUsers;
    }
    
    /**
     * 获取运维工单统计（本月每天的工单数量）
     */
    private function getEventStats()
    {
        $today = date('Y-m-d');
        $firstDay = date('Y-m-01');
        
        $data = Db::table('mt_event')
            ->where('start_time', '>=', $firstDay . ' 00:00:00')
            ->where('start_time', '<=', $today . ' 23:59:59')
            ->field("DATE_FORMAT(start_time, '%d') as day, COUNT(*) as count")
            ->group('day')
            ->order('day')
            ->select();
        
        $result = [];
        for ($i = 1; $i <= date('d'); $i++) {
            $count = 0;
            foreach ($data as $item) {
                if ((int)$item['day'] == $i) {
                    $count = (int)$item['count'];
                    break;
                }
            }
            $result[] = ['day' => $i, 'count' => $count];
        }
        
        return $result;
    }
    
    /**
     * 获取仓管物料统计
     */
    private function getMaterialStats()
    {
        // 物料状态：1-正常(未分配), 2-已分配, 3-维修中, 4-作废
        $statusStats = Db::table('stor_material_sn')
            ->field('status, COUNT(*) as count')
            ->group('status')
            ->select();
        
        $stats = [
            'repairing' => 0,      // 在维修
            'allocated' => 0,      // 已分配
            'unallocated' => 0,    // 未分配
            'scrapped' => 0,       // 已作废
            'total' => 0           // 总量
        ];
        
        foreach ($statusStats as $item) {
            $status = (int)$item['status'];
            $count = (int)$item['count'];
            $stats['total'] += $count;
            
            switch ($status) {
                case 1:
                    $stats['unallocated'] = $count;
                    break;
                case 2:
                    $stats['allocated'] = $count;
                    break;
                case 3:
                    $stats['repairing'] = $count;
                    break;
                case 4:
                    $stats['scrapped'] = $count;
                    break;
            }
        }
        
        // 获取物料类型数量
        $categoryCount = Db::table('stor_category')->where('status', 1)->count();
        
        return [
            'stats' => $stats,
            'category_count' => $categoryCount
        ];
    }
    
    private function getAccessibleModules()
    {
        $modules = Db::name('admin_menu')
            ->where('pid', 0)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->field('id, title, icon, url_value')
            ->select();
        
        $accessible = [];
        foreach ($modules as $module) {
            if ($this->checkModuleAccess($module['id'])) {
                $module['stats'] = $this->getModuleStats($module['url_value']);
                $accessible[] = $module;
            }
        }
        return $accessible;
    }
    
    private function checkModuleAccess($menuId)
    {
        if (UID == 1) {
            return true;
        }
        return check_auth_node(UID, 'admin', $menuId);
    }
    
    private function getModuleStats($urlValue)
    {
        $stats = [
            'total' => 0,
            'pending' => 0,
            'completed' => 0
        ];
        
        $module = explode('/', $urlValue)[0];
        
        switch ($module) {
            case 'maintenance':
                $stats['total'] = Db::table('mt_event')->count();
                $stats['pending'] = Db::table('mt_event')->where('is_closed', 0)->count();
                $stats['completed'] = Db::table('mt_event')->where('is_closed', 1)->count();
                break;
            case 'stor':
                $stats['total'] = Db::table('stor_material')->count();
                $stats['pending'] = Db::table('stor_stock')->where('quantity', '<', 10)->count();
                $stats['completed'] = Db::table('stor_inbound')->count();
                break;
            case 'coop':
                $stats['total'] = Db::table('mt_cooperate')->count();
                $stats['pending'] = Db::table('mt_cooperate')->where('is_closed', 0)->count();
                $stats['completed'] = Db::table('mt_cooperate')->where('is_closed', 1)->count();
                break;
            case 'user':
                $stats['total'] = Db::name('admin_user')->count();
                $stats['pending'] = Db::name('admin_user')->where('status', 0)->count();
                $stats['completed'] = Db::name('admin_user')->where('status', 1)->count();
                break;
        }
        
        return $stats;
    }

    /**
     * 清空系统缓存
     */
    public function wipeCache()
    {
        $wipe_cache_type = config('wipe_cache_type');
        if (!empty($wipe_cache_type)) {
            foreach ($wipe_cache_type as $item) {
                switch ($item) {
                    case 'TEMP_PATH':
                        array_map('unlink', glob(Env::get('runtime_path') . 'temp/*.*'));
                        break;
                    case 'LOG_PATH':
                        $dirs = (array)glob(Env::get('runtime_path') . 'log/*');
                        foreach ($dirs as $dir) {
                            array_map('unlink', glob($dir . '/*.log'));
                        }
                        array_map('rmdir', $dirs);
                        break;
                    case 'CACHE_PATH':
                        array_map('unlink', glob(Env::get('runtime_path') . 'cache/*.*'));
                        break;
                }
            }
            Cache::clear();
            $this->success('清空成功');
        } else {
            $this->error('请在系统设置中选择需要清除的缓存类型');
        }
    }

    /**
     * 个人设置
     */
    public function profile()
    {
        // 保存数据
        if ($this->request->isPost()) {
            $data = $this->request->post();

            $data['nickname'] == '' && $this->error('昵称不能为空');
            $data['id'] = UID;

            // 如果没有填写密码，则不更新密码
            if ($data['password'] == '') {
                unset($data['password']);
            }

            $UserModel = new UserModel();
            if ($user = $UserModel->allowField(['nickname', 'email', 'password', 'mobile', 'avatar'])
                ->update($data)) {
                // 记录行为
                action_log('user_edit', 'admin_user', UID, UID, get_nickname(UID));
                $this->success('编辑成功');
            } else {
                $this->error('编辑失败');
            }
        }

        // 获取数据
        $info = UserModel::where('id', UID)
            ->field('password', true)
            ->find();

        // 使用ZBuilder快速创建表单
        return ZBuilder::make('form')
            ->addFormItems([ // 批量添加表单项
                ['static', 'username', '用户名', '不可更改'], ['text', 'nickname', '昵称', '可以是中文'], ['text', 'email', '邮箱', ''], ['password', 'password', '密码', '必填，6-20位'], ['text', 'mobile', '手机号'], ['image', 'avatar', '头像']])
            ->setFormData($info) // 设置表单数据
            ->fetch();
    }

    /**
     * 检查版本更新
     * @return \think\response\Json
     * @throws \think\db\exception\BindParamException
     * @throws \think\exception\PDOException
     */
    public function checkUpdate()
    {
        return json(['update' => '', 'auth' => "thinkphp"]);
    }
}