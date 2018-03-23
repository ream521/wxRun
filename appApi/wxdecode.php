<?php
include_once "wxBizDataCrypt.php";
require_once "./config.php";
$appid=$config['appId'];

$sessionKey = $_REQUEST['sessionKey'];
$encryptedData=$_REQUEST['encryptedData'];
$iv =$_REQUEST['iv'];
$pc = new WXBizDataCrypt($appid, $sessionKey);
$errCode = $pc->decryptData($encryptedData, $iv, $data);

if ($errCode == 0) {
    die($data);
}else {
    die($errCode);
}