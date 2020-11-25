<?php

namespace App\Http\Controllers;

use App\Model\Cate;
use App\Model\GoodsModel;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use App\Models\Xcx;
use DB;
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
    //小程序 首页商品
    public function viewa(){
        $page_size=request()->get("size");
        $goods=GoodsModel::select('goods_id','goods_name','goods_price','goods_img',"goods_num")->paginate($page_size);
//        return $goods;
        $response = [
            "error"=>0,
            "msg"=>'ok',
            "data"=>[
                'list'=>$goods->items()
            ]
        ];
        return $response;
    }
    //小程序 导航栏
    public function category(){
        if(!Redis::get("daohanglan")){
            $cate=Cate::where("parent_id",0)->get()->toArray();
            $data=[
                "error"=>0,
                "msg"=>'成功',
                "data"=>$cate
            ];
            Redis::setex("daohanglan",3600,serialize($data));
            return $data;
        }else{
            return unserialize(Redis::get("daohanglan"));
        }

    }

    //小程序 详情页面
    public function details(){
        $goods_id=request()->get('goods_id');
//        dd($goods_id);
        $detail=GoodsModel::select("goods_id","goods_name","goods_img","goods_imgs","goods_num","goods_price","goods_desc")->where('goods_id',$goods_id)->first()->toArray();
//        dd($detail);
        $detail=[
            "goods_id"=>$detail['goods_id'],
            "goods_name"=>$detail['goods_name'],
            "goods_imgs"=>explode(",",$detail['goods_imgs']),
            "goods_img"=>$detail['goods_img'],
            "goods_num"=>$detail['goods_num'],
            "goods_price"=>$detail['goods_price'],
        ];
        $detail=[
            "errcode"=>0,
            "errMsg"=>"获取成功",
            "data"=>$detail
//            "goods_imgs"=>explode(',',$detail)
        ];
        return $detail;
    }
    //openid存数据库
    public function openid(){
        $code=request()->get('code');
//        print_r($code);
        //获取用户信息
        $userinfo=json_decode(file_get_contents("php://input"),true);
//        return $userinfo;
        //使用code
        $appid="wx14c27e149e84b960";
        $secret="bba4bcb902f416addae591d12fbe1441";
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code";
        $data=json_decode(file_get_contents($url),true);
        if(isset($data['errcode'])){
            $respose=[
                'error'=>50001,
                'msg'=>'登陆失败',
            ];
        }else{
            $openid=$data['openid'];
            $re=DB::table("userxcx")->where(['openid'=>$openid])->first();
            if($re){

            }else{
               $u_info= [
                   'openid'=>$openid,
                   'nickName'=>$userinfo['u']['nickName'],
                   'city'=>$userinfo['u']['city'],
                   'language'=>$userinfo['u']['language'],
                   'province'=>$userinfo['u']['province'],
                   'country'=>$userinfo['u']['country'],
                   'gender'=>$userinfo['u']['gender'],
                   'avatarUrl'=>$userinfo['u']['avatarUrl'],
               ];
                DB::table("userxcx")->insertGetId($u_info);
            }
            //生成token
            $token=sha1($data['openid'].$data['session_key'].mt_rand(0,999999));
            $data=[
                'error'=>'0',
                'msg'=>'登陆成功',
                'data'=>[
                    'token'=>$token
                ]
            ];
            return $data;
        }
    }

    //购物车

    //导航栏数据
    public function cate_id(){
    $cate_id=request()->get("cate_id",1);

    if($cate_id != 0){
        $cate_id2=DB::table("shop_cate")->select('cate_id')->where('parent_id',$cate_id)->get();
//        dd($cate_id2);
        $arr=[];
        foreach($cate_id2 as $k=>$v){
            foreach($v as $l=>$a){
                $arr[]= $a;
            }
        }
//        dd($arr);
        $cate_id3=DB::table("shop_cate")->select('cate_id')->whereIn('parent_id',$arr)->get();
//        dd($cate_id3);
        $arr=[];
        foreach($cate_id3 as $k=>$v){
            foreach($v as $l=>$a){
                $arr[]= $a;
            }
        }

//        dd($arr);
        $goods=DB::table('goods')->select('goods_id','goods_name','goods_price','goods_img')->whereIn('cate_id',$arr)->paginate(10);
//        dd(/$goods);


    }else{
        $goods=DB::table("goods")->select('goods_id','goods_name','goods_price','goods_img')->paginate(10);

    }
//        print_r($arr);die;
//        dd($goods);
    return $goods;
    }
    public function cart(){
        $goods_id=request()->get('goods_id');
//        print_r($cart_id);
        //根据商品id去往商品表查商品 让后再从商品表里查出来的数据 添加到购物车表中
        $goods=GoodsModel::select("goods_name","goods_img","goods_imgs","goods_num","goods_price")->find($goods_id);
//        print_r($goods);
        $cart=Cart::where(["goods_id"=>$goods_id])->first();
        return $cart;
    }

    public function index(){
//        echo '<pre>';print_r($_GET);echo '</pre>';
//        echo '<pre>';print_r($_POST);echo '</pre>';
        $code=request()->get('code');
        $appid="wx14c27e149e84b960";
        $secret="bba4bcb902f416addae591d12fbe1441";
        $url="https://api.weixin.qq.com/sns/jscode2session?appid=".$appid."&secret=".$secret."&js_code=".$code."&grant_type=authorization_code";
        $res=json_decode(file_get_contents($url),true);
        if(isset($res['errcode '])){    //如果有错误码就返回登陆失败
                $data=[
                    'error'=>'50001',
                    'msg'=>'登陆失败'
                ];
            return $data;
        }else{

            if(empty(Xcx::where("openid",$res['openid'])->first())){
//                $data=$res['openid'];
                Xcx::insert(['openid'=>$res['openid']]);

            }
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
