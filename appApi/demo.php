<?php
header("Content-type:text/html;charset=utf-8");

$act=$_REQUEST['a'];

if (function_exists($act)) {
    $act();
} else {
    echo json_encode(['msg'=>'方法不存在','code'=>'no']);die;
}

//测试
function ceshi(){

    include dirname(dirname(__FILE__))."/wxapp/themes/1.html";
}

