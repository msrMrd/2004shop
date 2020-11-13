<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;


//    public function http_get($url)
//    {
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, $url);//向那个url地址上面发送
//        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);//设置发送http请求时需不需要证书
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//设置发送成功后要不要输出1 不输出，0输出
//        $output = curl_exec($ch);//执行
//        curl_close($ch);    //关闭
//        return $output;
//    }
//
//    public function http_post($url, $data)
//    {
//        $curl = curl_init(); //初始化
//        curl_setopt($curl, CURLOPT_URL, $url);//向那个url地址上面发送
//        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
//        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);//需不需要带证书
//        curl_setopt($curl, CURLOPT_POST, 1); //是否是post方式 1是，0不是
//        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//需不需要输出
//        $output = curl_exec($curl);//执行
//        curl_close($curl); //关闭
//        return $output;
//    }
//
//    //access_token 证明的方法
//    public function get_access_token()
//    {
//        //先查询数据是否过期
//        $accessToken=AccessToken::orderBy("id","desc")->first();
//        //如果过期了，或者是没有 那么就直接加入数据库
//        if(!$accessToken|| time()-$accessToken->access_token_time>7000) {
//            $appid = "wx0d77b8bcd6646061";
//            $appsecret = "19433a1e5a2751de65eef68d114e0f23";
//            $url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $appsecret;
//            $result = $this->http_get($url);//用get方式
////            file_put_contents("data.txt",$result);
//            $result = json_decode($result, true);
////            判断
//            if (isset($result["access_token"])) {
//                //添加到数据库里
//                $accesstokemModel = new AccessToken();
//                $accesstokemModel->access_token = $result["access_token"];
//                $accesstokemModel->access_token_time = time();
//                $accesstokemModel->save();
//                return $result["access_token"];
//            } else {
//                return false;
//            }
//        }else{
//            return $accessToken->access_token;
//        }
//    }
//access_token
    public function getAccesstoken(){
        $key='weiAccess_token';
        if(!Redis::get($key)){
            $url="https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".env('MIX_APPID')."&secret=".env('MIX_SECRET')."";

            //第一种方式
            $token=file_get_contents($url);
            //第二种方式
//            $client=new Client();
//            $resource=$client->request('GET',$url,['verify'=>false]);
//
//            $json_str=$resource->getBody();
//        dd($token);
            $token=json_decode($token,true);
            //判断token是否存在
            if(isset($token['access_token'])){
                $token=$token['access_token'];
                Redis::setex($key,3600,$token);
                return $token;
//                    dd($token);
            }else{
                return false;
            }
        }else{
            return  Redis::get($key);
        }


    }
}
