<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------


// 应用公共文件
function makeUniqueCode()
{
    list($usec, $sec) = explode(" ", microtime());
    return str_replace('.', '', $sec.$usec );
}

function makeLoginToken($sub = '' , $prefix = '')
{
    return md5(uniqid($prefix , true) . $sub);
}

//获取当前时间
define('CURR_TIME', time());
define('CURR_DATE', date('Y-m-d'));
define('HOUR', 3600);
//定义删除状态
define('DEL_STATUS', 1);
define('NOT_DEL_STATUS', 0);
/**
 * 统一返回信息
 * @param $code
 * @param $data
 * @param $msge
 */
function api_out($code, $data, $msg, $httpCode = 200)
{
    return json([
        'code' => $code,
        'data' => $data,
        'msg' => $msg,
    ], $httpCode);
}

function success_out($data = '', $msg = '')
{
    $data = $data ?: null;
    return api_out(1, $data, $msg);
}

function error_out($data = "", $msg = '', $code = 0)
{
    $data = $data ?: null;
    return api_out($code, $data, $msg);
}

function birthdayAge($birthday)
{
    if (false == stripos('-', $birthday)) return 0;
    list($year, $month, $day) = explode("-", $birthday);
    $year_diff = date("Y") - $year;
    if ((date("d") - $day) < 0 || (date("m") - $month) < 0)
        $year_diff--;
    return $year_diff;
}

// 理解价格
function handlePrice($price){
    return (string) ceil($price);
}


if (!function_exists('handleImgPath')) {
    /**
     * 图片全路径
     * @param $date
     * @return int
     */
    function handleImgPath($image)
    {
        if (empty($image)) return "";
        if (strpos($image , '/') !== 0){
            $image = '/'.$image;
        }
        return config('img.domain') . $image;
    }
}

if (!function_exists('statisticsContentRangeValid')) {
    /**
     * 统计内容数量
     * @param $min 最小数量
     * @param $max 最大数量
     * @return true false
     */
    function statisticsContentRangeValid($content, $min = 10, $max = 200)
    {
        $count = mb_strlen($content, "utf-8");
        if($count >= $min && $count <= $max){
            return true;
        } else {
            return false;
        }

    }
}



if (!function_exists('currZeroDateToTime')) {
    /**
     * 当前日期零点时间戳
     * @param $date
     * @return int
     */
    function currZeroDateToTime()
    {
        return strtotime(date("Y-m-d"));
    }
}


if (!function_exists('dateFormatTimestamp')) {
    /**
     * 必须经 'validateGregoriandate' 函数验证过的日期, 日期格式化为时间戳
     * @param $date
     * @return int
     */
    function dateFormatTimestamp($date)
    {
        $date = explode("-", $date);
        return mktime(0, 0, 0, $date[1], $date[2], $date[0]);
    }
}

if (!function_exists('handleUserName')) {
    /**
     * 图片全路径
     * @param $date
     * @return int
     */
    function handleUserName($name, $start = 0, $length = 1, $encoding = "utf-8")
    {
        if (!$name) return "";
        $nameStr = mb_substr($name, $start, $length, $encoding);
        return $nameStr;
    }
}

