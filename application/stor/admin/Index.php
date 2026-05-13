<?php

namespace app\stor\admin;

use app\admin\controller\Admin;

class Index extends Admin
{
    public function index()
    {
        return $this->redirect('stor/material/index');
    }
}