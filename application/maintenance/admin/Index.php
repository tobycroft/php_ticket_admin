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
        // 设置页面标题
        return ZBuilder::make('blank')
            ->setPageTitle('运维系统')
            ->setPageTips('欢迎使用运维系统，此页面将作为 dashboard 使用', 'info')
            ->fetch();
    }
}
