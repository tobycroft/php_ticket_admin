<?php

namespace app\stor\action;

use app\stor\model\MaterialSnModel;
use app\stor\model\MaterialModel;

class ProjectAction
{
    public static function getProjectMaterials($projectId)
    {
        $snList = MaterialSnModel::where('project_id', $projectId)->select();
        
        $materialMap = [];
        $materials = [];
        
        foreach ($snList as $snItem) {
            $materialId = $snItem['material_id'];
            if (!isset($materialMap[$materialId])) {
                $material = MaterialModel::getInfo($materialId);
                $materialMap[$materialId] = $material;
                $materials[$materialId] = [
                    'material' => $material,
                    'sns' => []
                ];
            }
            $materials[$materialId]['sns'][] = $snItem['sn'];
        }
        
        return $materials;
    }

    public static function renderProjectMaterials($materials)
    {
        if (empty($materials)) {
            return '<div class="block"><div class="block-header bg-gray-lighter"><h3 class="block-title"><i class="fa fa-cubes mr-5"></i>使用物料</h3></div><div class="block-content text-center py-20"><div class="alert alert-info">该项目暂无使用物料</div></div></div>';
        }
        
        $html = '<div class="block"><div class="block-header bg-gray-lighter"><h3 class="block-title"><i class="fa fa-cubes mr-5"></i>使用物料</h3></div><div class="block-content">';
        
        foreach ($materials as $item) {
            $material = $item['material'];
            $sns = $item['sns'];
            
            $html .= '<div class="panel panel-default mb-10"><div class="panel-heading"><h4 class="panel-title">' . $material['name'] . '</h4></div><div class="panel-body"><div class="row"><div class="col-md-6"><p><strong>所属分类：</strong>' . $material['category_id'] . '</p><p><strong>存放位置：</strong>' . ($material['location'] ?: '-') . '</p><p><strong>单位：</strong>' . ($material['unit'] ?: '-') . '</p></div><div class="col-md-6"><p><strong>SN码列表：</strong></p><div class="tag-list">';
            
            foreach ($sns as $sn) {
                $html .= '<span class="label label-default mr-2">' . $sn . '</span>';
            }
            
            $html .= '</div></div></div></div>';
        }
        
        $html .= '</div></div>';
        return $html;
    }
}