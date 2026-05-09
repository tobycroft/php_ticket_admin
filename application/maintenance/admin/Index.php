<?php

namespace app\maintenance\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;

/**
 * 运维系统首页
 * @package app\maintenance\admin
 */
class Index extends Admin
{
    /**
     * 首页
     * @return mixed
     */
    public function index()
    {
        // 使用 table 构建器创建首页，预留 dashboard 功能
        return ZBuilder::make('table')
            ->setPageTitle('运维系统')
            ->setPageTips('欢迎使用运维系统，此页面将作为 dashboard 使用', 'info')
//            ->addColumn('tips', '提示信息')
//            ->setRowList([['tips' => 'Dashboard 功能开发中...']])
            ->fetch();
    }
}