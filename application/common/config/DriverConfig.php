<?php

namespace app\common\config;

use think\Db;

class DriverConfig extends BaseConfig
{
    //private $table='label';


    public function carColorList()
    {
        return [
            ['id' => 1, 'title' => '黄牌'],
            ['id' => 2, 'title' => '蓝牌'],
        ];
    }

    public function getCarID($id) {
        $carColorList = $this->carColorList();
        foreach ($carColorList as $k=>$v){
            if ($v['id'] == $id){
                return $v['title'];
            }
        }
        return '';
    }

    public function driverRegisterAudit()
    {
        return [
            ['id' => 1, 'title' => '待审核'],
            ['id' => 2, 'title' => '已通过'],
            ['id' => 3, 'title' => '未通过'],
        ];
    }

    public function driverRegisterAuditID($id) {
        $list = $this->driverRegisterAudit();
        foreach ($list as $k=>$v){
            if ($v['id'] == $id){
                return $v['title'];
            }
        }
        return '';
    }

}