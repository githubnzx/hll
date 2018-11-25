<?php
namespace app\driver\model;

use think\Db;
use think\Log;
use think\Model;
use think\Cache;

class DepositMode extends BaseModel
{
    protected $depositOrder = 'deposit_order';

    const STATUS_DEL = 0;
    const USER_TYPE_USER = 1;
    const OPERATE_TYPE_JYJ = 1;
    const OPERATE_TYPE_TYJ = 2;

    public function depositOrderAddGetId($data){
        $curr_time = CURR_TIME;
        $data['create_time'] = $curr_time;
        $data['update_time'] = $curr_time;
        return Db::table($this->depositOrder)->insertGetId($data);
    }

}