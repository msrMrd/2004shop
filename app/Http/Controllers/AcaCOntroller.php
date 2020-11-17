<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AcaCOntroller extends Controller
{
    public function text(){
      $data=[    //定义一个数据
            "name"=>"李亚周",
            "sex"=>"nan",
            "tel"=>"111111"
        ];
       echo json_encode($data);   //让后返回json格式
    }

    public function index(){
//        echo '<pre>';print_r($_GET);echo '</pre>';
//        echo '<pre>';print_r($_POST);echo '</pre>';
        $code=request()->get('code');
        $appid="wx14c27e149e84b960";
        $secret="bba4bcb902f416addae591d12fbe1441";
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code";
        $res=json_decode(file_get_contents($url),true);
        if(isset($res['errcode '])){    //如果有和这个
                $data=[
                    'error'=>'50001',
                    'msg'=>'登陆失败'
                ];
            return $data;
        }else{
            $token=sha1($res['openid'].$res['session_key'].mt_rand(0,99999));   //根据 用户唯一标识和会话密钥 拼接成一个token
            $redis_key="wxxcxkey:".$token;
                Redis::set($redis_key,time());
                Redis::expire($redis_key,7200);
            $data=[
                    'error'=>'0',
                    'msg'=>'登陆成功',
                    'data'=>[
                        'token'=>$token
                    ]
                ];
            return $data;     //

        }
    }

}
