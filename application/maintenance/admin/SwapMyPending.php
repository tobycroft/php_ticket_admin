<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\maintenance\model\UserSwapModel;
use app\maintenance\model\DailyScheduleModel;
use app\user\model\User as UserModel;

class SwapMyPending extends Admin
{
    public function index()
    {
        cookie('__forward__', $_SERVER['REQUEST_URI']);

        $map = [
            ['user_id', '=', UID],
            ['status', '=', 0]
        ];
        $data_list = UserSwapModel::where($map)->order('create_time desc')->paginate();

        $status_list = UserSwapModel::getStatusList();

        foreach ($data_list as &$item) {
            $item['status_text'] = isset($status_list[$item['status']]) ? $status_list[$item['status']] : '';
        }

        return ZBuilder::make('table')
            ->setPageTitle('我的待审核调班')
            ->setTableName('mt_user_swap')
            ->addColumns([
                ['id', 'ID'],
                ['target_user_name', '调换对象'],
                ['swap_date', '调换日期'],
                ['reason', '理由'],
                ['status_text', '状态'],
                ['right_button', '操作', 'btn']
            ])
            ->addTopButtons('add')
            ->addRightButtons(['edit', 'delete'])
            ->setRowList($data_list)
            ->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['target_user_id'])) {
                $this->error('请选择调换对象');
            }

            if (empty($data['swap_date'])) {
                $this->error('请选择调换日期');
            }

            if ($data['target_user_id'] == UID) {
                $this->error('不能与自己调换');
            }

            $target_user = UserModel::where('id', $data['target_user_id'])->find();
            if (!$target_user) {
                $this->error('调换对象不存在');
            }

            $has_schedule = DailyScheduleModel::where('user_id', $data['target_user_id'])
                ->where('schedule_date', $data['swap_date'])
                ->where('status', 1)
                ->find();
            if (!$has_schedule) {
                $this->error('调换对象当天没有排班，无法申请调班');
            }

            $data['user_id'] = UID;
            $data['user_name'] = get_nickname(UID);
            $data['target_user_name'] = $target_user['nickname'];
            $data['status'] = 0;

            try {
                UserSwapModel::create($data);
                if ($this->request->isAjax()) {
                    return json(['code' => 1, 'msg' => '申请成功', 'url' => url('index')]);
                }
                $this->success('申请成功', url('index'));
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }

        $user_list = UserModel::where('status', 1)->whereIn('role', [3, 4, 5, 6, 7])->where('id', '<>', UID)->column('nickname', 'id');

        return ZBuilder::make('form')
            ->setPageTitle('新增调班申请')
            ->addFormItems([
                ['select', 'target_user_id', '调换对象', '必填', $user_list],
                ['date', 'swap_date', '调换日期', '必填'],
                ['textarea', 'reason', '理由', '必填']
            ])
            ->fetch();
    }

    public function edit($id = null)
    {
        if ($id === null) {
            $this->error('参数错误');
        }

        $info = UserSwapModel::where('id', $id)->where('user_id', UID)->find();
        if (!$info) {
            $this->error('记录不存在');
        }

        if ($info['status'] != 0) {
            $this->error('该申请已处理，无法修改');
        }

        if ($this->request->isPost()) {
            $data = $this->request->post();

            if (empty($data['target_user_id'])) {
                $this->error('请选择调换对象');
            }

            if (empty($data['swap_date'])) {
                $this->error('请选择调换日期');
            }

            if ($data['target_user_id'] == UID) {
                $this->error('不能与自己调换');
            }

            $target_user = UserModel::where('id', $data['target_user_id'])->find();
            if (!$target_user) {
                $this->error('调换对象不存在');
            }

            $has_schedule = DailyScheduleModel::where('user_id', $data['target_user_id'])
                ->where('schedule_date', $data['swap_date'])
                ->where('status', 1)
                ->find();
            if (!$has_schedule) {
                $this->error('调换对象当天没有排班，无法申请调班');
            }

            $data['target_user_name'] = $target_user['nickname'];

            try {
                UserSwapModel::update($data);
                if ($this->request->isAjax()) {
                    return json(['code' => 1, 'msg' => '修改成功', 'url' => url('index')]);
                }
                $this->success('修改成功', url('index'));
            } catch (\Exception $e) {
                if ($this->request->isAjax()) {
                    return json(['code' => 0, 'msg' => $e->getMessage()]);
                }
                $this->error($e->getMessage());
            }
        }

        $user_list = UserModel::where('status', 1)->whereIn('role', [3, 4, 5, 6, 7])->where('id', '<>', UID)->column('nickname', 'id');

        return ZBuilder::make('form')
            ->setPageTitle('编辑调班申请')
            ->addFormItems([
                ['hidden', 'id'],
                ['select', 'target_user_id', '调换对象', '必填', $user_list],
                ['date', 'swap_date', '调换日期', '必填'],
                ['textarea', 'reason', '理由', '必填']
            ])
            ->setFormData($info)
            ->fetch();
    }

    public function delete($ids = null)
    {
        if (empty($ids)) {
            $this->error('请选择要删除的记录');
        }

        try {
            UserSwapModel::where('user_id', UID)->destroy($ids);
            if ($this->request->isAjax()) {
                return json(['code' => 1, 'msg' => '删除成功']);
            }
            $this->success('删除成功');
        } catch (\Exception $e) {
            if ($this->request->isAjax()) {
                return json(['code' => 0, 'msg' => $e->getMessage()]);
            }
            $this->error($e->getMessage());
        }
    }
}