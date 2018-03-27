<?php

require_once '../framework/bootstrap.inc.php';
require_once './config.php';

$uniacid = !empty($_REQUEST['i']) ? $_REQUEST['i'] : 6 ;
$act=$_REQUEST['a'];

if (function_exists($act)) {
    $act($uniacid);
} else {
    echo json_encode(['msg'=>'方法不存在','code'=>'no'],256);die;
}

//获取获取随机字
function getRandChar($uniacid){
    //$unionid=$_REQUEST['unionid'];
    $openid=$_REQUEST['openid'];
    $rid=$_REQUEST['rid'];
    $step=$_REQUEST['step'];

    $time=time();
    $sql="SELECT * FROM ".tablename('run_activity')." WHERE uniacid=".$uniacid.
        " AND status=1 AND startTime <= $time AND endTime>=$time AND id=".$rid;
    $stepsetting=pdo_fetch($sql);
    $stepsetting['keyword']=explode(',',$stepsetting['num']);
    if(empty($stepsetting)){
        echo json_encode(['msg'=>'活动未开始,不给字','code'=>'no']);die;
    }

    if($step < $stepsetting['originStep']){
        $total=0;//不给字
    }else if($step >= $stepsetting['maxStep']){
        $total=floor(($stepsetting['maxStep'] - $stepsetting['originStep']) / $stepsetting['stepSize']);//给设置的最多的字数
    }else{
        $total=floor(($step - $stepsetting['originStep']) / $stepsetting['stepSize']);//正常给
    }

    $sql="SELECT id FROM ".tablename('run_wxrun')." WHERE uniacid=".$uniacid.
        " AND openid='$openid' ";
    $wrid=pdo_fetchcolumn($sql);

    $start=strtotime(date('Y-m-d',time()));//0：00：00
    $end=strtotime(date('Y-m-d',time() + 3600*24));

    $sql="SELECT count(*) as count FROM ".tablename('run_gift')." WHERE rid=$rid AND wrid=$wrid AND type=1
        AND createTime >=$start AND createTime <$end";
    $count=pdo_fetchcolumn($sql);//当天系统已给字数

    $cannum=$total-$count;//目前可以得到的字数
    if($total==0 || $cannum==0){
        echo json_encode(['msg'=>'未达要求,不给字','code'=>'no']);die;
    }

    $sql="SELECT count(*) FROM ".tablename('run_gift')." WHERE rid=$rid ";
    $currenttotal=pdo_fetchcolumn($sql);//当前所有已经发出的字数

    $sql="SELECT count(*) FROM ".tablename('run_gift')." WHERE rid=$rid AND keyword='{$stepsetting['keyword'][0]}'";
    $facttotal=pdo_fetchcolumn($sql);//当前所有已经发出的特殊字数

    $user=getUserByOpenid($uniacid,$openid);
    if($user['wxuid']!=0){
        $sql="SELECT * FROM ".tablename('mc_members')." WHERE uid={$user['wxuid']}";
        $wxuser=pdo_fetch($sql);
    }
    $sql="SELECT * FROM ".tablename('run_activity')." WHERE find_in_set('".$wrid."',user_ids) ";
    $ret=pdo_fetch($sql);//是否之前抽到过

    $res=getRandFuZi($wxuser,$stepsetting,$total,$count,$currenttotal,$facttotal,$ret);
    $keywords=[
        $stepsetting['keyword1'],
        $stepsetting['keyword2'],
        $stepsetting['keyword3'],
        $stepsetting['keyword4'],
        $stepsetting['keyword5'],
    ];
    $data=[];
    foreach($res as $k=>$v){
        $index=array_search($v,$keywords)+1;//获取字的索引
        $insert=[
            'rid'=>$rid,
            'wrid'=>$wrid,
            'type'=>1,
            'keyword'=>$v,
            'title'=>"获得一个 “ ".$v." ”字",
            'createTime'=>time(),
        ];
        pdo_insert('run_gift',$insert);
        //更新user_ids
        if($stepsetting['keyword'][0]==$v){
            $sql="SELECT user_ids FROM ".tablename('run_activity')." WHERE id=$rid";
            $user_ids=pdo_fetchcolumn($sql);
            if(empty($user_ids)){
                $sql="UPDATE ".tablename('run_activity')." SET user_ids=$wrid WHERE id=$rid";
            }else{
                $user_ids.=",".$wrid;
                $sql="UPDATE ".tablename('run_activity')." SET user_ids=$user_ids WHERE id=$rid";
            }
            pdo_query($sql);
        }

        $data[]['img']=$stepsetting['big_img'.$index];
    }
    echo json_encode(['code'=>'ok','msg'=>'success','data'=>$data]);die;
}
//是否认证
function isrenzheng($uniacid){
    $openid=$_REQUEST['openid'];
    $user=getUserByOpenid($uniacid,$openid);
    if($user['wxuid']!=0){
        $sql="SELECT * FROM ".tablename('mc_members')." WHERE uid={$user['wxuid']}";
        $wxuser=pdo_fetch($sql);
        if(empty($wxuser['uvyid'])){
            die(json_encode(['code'=>'no','msg'=>'未认证']));
        }else{
            die(json_encode(['code'=>'ok','msg'=>'已认证']));
        }
    }
    die(json_encode(['code'=>'no','msg'=>'未认证']));
}
//获取活动背景
function getBgimg($uniacid){
    $rid=$_REQUEST['rid'];
    $time=time();
    $sql="SELECT img FROM ".tablename('run_activity')." WHERE uniacid=".$uniacid.
        " AND status=1  AND id=".$rid;
    $res=pdo_fetch($sql);
    die(json_encode($res));die;
}
//获取ads
function getAds($uniacid){
    $time=time();
    $sql="SELECT img,link,rid FROM ".tablename('run_ads')." WHERE uniacid=".$uniacid.
        " AND status=1 AND startTime <= $time AND title='运动' AND endTime>=$time ORDER BY weight DESC";
    $ads=pdo_fetchall($sql);
    die(json_encode($ads));die;
}
//获取获奖名单
function getGiftRes(){
    $rid=$_REQUEST['rid'];
    $sql="SELECT rgs.*,rw.openid,rw.unionid,rw.avatar,rw.nickname FROM ".tablename('run_gift_res')." as rgs left join ".tablename('run_wxrun')." as rw on rgs.wrid=rw.id  WHERE rgs.rid=$rid  ORDER BY rgs.createTime DESC ";

    $data = pdo_fetchall($sql);
    foreach($data as $k=>$v){
        $data[$k]['nickname']=base64_decode($v['nickname']);
    }
    echo json_encode($data);die;
}
//兑换奖品
function duihuanGift(){
    $rid=$_REQUEST['rid'];
    $uid=$_REQUEST['uid'];
    $phone=$_REQUEST['phone'];
    $shopcate=$_REQUEST['shopcate'];
    $giftType=$_REQUEST['gifttype'];

    $sql="SELECT * FROM ".tablename('run_gift')." WHERE wrid=$uid AND rid=$rid AND type !=0 GROUP BY keyword";
    $res=pdo_fetchall($sql);

    foreach($res as $k=>$v){
        $ids[]=$v['id'];
    }
    $ids=implode(',',$ids);
    try {
        pdo_begin();

        pdo_query("UPDATE ".tablename('run_gift')." SET type=0 WHERE id in ($ids)");
        $price=1.00;
        $data=[
            'rid'=>$rid,
            'wrid'=>$uid,
            'type'=>$giftType,
            'gift'=>$phone,
            'shopCate'=>$shopcate,
            'price'=>$price,
            'createTime'=>time(),
        ];
        if($giftType=='2'){
            $data['title']='虚拟奖品';
        }else{
            $data['title']='实物奖品';
        }

        pdo_insert("run_gift_res",$data);

        pdo_commit();
        $res=charge($phone,$shopcate,$price);
        $res=json_decode($res,true);
        if($res['statusCode']==1000){
            $code=['code'=>'ok','msg'=>'兑换成功'];
        }else{
            $code=['code'=>'no','msg'=>$res['statusMsg']];
        }
        $code=['code'=>'ok'];
    } catch (Exception $e) {
        pdo()->rollBack();
        $code=['code'=>'no','msg'=>'兑换失败'];
    }

    echo json_encode($code);die;
}
//获取互赠记录
function getGiveLog($uniacid){
    $rid=$_REQUEST['rid'];
    $openid=$_REQUEST['openid'];

    $sql="SELECT id FROM ".tablename('run_wxrun')." WHERE openid='$openid' AND uniacid=$uniacid ";

    $wrid=pdo_fetchcolumn($sql);
    $sql="SELECT keyword,fromuid,touid,type,title FROM ".tablename('run_gift_log')." WHERE ((type=1 AND touid=$wrid AND fromuid!=0) OR (type=2 AND fromuid=$wrid AND touid!=0)) AND rid=$rid ORDER BY createTime DESC";
    $logs=pdo_fetchall($sql);

    foreach($logs as $k=>$v){
        if($v['type']==1){
            $sql="SELECT * FROM ".tablename('run_wxrun')." WHERE id={$v['fromuid']}";
        }else{
            $sql="SELECT * FROM ".tablename('run_wxrun')." WHERE id={$v['touid']}";
        }
        $user=pdo_fetch($sql);
        $logs[$k]['avatar']=$user['avatar'];
        $logs[$k]['nickname']=base64_decode($user['nickname']);
    }
    echo json_encode($logs);die;
}
//获取活动详情
function getActDetail(){
    $rid=$_REQUEST['rid'];
    $sql="SELECT * FROM ".tablename('run_activity')." WHERE id=$rid";
    $res=pdo_fetch($sql);
    $res['startTime']=date('Y-m-d H:i:s',$res['startTime']);
    $res['endTime']=date('Y-m-d H:i:s',$res['endTime']);
    echo json_encode($res);die;
}
//获取已有多少人获集齐
function getAllNum(){
    $rid=$_REQUEST['rid'];

    $sql="SELECT count(*) FROM ".tablename('run_gift_res')." WHERE rid=$rid ";
    $facttotal=pdo_fetchcolumn($sql);
    $sql="SELECT * FROM ".tablename('run_activity')." WHERE id=$rid";
    $res=pdo_fetch($sql);
    $keywords=$res['keyword1'].$res['keyword2'].$res['keyword3'].$res['keyword4'].$res['keyword5'];

    echo json_encode(['keywords'=>$keywords,'total'=>$facttotal]);die;
}
//获取当前用户是否集齐5字
function getIsCollect(){
    $openid=$_REQUEST['openid'];
    $rid=$_REQUEST['rid'];

    $sql="SELECT rg.keyword,count(*) as num,rw.id FROM ".tablename('run_gift')." as rg LEFT JOIN ".tablename('run_wxrun').
        " as rw on rg.wrid=rw.id WHERE rw.openid='$openid' AND rg.rid=$rid AND rg.type!=0 GROUP BY keyword";
    $res=pdo_fetchall($sql);
    $count=count($res);
    if($count==5){
        //集齐的话查看活动奖励类型
        $sql="SELECT giftType,gift_bgimg,endTime FROM ".tablename('run_activity')." WHERE id=$rid";
        $gift=pdo_fetch($sql);
        if($gift['endTime'] <= time()){
            $data['code']='ok';
            $data['gifttype']=$gift['giftType'];
            $data['gift_bgimg']=$gift['gift_bgimg'];
            $data['msg']='已集齐';
        }else{
            $data['code']='no';
            $data['msg']='未到开奖时间';
        }
    }else{
        $data['code']='no';
        $data['msg']='未集齐';
    }
    echo json_encode($data);die;
}
//更新gift_log表 , gift表
function linkGiftLog(){
    $uid=$_REQUEST['uid'];
    $fromid=$_REQUEST['fromid'];
    $logtype=$_REQUEST['logtype'];
    $rid=$_REQUEST['rid'];
    $keyword=$_REQUEST['keyword'];
    if($fromid == $uid){
        echo json_encode(['code'=>'no']);die;
    }
    $time=time();
    $sql="SELECT * FROM ".tablename('run_activity')." WHERE status=1 AND startTime<=$time AND endTime>$time AND id=$rid";
    $activity=pdo_fetch($sql);
    if(empty($activity)){
        echo json_encode(['code'=>'no','msg'=>'活动已结束']);die;
    }

    if($logtype==1){
        $sql="SELECT id FROM ".tablename('run_gift_log')." WHERE type=$logtype AND fromuid=$fromid AND touid=0 AND rid=$rid AND keyword='$keyword'";

        $logid=pdo_fetchcolumn($sql);//只要一条

        $sql2="SELECT id FROM ".tablename('run_gift')." WHERE wrid=$fromid AND keyword='$keyword' AND rid=$rid AND type=2";
        $id=pdo_fetchcolumn($sql2);//只要一条
        pdo_query("UPDATE ".tablename('run_gift')." SET wrid=$uid,type=3 WHERE id=$id");

        $sql1="UPDATE ".tablename('run_gift_log')." SET touid=$uid WHERE id=$logid";
    }else if($logtype==2){
        $sql="SELECT id FROM ".tablename('run_gift_log')." WHERE type=$logtype AND touid=$fromid AND fromuid=0 AND rid=$rid AND keyword='$keyword'";

        $logid=pdo_fetchcolumn($sql);//只要一条

        $sql1="UPDATE ".tablename('run_gift_log')." SET fromuid=$uid WHERE id=$logid";
    }
    $res=pdo_query($sql1);
    if($res){
        echo json_encode(['code'=>'ok','logtype'=>$logtype]);die;
    }else{
        echo json_encode(['code'=>'no']);die;
    }
}
//获取用户的uid
function getUid($uniacid){
    $openid=$_REQUEST['openid'];

    $user=getUserByOpenid($uniacid,$openid);
    echo json_encode($user);die;
}
//获取当前用户获取的字数
function getUserFuzi(){
    //$unionid=$_REQUEST['unionid'];
    $openid=$_REQUEST['openid'];
    $rid=$_REQUEST['rid'];

    $sql="SELECT * FROM ".tablename('run_activity')." WHERE id=$rid";
    $activity=pdo_fetch($sql);

    $sql="SELECT rg.keyword,count(*) as num FROM ".tablename('run_gift')." as rg LEFT JOIN ".tablename('run_wxrun').
        " as rw on rg.wrid=rw.id WHERE rw.openid='$openid' AND rg.type !=0 AND rg.rid=$rid GROUP BY keyword";
    $res=pdo_fetchall($sql);
    //echo json_encode($res);die;
    $keywords=[
        $activity['keyword1'],
        $activity['keyword2'],
        $activity['keyword3'],
        $activity['keyword4'],
        $activity['keyword5'],
    ];
    $data=[];

    foreach($keywords as $k=>$v){
        $index=$k+1;
        $data[$k]['id']=$k;
        $data[$k]['small_pic']=$activity['small_pic'.$index];
        $data[$k]['small_img']=$activity['small_img'.$index];
        $data[$k]['big_pic']=$activity['big_pic'.$index];
        $data[$k]['big_img']=$activity['big_img'.$index];
        $data[$k]['keyword']=$v;
        $data[$k]['num']=0;
        foreach($res as $kk=>$vv){
            if($v == $vv['keyword']){
                $data[$k]['num']=$vv['num'];
            }
        }
    }
    echo json_encode($data);die;
}
//赠送给好友
function giveFrind($uniacid){
    //$unionid=$_REQUEST['unionid'];
    $openid=$_REQUEST['openid'];
    $rid=$_REQUEST['rid'];
    $keyword=$_REQUEST['keyword'];

    $user=getUserByOpenid($uniacid,$openid);
    if(empty($user)){
        echo json_encode(['code'=>'no','msg'=>'非法请求']);die;
    }
    $sql="SELECT * FROM ".tablename('run_gift')." WHERE rid=$rid AND wrid='{$user['id']}' AND keyword='$keyword'";
    $gift=pdo_fetch($sql);//只要一条
    if(empty($gift)){
        echo json_encode(['code'=>'no','msg'=>'您的字不足']);die;
    }
    pdo_query("UPDATE ".tablename('run_gift')." SET type=2 WHERE id={$gift['id']}");
    $data=[
        'rid'=>$rid,
        'fromuid'=>$user['id'],//todo 如何获取索取人的id? //谁点算谁，才生成
        'touid'=>0,
        'type'=>1,
        'title'=>"赠送给你一枚“".$keyword."”字徽章",
        'keyword'=>$keyword,
        'createTime'=>time(),
    ];
    pdo_insert('run_gift_log',$data);
    $id=pdo_insertid();
    echo json_encode(['code'=>'ok','data'=>$id]);die;
}
//向好友索取字
function getFrind($uniacid){
    $openid=$_REQUEST['openid'];
    $rid=$_REQUEST['rid'];
    $keyword=$_REQUEST['keyword'];

    $user=getUserByOpenid($uniacid,$openid);
    if(empty($user)){
        echo json_encode(['code'=>'no','msg'=>'非法请求']);die;
    }
    $data=[
        'rid'=>$rid,
        'fromuid'=>0,
        'touid'=>$user['id'],//todo 如何获取索取人的id? //谁点算谁，才生成
        'type'=>2,
        'title'=>"向你索取一枚“".$keyword."”字徽章",
        'keyword'=>$keyword,
        'createTime'=>time(),
    ];
    pdo_insert('run_gift_log',$data);
    $id=pdo_insertid();
    echo json_encode(['code'=>'ok','data'=>$id]);die;
}
//转发失败删除log
function delLogByid(){
    $logid=$_REQUEST['logid'];

    pdo_delete('run_gift_log',['id'=>$logid]);
    echo json_encode(['code'=>'ok']);die;

}
//用户信息入库
function insertUser($uniacid){
    $data['openid']=$_REQUEST['openid'];

    $user=getUserByOpenid($uniacid,$data['openid']);
    if(empty($user)){
        $data['nickname']=base64_encode($_REQUEST['nickname']);
        $data['avatar']=$_REQUEST['avatar'];
        $data['uniacid']=$uniacid;
        $data['addTime']=time();
        $res=pdo_insert('run_wxrun',$data);
        if($res){
            echo json_encode(['code'=>'ok','msg'=>'用户入库成功']);die;
        }else{
            echo json_encode(['code'=>'no','msg'=>'用户入库失败']);die;
        }
    }else{
        echo json_encode(['code'=>'ok','msg'=>'用户已存在']);die;
    }
}
//获取该公众号下的用户信息
function getUserByOpenid($uniacid,$openid){
    $sql="SELECT * FROM ".tablename('run_wxrun')." WHERE uniacid=$uniacid AND openid='$openid'";
    $user=pdo_fetch($sql);
    return $user;
}
//获取自定义颜色
function getMyColor($uniacid){
    $rid=$_REQUEST['rid'];
    $time=time();
    $sql="SELECT * FROM ".tablename('run_activity')." WHERE uniacid=".$uniacid.
        " AND status=1 AND id=".$rid;//AND startTime <= $time AND endTime>=$time 
    $res=pdo_fetch($sql);
    die(json_encode($res));
}
//获取字的算法
function getRandFuZi($user,$stepsetting,$total,$count,$currenttotal,$facttotal,$ret){
    $stepsetting['keyword']=explode(',',$stepsetting['num']);
    $keywords=[
        $stepsetting['keyword1'],
        $stepsetting['keyword2'],
        $stepsetting['keyword3'],
        $stepsetting['keyword4'],
        $stepsetting['keyword5'],
    ];
    $index=array_search($stepsetting['keyword'][0],$keywords);//获取特殊字的索引
    array_splice($keywords, $index, 1);//删除特殊字并重新建立索引

    $cankeywords=[];

    for($i=$count;$i<$total;$i++){
        if($i == 0 || empty($user['uvyid']) || !empty($ret)){

            $cankeywords[]=$keywords[rand(0,3)];
        }else{
            $j=rand(1,5);
            if($j != 1){
                $cankeywords[]=$keywords[rand(0,3)];
            }else{
                $plan=$stepsetting['keyword'][1] / $currenttotal;//计划比例
                $fact=$facttotal / $currenttotal;//实际比例
                if($fact < $plan){
                    $cankeywords[]=$stepsetting['keyword'][0];
                }else{
                    $cankeywords[]=$keywords[rand(0,3)];
                }
            }
        }
    }

    return $cankeywords;
}

function iframe(){
    $sopenid=$_REQUEST['sopenid'];

    include IA_ROOT."/wxapp/themes/iframe.html";
}

//获取公众号openid
function getOpenidByAuth(){
    $i=empty($_REQUEST['i']) ? 6 : $_REQUEST['i'];
    $code = $_REQUEST['code'];

    $sql="SELECT * FROM ".tablename('account_wechats')." WHERE acid=$i";
    $weixin=pdo_fetch($sql);
    $appid=$weixin['key'];
    $appsecret=$weixin['secret'];

    $smallopenid=$_REQUEST['sopenid'];
//    $appid='wx0cf7a61bb900579b';
//    $appsecret='b4364830d82c63710b677767a1a82a93';

    if(empty($code)){
        $uri=urlencode("https://hygs.web.mai022.com/wxapp/index.php?a=getOpenidByAuth&i=$i&sopenid=$smallopenid");
        $url="https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$uri&response_type=code&scope=snsapi_base&state=123#wechat_redirect";

        header("location:$url");
    }else{

        $url="https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$appsecret&code=$code&grant_type=authorization_code";

        $res=file_get_contents($url);

        $data=json_decode($res,1);
        $openid=$data['openid'];

        $sql="SELECT uid,fanid FROM ".tablename('mc_mapping_fans')." WHERE openid='{$openid}' AND acid='$i'";
        $fans=pdo_fetch($sql);
        $uid=$fans['uid'];

        if(empty($fans)){
            $uid=insertMembers($i,$openid);
            $fansid=insertFans($i,$openid);
        }else if($fans['uid']==0){
            $uid=insertMembers($i,$openid);
            $update['uid']=$uid;
            $where['fanid']=$fans['fanid'];
            pdo_update('mc_mapping_fans',$update,$where);
        }

        $sql="UPDATE ".tablename('run_wxrun')." SET wxuid='$uid' WHERE openid='$smallopenid'";

        $res=pdo_query($sql);
        //var_dump($res);die;
        include IA_ROOT."/wxapp/themes/wechat.html";
    }
}
//members 表
function insertMembers($i,$openid){
    $salt=random(8);//$config['setting']['authkey'] = 'a8434729';
    $members=[
        'uniacid'=>$i,
        'mobile'=>'',
        'email'=>md5($openid).'@www.zhy88.com',
        'password'=>md5('123456' .$salt. 'a8434729'),//默认密码123456
        'salt'=>$salt,
        'groupid'=>5,
        'credit1'=>0,
        'credit2'=>0,
        'credit3'=>0,
        'credit4'=>0,
        'credit5'=>0,
        'credit6'=>0,
        'createtime'=>time(),
        'realname'=>'',
        'nickname'=>'',
    ];
    pdo_insert('mc_members',$members);
    return pdo_insertid();
}
//fans表
function insertFans($i,$openid,$uid){
    $mfans=[
        'acid'=>$i,
        'uniacid'=>$i,
        'uid'=>$uid,
        'openid'=>$openid,
        'salt' => random(8),
        'follow'=>0,
        'followtime'=>0,
        'unfollowtime'=>0,
        'tag'=>'',
        'nickname'=>'',
        'groupid'=>0,
        'updatetime'=>0,
        'unionid'=>'',
    ];
    pdo_insert('mc_mapping_fans',$mfans);
    return pdo_insertid();
}
//curl get 请求
function httpGet($url) {
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);//如果成功只将结果返回，不自动输出任何内容。
    curl_setopt($curl, CURLOPT_TIMEOUT, 500); //作为最大延续500毫秒，超过这个时间将不去读取页面

    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//不检测服务器的证书是否由正规浏览器认证过的授权CA颁发

    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);//不检测服务器的域名与证书上的是否一致

    curl_setopt($curl, CURLOPT_URL, $url);//设置提交地址路径

    $res = curl_exec($curl);//执行，并接收返回结果

    curl_close($curl);//关闭.

    return $res;
}
//充值
function charge($mobile,$type=1,$price='0.00'){
    $url="http://api.yunpaas.cn/gateway/telbill/charge?";

    $param['token']='1474d83a3f7d408996b5dc11305bf306';
    $param['mobile']=$mobile;
    $param['customId']="xuni".time().substr($mobile,-4);
    if($type=1){
        $code='400';
    }elseif($type=2){
        $code='500';
    }elseif($type=3){
        $code='600';
    }
    $param['code']=$code.sprintf('%03s',intval($price));
    $param['callbackUrl']="https://".$_SERVER['SERVER_NAME']."/wxapp/index.php?a=chargeCallback";
    $sign='';
    foreach ($param as $v){
        $sign.=$v;
    }
    $param['sign']=strtolower(md5($sign));

    $url .=http_build_query($param);
    $res=httpGet($url);

    return $res;
}
//充值回调地址
function chargeCallback(){
    echo 'ok';
}
//同步用户步数
function syncStep($uniacid){
    $openid=$_REQUEST['openid'];
    $steps = json_decode($_REQUEST['step'],true);
    $user = getUserByOpenid($uniacid,$openid);
    if(empty($user)){
        die(json_encode(['code'=>'no','msg'=>'用户数据出错']));
    }
    if(date("Y-m-d",$user['lastTime']) != date("Y-m-d",time())){
        pdo_update('run_wxrun',['today_step'=>0,'lastTime'=>time()]);//所有人的当天步数置0
    }
    $time = time();
    $rank = pdo_fetch("SELECT id,startTime,endTime FROM ".tablename('run_rank')." WHERE uniacid = $uniacid AND status=1 AND startTime <= $time AND endTime >= $time");
    if(empty($rank)){
        die(json_encode(['code'=>'no','msg'=>'活动未开启']));
    }
    $data['today_step'] = $steps[30]['step'];
    $res = pdo_update('run_wxrun',$data,['id'=>$user['id']]);
    unset($steps[30]);//去掉当天的步数
    foreach ($steps as $v){
        if($v['timestamp'] < strtotime(date("Y-m-d",$rank['startTime'])) || $v['timestamp'] > strtotime(date("Y-m-d",$rank['endTime']))){
            continue;
        }
        $res = pdo_fetch("SELECT timestamp FROM ".tablename('run_step')." WHERE uid = {$user['id']} AND timestamp = {$v['timestamp']}");
        if($res){
            continue;
        }
        if(strtotime(date("Y-m-d",$user['tm_join'])) <= $v['timestamp']){
            $tid = $user['tm_id'];
        }else{
            $tid = 0;
        }
        $step = [
            'uid'=>$user['id'],
            'tid'=>$tid,
            'rid'=>$rank['id'],
            'timestamp'=>$v['timestamp'],
            'step'=>$v['step'],
            'createTime'=>time()
        ];
        pdo_insert('run_step',$step);
    }
    die(json_encode(['code'=>'ok','msg'=>'同步成功']));
}
//排行榜
function getRankList($uniacid){
    $openid=$_REQUEST['openid'];
    $cid=$_REQUEST['cid'];
    $user = getUserByOpenid($uniacid,$openid);
    // 1 个人今日排行，2 团队今日排行 3 个人本期排行 4 团队本期排行
    if($cid == '1'){
        $sql = "SELECT openid,nickname,avatar,today_step FROM ".tablename('run_wxrun')." WHERE uniacid=$uniacid ORDER BY today_step DESC ";
        $ranklist = pdo_fetchall($sql);
        foreach($ranklist as $k=>$v){
            $v['nickname'] = base64_decode($v['nickname']);
            if($v['openid'] == $openid){
                $rank = $v;
                $rank['rank'] = '第 '.($k+1)." 名";
            }
            //显示前100名
            if($k < 100){
                $data[] = $v;
            }
        }
    }else if($cid == '2'){

        $team = getTeam($user['tm_id']);

        $sql = "SELECT rt.id,rt.team_name as nickname,rt.team_img as avatar,SUM(rw.today_step) as today_step FROM ".tablename('run_wxrun')." rw JOIN ".tablename('run_team')." rt ON rw.tm_id = rt.id WHERE rw.uniacid=$uniacid AND rw.tm_id !=0 GROUP BY rw.tm_id ORDER BY today_step DESC ";
        $ranklist = pdo_fetchall($sql);
        foreach($ranklist as $k=>$v){
            if($v['id'] == $user['tm_id']){
                $rank = $v;
                $rank['rank'] = '第 '.($k+1)." 名";
            }
            //显示前100名
            if($k < 100){
                $data[] = $v;
            }
        }
    }else if($cid == '3'){
        $sql = "SELECT rw.openid,rw.nickname,rw.avatar,SUM(rs.step)+rw.today_step as today_step FROM ".tablename('run_wxrun')." rw right join ".tablename('run_step')." rs 
on rw.id = rs.uid WHERE rw.uniacid=6 group by rs.uid ORDER BY today_step DESC ";
        $ranklist = pdo_fetchall($sql);
        foreach($ranklist as $k=>$v){
            $v['nickname'] = base64_decode($v['nickname']);
            if($v['openid'] == $openid){
                $rank = $v;
                $rank['rank'] = '第 '.($k+1)." 名";
            }
            //显示前100名
            if($k < 100){
                $data[] = $v;
            }
        }

    }else if($cid == '4'){
        $team = getTeam($user['tm_id']);

        $sql = "SELECT rw.tm_id,rt.id,rt.team_name as nickname,rt.team_img as avatar,SUM(rw.today_step) as today_step FROM ".tablename('run_wxrun')." rw JOIN ".tablename('run_team')." rt ON rw.tm_id = rt.id WHERE rw.uniacid=$uniacid AND rw.tm_id !=0 GROUP BY rw.tm_id ORDER BY today_step DESC ";
        $ranklist = pdo_fetchall($sql);
        foreach($ranklist as $k=>$v){
            $ranklist[$k]['today_step'] =$v['today_step'] + pdo_fetchcolumn("SELECT SUM(step) FROM ".tablename('run_step')." WHERE tid=  {$v['tm_id']} AND tid != 0");
        }
        $ranklist = my_sort($ranklist,'today_step');
        foreach ($ranklist as $k=>$v){
            if($v['id'] == $user['tm_id']){
                $rank = $v;
                $rank['rank'] = '第 '.($k+1)." 名";
            }
            //显示前100名
            if($k < 100){
                $data[] = $v;
            }
        }
    }
    if($cid == '1' || $cid == '3'){
        //个人
        $timepre7 = strtotime(date('Y-m-d')) - 7*24*3600;
        $sql = "SELECT step,timestamp FROM ".tablename('run_step')." WHERE timestamp > $timepre7 AND uid = {$user['id']} order by timestamp ASC";
        $charts = pdo_fetchall($sql);
        foreach($charts as $v){
            $chart['step'][] = $v['step'];
            $chart['date'][] = date('md',$v['timestamp']);
        }
        $chart['step'][] = $user['today_step'];
        $chart['date'][] = '今';//date('d');
    }else if($cid == '2' || $cid == '4'){
        //团队
        $sql = "SELECT startTime,endTime FROM ".tablename('run_rank')." WHERE status = 1 AND uniacid = $uniacid";
        $act = pdo_fetch($sql);//活动时间
        getTeam($user['tm_id']);
        $sql = "SELECT timestamp,SUM(step) as step FROM ".tablename('run_step')." WHERE timestamp >= {$act['startTime']} AND timestamp <= {$act['endTime']} AND tid = {$user['tm_id']} group by timestamp order by timestamp ASC";
        $charts = pdo_fetchall($sql);
        foreach($charts as $v){
            $chart['step'][] = $v['step'];
            $chart['date'][] = date('md',$v['timestamp']);
        }
        $chart['step'][] = pdo_fetchcolumn("SELECT SUM(today_step) FROM ".tablename('run_wxrun')." WHERE tm_id = {$user['tm_id']}");
        $chart['date'][] = '今';//date('d');
    }

    die(json_encode(['code'=>'ok','msg'=>'获取成功','data'=>['ranklist'=>$data,'rank'=>$rank,'charts'=>$chart]]));
}
//判断活动是否开启
function isrank($uniacid){
    $openid=$_REQUEST['openid'];
    $user = getUserByOpenid($uniacid,$openid);

    $sql = "SELECT startTime,endTime FROM ".tablename('run_rank')." WHERE status = 1 AND uniacid = $uniacid";
    $rank = pdo_fetch($sql);
    if(empty($rank) || $rank['startTime'] >time() || $rank['endTime'] < time()){
        die(json_encode(['code'=>'no','msg'=>'活动未开始']));
    }
    $data = 'ok';
    if($user['tm_id'] == 0){
        $data = 'no';
    }
    $sql = "SELECT * FROM ".tablename('run_team')." WHERE id={$user['tm_id']}";
    $team = pdo_fetch($sql);
    if(empty($team)){
        $data = 'no';
    }
    die(json_encode(['code'=>'ok','msg'=>'活动进行中','data'=>$data]));
}
//验证团队是否唯一
function checkteamname($uniacid){
    $name = trim($_REQUEST['teamname']);
    $team=pdo_fetch("SELECT team_name FROM ".tablename('run_team')." WHERE binary team_name='$name' AND uniacid=$uniacid ");
    if(empty($team)){
        die(json_encode(['code'=>'ok','msg'=>'可以使用']));
    }
    die(json_encode(['code'=>'no','msg'=>'团队名称已存在']));
}
//获取团队信息
function getTeam($id){
    $rank=[
        'nickname'=>'您所在团队名称',
        'avatar'=>'http://hygs1.web.mai022.com/attachment/images/6/2018/03/VtqTWRqEtrHitfZxTfGWXtZpgTThlf.jpg',
        'today_step'=>'0',
        'rank'=>'无名次'
    ];
    if($id == '0'){
        die(json_encode(['code'=>'no','msg'=>'您尚未加入团队','data'=>['ranklist'=>[],'rank'=>$rank]]));
    }
    $sql = "SELECT * FROM ".tablename('run_team')." WHERE id=$id";
    $team = pdo_fetch($sql);
    if(empty($team)){
        pdo_update('run_wxrun',['tm_id'=>0,'tm_join'=>0]);//解散的团队置0
        die(json_encode(['code'=>'no','msg'=>'您所在的团队已解散','data'=>['ranklist'=>[],'rank'=>$rank]]));
    }
    return $team;
}
//创建团队
function createteam($uniacid){
    $openid = $_REQUEST['openid'];
    $teamname = $_REQUEST['team_name'];
    $team = pdo_fetch("SELECT team_name FROM ".tablename('run_team')." WHERE binary team_name='$teamname' AND uniacid=$uniacid ");
    if(!empty($team)){
        die(json_encode(['code'=>'no','msg'=>'团队名称已存在']));
    }
    $path = "./img/team/".date("Ymd");
    mkdirs($path);
    if(!empty($_FILES['file'])){
        $ext = end(explode('.', $_FILES['file']['name']));
        $filename = $path."/".$openid.'.'.$ext;
        move_uploaded_file($_FILES['file']['tmp_name'],$filename);
        $teamimg ="http://".$_SERVER['SERVER_NAME']."/wxapp/".$filename;
    }
    $user = getUserByOpenid($uniacid,$openid);
    $team = [
        'uniacid'=>$uniacid,
        'team_name'=>$teamname,
        'team_img'=>$teamimg,
        'status'=>1,
        'addTime'=>time(),
        'uid'=>$user['id']
    ];
    $res = pdo_insert('run_team',$team);
    $lastid = pdo_insertid();
    if($res){
        pdo_update('run_wxrun',['tm_id'=>$lastid,'tm_join'=>time()],['id'=>$user['id']]);
        die(json_encode(['code'=>'ok','msg'=>'创建成功','data'=>$lastid]));
    }else{
        die(json_encode(['code'=>'no','msg'=>'创建失败']));
    }
}
//加入团队
function joinTeam($uniacid){
    $openid = $_REQUEST['openid'];
    $tmid = $_REQUEST['tmid'];
    $team = getTeam($tmid);
    $user = getUserByOpenid($uniacid,$openid);
    if($user['tm_id'] == $team['id']){
        die(json_encode(['code'=>'no','msg'=>'您已在该团队中']));
    }
    $sql = "SELECT count(*) FROM ".tablename('run_wxrun')." WHERE tm_id = {$team['id']}";
    $count = pdo_fetchcolumn($sql);
    if($count > 8){
        die(json_encode(['code'=>'no','msg'=>'该团队人员已满']));
    }
    $res = pdo_update('run_wxrun',['tm_id'=>$tmid,'tm_join'=>time()],['openid'=>$openid]);
    if($res){
        die(json_encode(['code'=>'ok','msg'=>'已加入团队','data'=>$team]));
    }else{
        die(json_encode(['code'=>'no','msg'=>'加入团队失败']));
    }
}
//获取团队详情
function teamDetail($uniacid){
    $tmid = $_REQUEST['tmid'];
    $team = getTeam($tmid);
    $sql = "SELECT openid,nickname,avatar,today_step FROM ".tablename('run_wxrun')." WHERE tm_id = $tmid order by today_step DESC";
    $rankList = pdo_fetchall($sql);
    foreach($rankList as $k=> $v){
        $rankList[$k]['nickname'] = base64_decode($v['nickname']);
    }
    $rank = [
        'nickname'=>$team['team_name'],
        'avatar'=>$team['team_img'],
        'today_step'=>'队长：'.base64_decode(pdo_fetchcolumn("SELECT nickname FROM ".tablename('run_wxrun')." WHERE id = {$team['uid']}")),
        'rank'=>'欢迎加入我们团队',
    ];
    die(json_encode(['code'=>'ok','msg'=>'成功','data'=>['ranklist'=>$rankList,'rank'=>$rank]]));
}
//递归创建目录
function mkdirs($path) {
    if (!is_dir($path)) {
        mkdirs(dirname($path));
        mkdir($path);
    }
    return is_dir($path);
}
//二维数组排序
function my_sort($arrays,$sort_key,$sort_order=SORT_DESC,$sort_type=SORT_NUMERIC){
    if(is_array($arrays)){
        foreach ($arrays as $array){
            if(is_array($array)){
                $key_arrays[] = $array[$sort_key];
            }else{
                return false;
            }
        }
    }else{
        return false;
    }
    array_multisort($key_arrays,$sort_order,$sort_type,$arrays);
    return $arrays;
}