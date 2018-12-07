<?php

namespace app\common\config;

use think\Db;

class DriverConfig extends BaseConfig
{
    //private $table='label';


    public function carColorList()
    {
        return [
            ['id' => 0, 'title' => '未知'],
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

    public function truckPrice()
    {
        return [
            ['id' => 1, 'starting_price' => "35", "excess_fee" => "3"], // 小型面包车
            ['id' => 2, 'starting_price' => "49", "excess_fee" => "4"], // 小型平板车
            ['id' => 3, 'starting_price' => "49", "excess_fee" => "4"], // 小型厢式货车
            ['id' => 4, 'starting_price' => "95", "excess_fee" => "5"], // 中型厢式货车
            ['id' => 5, 'starting_price' => "95", "excess_fee" => "5"], // 中型平板车
            ['id' => 6, 'starting_price' => "95", "excess_fee" => "5"], // 中型高栏
        ];
    }

    public function truckPriceId($id) {
        $list = $this->truckPrice();
        foreach ($list as $k=>$v){
            if ($v['id'] == $id){
                return $v;
            }
        }
        return '';
    }

    public function truckTypeName()
    {
        return [
            ['id' => 0, 'title' => "未知"],
            ['id' => 1, 'title' => "小型面包"],
            ['id' => 2, 'title' => "小型平板"],
            ['id' => 3, 'title' => "小型箱货"],
            ['id' => 4, 'title' => "中型平板"],
            ['id' => 5, 'title' => "中型箱货"],
            ['id' => 6, 'title' => "中型高栏"],
        ];
    }

    public function truckTypeNameId($id) {
        $list = $this->truckTypeName();
        foreach ($list as $k=>$v){
            if ($v['id'] == $id){
                return $v["title"];
            }
        }
        return '';
    }

    public function userNameType()
    {
        return [
            ['id' => 0, 'title' => "未知"],
            ['id' => 1, 'title' => "先生"],
            ['id' => 2, 'title' => "女士"]
        ];
    }

    public function userNameTypeId($id) {
        $list = $this->userNameType();
        foreach ($list as $k=>$v){
            if ($v['id'] == $id){
                return $v["title"];
            }
        }
        return '';
    }


}