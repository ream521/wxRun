<?php
require_once "./config.php";
$appid = $config['appId'];
$appSecret = $config['appSecret'];
$code=$_REQUEST['code'];

$url="https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$appSecret&js_code=$code&grant_type=authorization_code";

$res=getSessionKey($url);

$sessionkey=json_decode($res,true);
$sk['sessionKey']=$sessionkey['session_key'];
$sk['openid']=$sessionkey['openid'];

die(json_encode($sk));
function getSessionKey($url,$second=60){

    $ch = curl_init();
    curl_setopt($ch,CURLOPT_TIMEOUT,$second);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER, 1);

    curl_setopt($ch,CURLOPT_URL,$url);

    curl_setopt($ch,CURLOPT_POST, 1);
    $data = curl_exec($ch);
    return $data;
}
