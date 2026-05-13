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
        return ZBuilder::make('table')
            ->setPageTitle('运维系统')
            ->setPageTips('欢迎使用运维系统', 'info')
            ->fetch();
    }
}