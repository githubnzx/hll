<?php
// +----------------------------------------------------------------------
// | snake
// +----------------------------------------------------------------------
// | Copyright (c) 2016~2022 http://baiyf.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: NickBai <1902822973@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;


use app\admin\model\RoleModel;
use app\admin\model\UserModel;
use app\user\model\UsersModel;
use app\admin\model\OrderModel;
use app\admin\model\DriverModel;

ob_clean();

class User extends Base
{
    // 用户列表
    public function lst() {

        $type = request()->post('type_id/d', '');
        $sports_ids = request()->post('sports_ids/d', '');
        if (!$type || !$sports_ids) return error_out([], '参数错误');
        $list = LableModel::priceList($type,$sports_ids);
        foreach ($list as $k=>&$v){
            $v['user_max_salary']=$v['max_salary']+$v['accumu_salary'];
            $v['user_min_salary']=$v['min_salary']+$v['accumu_salary'];
            $v['type_id'] = CourseModel::typeName($v['type_id']);
            $v['level_id'] = CourseModel::levelName($v['level_id']);
        }

        return json(['list' => $list, 'msg' => '']);
    }



    //用户列表
    public function index()
    {
        if(request()->isAjax()){

            $param = input('param.');

            $limit = $param['pageSize'];
            $offset = ($param['pageNumber'] - 1) * $limit;

            $where = [];
            if (!empty($param['searchText'])) {
                $where['user_name'] = ['like', '%' . $param['searchText'] . '%'];
            }
            $user = new UserModel();
            $selectResult = $user->getUsersByWhere($where, $offset, $limit);

            $status = config('user_status');

            // 拼装参数
            foreach($selectResult as $key=>$vo){

                $selectResult[$key]['last_login_time'] = date('Y-m-d H:i:s', $vo['last_login_time']);
                $selectResult[$key]['status'] = $status[$vo['status']];

                if( 1 == $vo['id'] ){
                    $selectResult[$key]['operate'] = '';
                    continue;
                }
                $selectResult[$key]['operate'] = showOperate($this->makeButton($vo['id']));
            }

            $return['total'] = $user->getAllUsers($where);  //总数据
            $return['rows'] = $selectResult;

            return json($return);
        }

        return $this->fetch();
    }

    // 添加用户
    public function userAdd()
    {
        if(request()->isPost()){

            $param = input('post.');

            $param['password'] = md5($param['password'] . config('salt'));
            $param['head'] = '/static/admin/images/profile_small.jpg'; // 默认头像

            $user = new UserModel();
            $flag = $user->insertUser($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $role = new RoleModel();
        $this->assign([
            'role' => $role->getRole(),
            'status' => config('user_status')
        ]);

        return $this->fetch();
    }

    // 编辑用户
    public function userEdit()
    {
        $user = new UserModel();

        if(request()->isPost()){

            $param = input('post.');

            if(empty($param['password'])){
                unset($param['password']);
            }else{
                $param['password'] = md5($param['password'] . config('salt'));
            }
            $flag = $user->editUser($param);

            return json(msg($flag['code'], $flag['data'], $flag['msg']));
        }

        $id = input('param.id');
        $role = new RoleModel();

        $this->assign([
            'user' => $user->getOneUser($id),
            'status' => config('user_status'),
            'role' => $role->getRole()
        ]);
        return $this->fetch();
    }

    // 删除用户
    public function userDel()
    {
        $id = input('param.id');

        $role = new UserModel();
        $flag = $role->delUser($id);
        return json(msg($flag['code'], $flag['data'], $flag['msg']));
    }

    /**
     * 拼装操作按钮
     * @param $id
     * @return array
     */
    private function makeButton($id)
    {
        return [
            '编辑' => [
                'auth' => 'user/useredit',
                'href' => url('user/userEdit', ['id' => $id]),
                'btnStyle' => 'primary',
                'icon' => 'fa fa-paste'
            ],
            '删除' => [
                'auth' => 'user/userdel',
                'href' => "javascript:userDel(" .$id .")",
                'btnStyle' => 'danger',
                'icon' => 'fa fa-trash-o'
            ]
        ];
    }

    // 用户统计
    public function counts(){
        $date_start= request()->post('date_start/s', '');
        $date_end  = request()->post('date_end/s', '');
        $where = [];
        // 用户统计
        //$user = new UserModel();
        if ($date_start && $date_end) {
            $dateStart = strtotime($date_start);
            $dateEnd = strtotime($date_end);
            $where["create_time"] = ["between", [$date_start, $date_end]]; // 用户条件
        }
        $userNumber = UsersModel::getInstance()->getUsersCount($where);  // 用户总数量
        $orderNumber= OrderModel::getInstance()->orderCount($where); // 订单总数量
        $orderCompletedNumber= OrderModel::getInstance()->orderCount($where+["status"=>2]); // 已完成订单总数量
        $orderPriceNumber= OrderModel::getInstance()->orderTotalPrice($where+["status"=>2]); // 订单总金额
        $driverNmber = DriverModel::getInstance()->driverCount($where);

        $data["user"]  = $userNumber;
        $data["order"] = $orderNumber;
        $data["orderCompleted"] = $orderCompletedNumber;
        $data["orderPrice"] = $orderPriceNumber;
        $data["driver"] = $driverNmber;
        return json(['list' => $data, 'msg' => '']);
    }

}
