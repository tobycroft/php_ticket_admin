<?php

namespace app\stor\admin;

use app\admin\controller\Admin;
use app\common\builder\ZBuilder;
use app\stor\model\MaterialModel;
use app\stor\model\StockModel;

class Stock extends Admin
{
    public function index()
    {
        $map = $this->getMap();

        $stock_list = StockModel::getStockList($map);

        $material_list = MaterialModel::getList(['status' => 1]);
        $material_map = [];
        foreach ($material_list as $item) {
            $material_map[$item['id']] = $item['name'];
        }

        return ZBuilder::make('table')
            ->setPageTitle('库存管理')
            ->setTableName('stor_stock')
            ->setSearch(['material_id' => '物料'])
            ->addColumns([
                ['id', 'ID'],
                ['material_id', '物料名称', $material_map],
                ['quantity', '库存数量'],
                ['location', '存放位置'],
                ['update_time', '更新时间', 'datetime']
            ])
            ->setRowList($stock_list)
            ->fetch();
    }
}