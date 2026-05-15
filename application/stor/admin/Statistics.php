<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\stor\model\MaterialModel;
use app\stor\model\MaterialSnModel;
use app\stor\model\StockModel;
use app\stor\model\InboundModel;
use app\stor\model\OutboundModel;
use app\stor\model\RepairModel;
use app\stor\model\ProjectModel;

class Statistics extends Admin
{
    public function index()
    {
        $material_total = MaterialModel::where(['status' => 1])->count();
        $material_scrap = MaterialModel::where(['status' => 2])->count();
        
        $sn_total = MaterialSnModel::count();
        $sn_available = MaterialSnModel::where('status', 1)->whereNull('project_id')->count();
        $sn_used = MaterialSnModel::where('status', 1)->whereNotNull('project_id')->count();
        $sn_repair = MaterialSnModel::where('status', 2)->count();
        $sn_scrap = MaterialSnModel::where('status', 3)->count();
        
        $stock_total = StockModel::sum('quantity') ?: 0;
        
        $inbound_total = InboundModel::count();
        $outbound_total = OutboundModel::where(['status' => 1])->count();
        
        $repair_pending = RepairModel::where('status', 1)->count();
        $repair_processing = RepairModel::where('status', 2)->count();
        $repair_completed = RepairModel::where('status', 3)->count();
        
        $project_total = ProjectModel::where(['status' => 1])->count();

        $this->assign('material_total', $material_total);
        $this->assign('material_scrap', $material_scrap);
        $this->assign('sn_total', $sn_total);
        $this->assign('sn_available', $sn_available);
        $this->assign('sn_used', $sn_used);
        $this->assign('sn_repair', $sn_repair);
        $this->assign('sn_scrap', $sn_scrap);
        $this->assign('stock_total', $stock_total);
        $this->assign('inbound_total', $inbound_total);
        $this->assign('outbound_total', $outbound_total);
        $this->assign('repair_pending', $repair_pending);
        $this->assign('repair_processing', $repair_processing);
        $this->assign('repair_completed', $repair_completed);
        $this->assign('project_total', $project_total);
        $this->assign('page_title', '仓管统计');

        return $this->fetch('statistics/index');
    }
}