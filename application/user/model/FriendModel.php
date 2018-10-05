<?php
/**
 * Created by PhpStorm.
 * User: php_s
 * Date: 2018/1/30
 * Time: 16:03
 */

namespace app\user\model;

use app\user\model\OrderModel;
use think\Db;
use think\Exception;
use think\exception\HttpException;

class FriendModel extends BaseModel
{
    const IS_DEL = 0; //1:删除 0:未删除
    private $table = 'user_friend';

    public function friendAdd($data){
        $data["create_time"] = CURR_TIME;
        $data["update_time"] = CURR_TIME;
        return Db::table($this->table)->insert($data);
    }

    public function friendList($where, $fields = "*"){
        $where["is_del"] = self::IS_DEL;
        return Db::table($this->table)->field($fields)->where($where)->select();
    }

    public function friendFind($where, $fields = "*"){
        $where["is_del"] = self::IS_DEL;
        return Db::table($this->table)->field($fields)->where($where)->find();
    }

    public function friendEdit($where, $param){
        if(!isset($param["update_time"])){
            $param["update_time"] = CURR_TIME;
        }
        return Db::table($this->table)->where($where)->update($param);
    }



}