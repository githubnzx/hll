<?php

namespace app\common\logic;

use think\Request;

/**
 * Created by PhpStorm.
 * User: nzx
 * Date: 2018/09/09
 * Time: 上午10:30
 */
class PageLogic extends BaseLogic
{
    private $page = 1;
    private $pageSize = 10;
    public function __construct()
    {
        $this->page = Request::instance()->param('pageNumber', 1);
        $this->pageSize = Request::instance()->param('pageSize', 10);
    }

    public function getPages()
    {
        return $this->page . ',' . $this->pageSize;
    }

    public function checkHasMore($list)
    {
        return count($list) < $this->pageSize ? 0 : 1;
    }

}