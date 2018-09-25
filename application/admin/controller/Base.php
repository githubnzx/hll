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


use think\Controller;
use app\admin\model\RoleModel;
use think\exception\HttpException;
use think\Request;

session_start();

class Base extends Controller
{

    protected $msg = "";

    public function _initialize()
    {
        if (request()->isOptions()){
            throw new HttpException('204','');
        }
        if(empty(session('username')) || empty(session('id'))){
            $loginUrl = url('page/login', '', false);
            if(request()->isAjax()){
                $this->msg = "登录超时";
                //return msg(111, $loginUrl, '登录超时');
            } else {
                $this->msg = "请登陆";
            }
            //throw new HttpException(402, '请登陆');
        }

        // 检查缓存
        //$this->cacheCheck();

        // 检测权限
        $control = lcfirst(request()->controller());
        $action = lcfirst(request()->action());

        /*if(empty(authCheck($control . '/' . $action))){
            if(request()->isAjax()){
                return msg(403, '', '您没有权限');
            }

            $this->error('403 您没有权限');
        }*/

        $this->assign([
            //'head'     => session('head'),
            'username' => session('username'),
            'rolename' => session('role')
        ]);

    }

    private function cacheCheck()
    {
        $action = cache(session('role_id'));

        if(is_null($action) || empty($action)){

            // 获取该管理员的角色信息
            $roleModel = new RoleModel();
            $info = $roleModel->getRoleInfo(session('role_id'));
            cache(session('role_id'), $info['action']);
        }
    }

    protected function removRoleCache()
    {
        $roleModel = new RoleModel();
        $roleList = $roleModel->getRole();

        foreach ($roleList as $value) {
            cache($value['id'], null);
        }
    }
}
