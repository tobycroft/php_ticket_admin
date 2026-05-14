<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\stor\model\MaterialModel;
use app\stor\model\CategoryModel;
use app\stor\model\ProjectModel;
use app\stor\model\StockModel;
use app\stor\model\InboundModel;
use app\stor\model\OutboundModel;

class Dashboard extends Admin
{
    public function index()
    {
        $material_count = MaterialModel::where(['status' => 1])->count();
        $scrap_material_count = MaterialModel::where(['status' => 2])->count();
        $category_count = CategoryModel::where(['status' => 1])->count();
        $project_count = ProjectModel::where(['status' => 1])->count();
        
        $stock_total = StockModel::sum('quantity');
        
        $today_inbound_count = InboundModel::where('create_time', 'like', date('Y-m-d') . '%')->count();
        $today_outbound_count = OutboundModel::where('create_time', 'like', date('Y-m-d') . '%')->count();

        $this->assign('material_count', $material_count);
        $this->assign('scrap_material_count', $scrap_material_count);
        $this->assign('category_count', $category_count);
        $this->assign('project_count', $project_count);
        $this->assign('stock_total', $stock_total);
        $this->assign('today_inbound_count', $today_inbound_count);
        $this->assign('today_outbound_count', $today_outbound_count);
        $this->assign('page_title', '信息面板');

        return $this->fetch('dashboard/index');
    }
}