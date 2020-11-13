<?php

namespace App\Http\Controllers;

use App\Models\Imga;
use App\Models\Media;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Log;
use GuzzleHttp\Client;
class Test1Controller extends Controller
{
    public function index(){
        $res=request()->get('echostr','');
        if($this->checkSignature() && !empty($res)){
            echo $res;
        }else{
//
            $xml=file_get_contents("php://input");//获取微信公众平台传过来的信息
               $obj=simplexml_load_string($xml,"SimpleXMLElement",LIBXML_NOCDATA);//将一个xml格式的对象
            file_put_contents("wx2004.txt",$xml,FILE_APPEND);

            if($obj->MsgType=="video" || $obj->MsgType=="image" || $obj->MsgType=="text" || $obj->MsgType=="voice" ){   //不是关注 也不是取消关注的
                $this->typeContent($obj);         //先调用这方法 判断是什么类型 ，在添加数据库9
            }
            //签到
            if($obj->EventKey=="V1001_TODAY_MUSIC") {
                $key = $obj->FromUserName;
                $times = date("Y-m-d", time());
                $date = Redis::zrange($key, 0, -1);
                if ($date) {
                    $date = $date[0];
                }
                if ($date == $times) {
                    $content = "您今日已经签到过了!";
                } else {
                    $zcard = Redis::zcard($key);
                    if ($zcard >= 1) {
                        Redis::zremrangebyrank($key, 0, 0);
                    }
                    $keys = json_decode(json_encode($obj),true);


                    $keys = $keys['FromUserName'];
                    $zincrby = Redis::zincrby($key, 1, $keys);
                    $zadd = Redis::zadd($key, $zincrby, $times);
                    $content = "签到成功您以积累签到" . $zincrby . "天!";
                }

            }
                switch($obj->MsgType){
                    case "event":
                        //关注
                        if($obj->Event=="subscribe"){
                            $openid=$obj->FromUserName;   //获取用户的openid
                            $AccessToken=$this->getAccesstoken();   //获取token
                            $url="https://api.weixin.qq.com/cgi-bin/user/info?access_token=".$AccessToken."&openid=".$openid."&lang=zh_CN";
//                            dd($url);
                            $user=file_get_contents($url);    //获取第三方 的数据
                            $user=json_decode($user,true);
                            //查到了
//                                if(!Redis::get($openid)){
//                                    Redis::set($openid,'gggt');
//                                    $content="谢谢你关注";
//                                    echo   $this->text($obj,$content);
//                                }else{
//                                    $content="谢谢你们再次关注,我们加倍努力的";
//                                    echo   $this->text($obj,$content);
//                                }
                            if(isset($user['errcode'])){
                                $this->writeLog("获取用户信息失败了");

                            }else{
                                $user_id=User::where('openid',$openid)->first();   //查询一条
                                if($user_id){
                                    $user_id->subscribe=1;   //查看这个用户的状态  1关注   0未关注
                                    $user_id->save();
                                    $content="谢谢你们再次关注,我们加倍努力的";
//                                    echo $this->text($obj,$content);
                                }else{
                                    $res=[
                                        "subscribe"=>$user["subscribe"],
                                        "openid"=>$user["openid"],
                                        "nickname"=>$user["nickname"],
                                        "sex"=>$user["sex"],
                                        "city"=>$user["city"],
                                        "country"=>$user["country"],
                                        "province"=>$user["province"],
                                        "language"=>$user["language"],
                                        "headimgurl"=>$user["headimgurl"],
                                        "subscribe_time"=>$user["subscribe_time"],
                                        "subscribe_scene"=>$user["subscribe_scene"]
                                    ];
                                    User::insert($res);
                                    $content="官人，谢谢关注！";
//                                    echo $this->text($obj,$content);

                                }
                            }

                        }
                        //取消关注
                        if($obj->Event=="unsubscribe"){
                            $user_id->subscribe=0;
                            $user_id->save();
                        }
                        echo   $this->text($obj,$content);
                        break;
                    //天气
                    case "text":
                        $city=urlencode(str_replace("天气:","",$obj->Content));//城市名称是字符串
                        $key="77aee97ce2cadb280fab57b84a151966";
                        $url="http://apis.juhe.cn/simpleWeather/query?city=".$city."&key=".$key;
                        $result=file_get_contents($url);
                        $result=json_decode($result,true);
                        if($result['error_code']==0){
                            $today=$result["result"]['realtime'];   //获取本天的天气
                            $content="查询天气的城市：".$result["result"]["city"]."\n";
                            $content.="天气详细情况：".$today["info"];
                            $content.="温度：".$today["temperature"]."\n";
                            $content.="湿度：".$today["humidity"]."\n";
                            $content.="风向：".$today["direct"]."\n";
                            $content.="风力：".$today["power"]."\n";
                            $content.="空气质量指数：".$today["aqi"]."\n";
                            //获取一个星期的
                            $future=$result["result"]["future"];
                            foreach($future as $k=>$v){
                                $content.="日期:".date("Y-m-d",strtotime($v["date"])).$v['temperature'].",";
                                $content.="天气:".$v['weather']."\n";
                            }
//                            echo $this->text($obj,$content);
                        }else{
                            $content="你的查询天气失败，你的格式是天气:城市,这个城市不属于中国";
                        }
                       echo $this->text($obj,$content);
                        break;
                }

        }
    }

    //判断类型
    private function writeLog($data){
        if(is_object($data) || is_array($data)){   //不管是数据和对象都转json 格式
            $data=json_encode($data);
        }
        file_put_contents('2004.txt',$data);die;
    }

    //接收微信公众传过来的信息
    private function receiveMsg(){
        $xml=file_get_contents("php://input");//获取微信公众平台创过来的信息
//            file_put_contents("data.txt",$data); //将数据写入到某个文件
        $obj=simplexml_load_string($xml,"SimpleXMLElement",LIBXML_NOCDATA);//将一个xml格式的字符串转化为一个对象方便使用
        return $obj;
    }

    //对接
    private function checkSignature()
    {
        $signature =request()->get("signature");
        $timestamp =request()->get ("timestamp");
        $nonce = request()->get('nonce');

        $token = "Tokes";
        $tmpArr = array($token, $timestamp, $nonce);
        sort($tmpArr, SORT_STRING);
        $tmpStr = implode( $tmpArr );
        $tmpStr = sha1( $tmpStr );

        if( $tmpStr == $signature ){
            return true;
        }else{
            return false;
        }
    }

    //guzlle发送请求
    public function geta(){
//        echo "qqq";die;
        $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('MIX_APPID')."&secret=".env('MIX_SECRET');
        $client=new Client();
        $resource=$client->request('GET',$url,['verify'=>false]);

        $json_str=$resource->getBody();   //服务器响应的数据
        echo $json_str;
    }

      #################### 素材 ################

    public function getb(){
        $token=$this->getAccesstoken();
//        dd($token);
        $type="image";
        $url="https://api.weixin.qq.com/cgi-bin/media/upload?access_token=".$token."&type=".$type;
        $client=new Client();
        $resource=$client->request('POST',$url,[
            'verify'=>false,
            'multipart' => [
                [ 'name' =>"media",
                    'contents' =>fopen('gsi.jpg','r')
                ],
            ]
        ]);   //发送请求想起应
        $data = $resource->getBody();   //服务器响应的
        echo $data;
    }

    #################### 自定义菜单 ################

    public function textmenu(){
        $token=$this->getAccesstoken();
        $url="https://api.weixin.qq.com/cgi-bin/menu/create?access_token=".$token;
        $menu=[
            "button"=>[
        [
                    "name"=>"2004wx",
                    "sub_button"=>[
                            [
                            "type"=>"view",
                            "name"=>"项目",
                            "url"=>"http://www.jd.com/"
                                ]
                            ]
                    ],
             [
                    "name"=>"操作",
                    "sub_button"=>[[
                        "type"=>"click",
                        "name"=>"签到",
                         "key"=> "V1001_TODAY_MUSIC"
                    ],
                    [
                        "type"=>"view",
                        "name"=>"京东",
                        "url"=>"http://www.jd.com/"
                    ]
                    ]
                    ],
                    [
                    "name"=>"访问量",
                    "sub_button"=>[[
                        "type"=>"view",
                        "name"=>"搜索",
                        "url"=>"http://www.baidu.com/"
                    ],
                    [
                        "type"=>"view",
                        "name"=>"淘宝",
                        "url"=>"http://www.taobao.com/"
                    ],
                    [
                        "type"=>"view",
                        "name"=>"京东",
                        "url"=>"http://www.jd.com/"
                    ]
                    ]
                ]
            ]
        ];
        $client=new Client();
        $resource=$client->request('POST',$url,[
            'verify'=>false,
            'body'=>json_encode($menu,JSON_UNESCAPED_UNICODE)
        ]);   //发送请求想起应
        $data = $resource->getBody();   //服务器响应的
        echo $data;
    }

    #################文本消息################

    function text($obj,$content){
        $ToUserName=$obj->FromUserName;
        $FromUserName=$obj->ToUserName;
        $CreateTime=time();
        $MsgType="text";

        $xml="<xml>
              <ToUserName><![CDATA[%s]]></ToUserName>
              <FromUserName><![CDATA[%s]]></FromUserName>
              <CreateTime>%s</CreateTime>
              <MsgType><![CDATA[%s]]></MsgType>
              <Content><![CDATA[%s]]></Content>
            </xml>";
        echo sprintf($xml,$ToUserName,$FromUserName,$CreateTime,$MsgType,$content);
    }
#################消息入库################
 public  function typeContent($obj){
     $res=Media::where("media_id",$obj->MediaId)->first();
     $token=$this->getAccesstoken();     //获取token
     if(empty($res)){   //如果没有的话就执行添加
         $url="https://api.weixin.qq.com/cgi-bin/media/get?access_token=".$token."&media_id=".$obj->MediaId;
         $url=file_get_contents($url);
         $data=[           //类型公用的   然后类型不一样的往$data里面插数据
             "time"=>time(),
             "msg_type"=>$obj->MsgType,
             "openid"=>$obj->FromUserName,
             "msg_id"=>$obj->MsgId
         ];
         //图片
         if($obj->MsgType=="image"){
             $file_type = '.jpg';
             $data["url"] = $obj->PicUrl;
                $data["media_id"] = $obj->MediaId;
         }
         //视频
         if($obj->MsgType=="video"){
             $file_type = '.mp4';
             $data["media_id"]=$obj->MediaId;

         }
//         文本
         if($obj->MsgType=="text"){
             $file_type = '.txt';
             $data["content"]=$obj->Content;
         }
         //音频
         if($obj->MsgType=="voice"){
             $file_type = '.amr';
             $data["media_id"]=$obj->MediaId;

         }
         if(!empty($file_type)){    //如果不是空的这下载
             file_put_contents("dwaw".$file_type,$url);
         }
         Media::create($data);


     }else{
        return $res;
     }

     return true;
 }

//    ############################图片消息##########################
//    function imgContent($obj,$content){
//        $ToUserName=$obj->FromUserName;
//        $FromUserName=$obj->ToUserName;
//        $CreateTime=time();
//        $xml="<xml>
//              <ToUserName><![CDATA[%s]]></ToUserName>
//              <FromUserName><![CDATA[%s]]></FromUserName>
//              <CreateTime>%s</CreateTime>
//              <MsgType><![CDATA[%s]]></MsgType>
//              <Image>
//              <MsgId>%s</MsgId>
//              </Image>
//            </xml>";
//        echo sprintf($xml,$ToUserName,$FromUserName,$CreateTime,'image',$content);
//    }
//
//
//
//
//#############################音频消息####################
//    public function voiceContent($obj,$content){
//        $ToUserName=$obj->FromUserName;
//        $FromUserName=$obj->ToUserName;
//        $CreateTime=time();
//        $xml="
//            <xml>
//              <ToUserName><![CDATA[%s]]></ToUserName>
//              <FromUserName><![CDATA[%s]]></FromUserName>
//              <CreateTime>%s</CreateTime>
//              <MsgType><![CDATA[%s]]></MsgType>
//              <Voice>
//              <MediaId><![CDATA[%s]]></MediaId>
//              </Voice>
//            </xml>";
//        echo sprintf($xml,$ToUserName,$FromUserName,$CreateTime,'voice',$content);
//
//    }
//
//
//    ##############################视频消息###################
//    public function videoContent($obj,$content,$title,$description){
//        $ToUserName=$obj->FromUserName;
//        $FromUserName=$obj->ToUserName;
//        $CreateTime=time();
//        $xml="
//        <xml>
//          <ToUserName><![CDATA[%s]]></ToUserName>
//          <FromUserName><![CDATA[%s]]></FromUserName>
//          <CreateTime>%s</CreateTime>
//          <MsgType><![CDATA[%s]]></MsgType>
//          <MediaId><![CDATA[%s]]></MediaId>
//          <ThumbMediaId><![CDATA[%s]]></ThumbMediaId>
//          <MsgId>%s</MsgId>
//        </xml>";
//        echo sprintf($xml,$ToUserName,$FromUserName,$CreateTime,'video',$content,$title,$description);
//
//    }s
}
