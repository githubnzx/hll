<?php
namespace app\user\controller;
use app\user\logic\FileLogic;

class Upload extends Base
{
    public function index()
    {
        $file = $this->request->file('file');
        $upload = FileLogic::getInstance()->uploadOne($file);
        if ($upload['code']) {
            $data = ['imgUrl'=> $upload['url']];
            $code = 1;
            $msg = '';
        } else {
            $data = '';
            $msg = $upload['message'];
            $code = 0;
        }
        ob_clean();
        return api_out($code, $data, $msg);
    }
    public function excel()
    {
        $file = $this->request->file('file');
        $upload = FileLogic::getInstance()->uploadExcel($file);
        if ($upload['code']) {
            $data = ['upload'=> $upload['url']];
            $code = 1;
            $msg = '';
        } else {
            $data = '';
            $msg = $upload['message'];
            $code = 0;
        }
        return api_out($code, $data, $msg);
    }
}
