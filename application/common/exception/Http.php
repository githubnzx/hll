<?php
namespace app\common\exception;

use Exception;
use think\exception\ErrorException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;

/**
 * Created by PhpStorm.
 * User: wangaojie
 * Date: 2017/10/15
 * Time: 上午11:26
 */
class Http extends Handle
{
    public function render(Exception $e)
    {
        if ($e instanceof HttpException) {
            $statusCode = $e->getStatusCode();
            $content = $e->getMessage();
            return api_out(0, '', $content, $statusCode);
        } else {
            return parent::render($e);
        }
    }
}