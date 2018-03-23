<?php
/**
 * wxapp配置文件
 */

return $config=[
    // 微信小程序 AppID
    //'appId' => 'wx8f6a46103460747b',//o2o
    'appId' => 'wx17a78eaf1dbe7419',//hygs

    // 微信小程序 AppSecret
    //'appSecret' => '186c282f55b43ba0f9e3504b12e9cc15',//o2o
    'appSecret' => '36ec201deb181053e8d060d39ef33fd8',//hygs

    // 微信登录态有效期
    'wxLoginExpires' => 7200,
    'wxMessageToken' => 'abcdefgh',

    'smsUrl'=>'http://api.yunpaas.cn/gateway/telbill/charge',
    'smsToken'=>'1474d83a3f7d408996b5dc11305bf306',
];
