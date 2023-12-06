<?php

namespace Superzc\QQConnect;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Log;
use Superzc\QQConnect\Exceptions\DefaultException;

class QQConnect extends Oauth
{
    private $keysArr;
    private $APIMap;

    protected $appid;
    protected $appkey;
    protected $callback;
    protected $scope;
    protected $openid;
    protected $access_token;

    /**
     * 构造方法
     */
    public function __construct()
    {
        parent::__construct();

        //初始化APIMap
        /*
         * 加#表示非必须，无则不传入url(url中不会出现该参数)， "key" => "val" 表示key如果没有定义则使用默认值val
         * 规则 array( baseUrl, argListArr, method)
         */
        $this->APIMap = array(
            /*                       qzone                    */
            "add_blog" => array(
                "https://graph.qq.com/blog/add_one_blog",
                array("title", "format" => "json", "content" => null),
                "POST"
            ),
            "add_topic" => array(
                "https://graph.qq.com/shuoshuo/add_topic",
                array("richtype","richval","con","#lbs_nm","#lbs_x","#lbs_y","format" => "json", "#third_source"),
                "POST"
            ),
            "get_user_info" => array(
                "https://graph.qq.com/user/get_user_info",
                array("format" => "json"),
                "GET"
            ),
            "add_one_blog" => array(
                "https://graph.qq.com/blog/add_one_blog",
                array("title", "content", "format" => "json"),
                "GET"
            ),
            "add_album" => array(
                "https://graph.qq.com/photo/add_album",
                array("albumname", "#albumdesc", "#priv", "format" => "json"),
                "POST"
            ),
            "upload_pic" => array(
                "https://graph.qq.com/photo/upload_pic",
                array("picture", "#photodesc", "#title", "#albumid", "#mobile", "#x", "#y", "#needfeed", "#successnum", "#picnum", "format" => "json"),
                "POST"
            ),
            "list_album" => array(
                "https://graph.qq.com/photo/list_album",
                array("format" => "json")
            ),
            "add_share" => array(
                "https://graph.qq.com/share/add_share",
                array("title", "url", "#comment","#summary","#images","format" => "json","#type","#playurl","#nswb","site","fromurl"),
                "POST"
            ),
            "check_page_fans" => array(
                "https://graph.qq.com/user/check_page_fans",
                array("page_id" => "314416946","format" => "json")
            ),
            /*                    wblog                             */
            "add_t" => array(
                "https://graph.qq.com/t/add_t",
                array("format" => "json", "content","#clientip","#longitude","#compatibleflag"),
                "POST"
            ),
            "add_pic_t" => array(
                "https://graph.qq.com/t/add_pic_t",
                array("content", "pic", "format" => "json", "#clientip", "#longitude", "#latitude", "#syncflag", "#compatiblefalg"),
                "POST"
            ),
            "del_t" => array(
                "https://graph.qq.com/t/del_t",
                array("id", "format" => "json"),
                "POST"
            ),
            "get_repost_list" => array(
                "https://graph.qq.com/t/get_repost_list",
                array("flag", "rootid", "pageflag", "pagetime", "reqnum", "twitterid", "format" => "json")
            ),
            "get_info" => array(
                "https://graph.qq.com/user/get_info",
                array("format" => "json")
            ),
            "get_other_info" => array(
                "https://graph.qq.com/user/get_other_info",
                array("format" => "json", "#name", "fopenid")
            ),
            "get_fanslist" => array(
                "https://graph.qq.com/relation/get_fanslist",
                array("format" => "json", "reqnum", "startindex", "#mode", "#install", "#sex")
            ),
            "get_idollist" => array(
                "https://graph.qq.com/relation/get_idollist",
                array("format" => "json", "reqnum", "startindex", "#mode", "#install")
            ),
            "add_idol" => array(
                "https://graph.qq.com/relation/add_idol",
                array("format" => "json", "#name-1", "#fopenids-1"),
                "POST"
            ),
            "del_idol" => array(
                "https://graph.qq.com/relation/del_idol",
                array("format" => "json", "#name-1", "#fopenid-1"),
                "POST"
            ),
            /*                           pay                          */
            "get_tenpay_addr" => array(
                "https://graph.qq.com/cft_info/get_tenpay_addr",
                array("ver" => 1,"limit" => 5,"offset" => 0,"format" => "json")
            )
        );
    }

    // 初始化
    public function init($openid, $access_token)
    {
        $this->appid = config('qqconnect.appid');
        $this->appkey = config('qqconnect.appkey');
        $this->callback = config('qqconnect.callback');
        $this->scope = config('qqconnect.scope', 'get_user_info');

        $this->openid = $openid;
        $this->access_token = $access_token;

        $this->keysArr = array(
            "oauth_consumer_key" => $this->appid,
            "access_token" => $this->access_token,
            "openid" => $this->openid,
        );
    }

    // 调用相应api
    private function _applyAPI($arr, $argsList, $baseUrl, $method)
    {
        $pre = "#";
        $keysArr = $this->keysArr;

        $optionArgList = array();//一些多项选填参数必选一的情形
        foreach($argsList as $key => $val) {
            $tmpKey = $key;
            $tmpVal = $val;

            if(!is_string($key)) {
                $tmpKey = $val;

                if(strpos($val, $pre) === 0) {
                    $tmpVal = $pre;
                    $tmpKey = substr($tmpKey, 1);
                    if(preg_match("/-(\d$)/", $tmpKey, $res)) {
                        $tmpKey = str_replace($res[0], "", $tmpKey);
                        $optionArgList[$res[1]][] = $tmpKey;
                    }
                } else {
                    $tmpVal = null;
                }
            }

            //-----如果没有设置相应的参数
            if(!isset($arr[$tmpKey]) || $arr[$tmpKey] === "") {

                if($tmpVal == $pre) {//则使用默认的值
                    continue;
                } elseif($tmpVal) {
                    $arr[$tmpKey] = $tmpVal;
                } else {
                    if($v = $_FILES[$tmpKey]) {

                        $filename = dirname($v['tmp_name']) . "/" . $v['name'];
                        move_uploaded_file($v['tmp_name'], $filename);
                        $arr[$tmpKey] = "@$filename";

                    } else {
                        throw new DefaultException('api调用参数错误: 未传入参数' . $tmpKey);
                    }
                }
            }

            $keysArr[$tmpKey] = $arr[$tmpKey];
        }
        //检查选填参数必填一的情形
        foreach($optionArgList as $val) {
            $n = 0;
            foreach($val as $v) {
                if(in_array($v, array_keys($keysArr))) {
                    $n++;
                }
            }

            if(!$n) {
                $str = implode(",", $val);
                throw new DefaultException('api调用参数错误: ' . $str . '必填一个');
            }
        }

        if($method == "POST") {
            $response = Http::post($baseUrl, $keysArr);
        } elseif($method == "GET") {
            $baseUrl = $this->combineURL($baseUrl, $keysArr);
            $response = Http::get($baseUrl);
        }

        return $response;

    }

    /**
     * _call
     * 魔术方法，做api调用转发
     * @param string $name    调用的方法名称
     * @param array $arg      参数列表数组
     * @since 5.0
     * @return array          返加调用结果数组
     */
    public function __call($name, $arg)
    {
        //如果APIMap不存在相应的api
        if(empty($this->APIMap[$name])) {
            throw new DefaultException('API调用参数错误，不存在的API: ' . $name);
        }

        //从APIMap获取api相应参数
        $baseUrl = $this->APIMap[$name][0];
        $argsList = $this->APIMap[$name][1];
        $method = isset($this->APIMap[$name][2]) ? $this->APIMap[$name][2] : "GET";

        if(empty($arg)) {
            $arg[0] = null;
        }

        //对于get_tenpay_addr，特殊处理，php json_decode对\xA312此类字符支持不好
        if($name != "get_tenpay_addr") {
            $response = json_decode($this->_applyAPI($arg[0], $argsList, $baseUrl, $method));
            $responseArr = $this->objToArr($response);
        } else {
            $responseArr = $this->simple_json_parser($this->_applyAPI($arg[0], $argsList, $baseUrl, $method));
        }


        //检查返回ret判断api是否成功调用
        if($responseArr['ret'] == 0) {
            return $responseArr;
        } else {
            throw new DefaultException($response->msg, $response->ret);
        }

    }

    //php 对象到数组转换
    private function objToArr($obj)
    {
        if(!is_object($obj) && !is_array($obj)) {
            return $obj;
        }
        $arr = array();
        foreach($obj as $k => $v) {
            $arr[$k] = $this->objToArr($v);
        }
        return $arr;
    }

    //简单实现json到php数组转换功能
    private function simple_json_parser($json)
    {
        $json = str_replace("{", "", str_replace("}", "", $json));
        $jsonValue = explode(",", $json);
        $arr = array();
        foreach($jsonValue as $v) {
            $jValue = explode(":", $v);
            $arr[str_replace('"', "", $jValue[0])] = (str_replace('"', "", $jValue[1]));
        }
        return $arr;
    }

}

class Oauth
{
    public const VERSION = "2.0";
    public const GET_AUTH_CODE_URL = "https://graph.qq.com/oauth2.0/authorize";
    public const GET_ACCESS_TOKEN_URL = "https://graph.qq.com/oauth2.0/token";
    public const GET_OPENID_URL = "https://graph.qq.com/oauth2.0/me";

    protected $userData;

    public function __construct()
    {
        if(empty(Session::get('QC_userData'))) {
            $this->userData = [];
        } else {
            $this->userData = Session::get('QC_userData');
        }
    }

    public function qq_login()
    {
        //-------生成唯一随机串防CSRF攻击
        $state = md5(uniqid(rand(), true));

        //-------构造请求参数列表
        $keysArr = array(
            "response_type" => "code",
            "client_id" => $this->appid,
            "redirect_uri" => $this->callback,
            "state" => $state,
            "scope" => $this->scope
        );

        $login_url =  $this->combineURL(self::GET_AUTH_CODE_URL, $keysArr);

        header("Location:$login_url");
    }

    public function qq_callback()
    {
        $state = $this->userData['state'];

        //--------验证state防止CSRF攻击
        if(!$state || Request::get('state') != $state) {
            throw new DefaultException('参数state不匹配，跨站请求异常');
        }

        //-------请求参数列表
        $keysArr = array(
            "grant_type" => "authorization_code",
            "client_id" => $this->appid,
            "redirect_uri" => urlencode($this->callback),
            "client_secret" => $this->appkey,
            "code" => Request::get('code')
        );

        //------构造请求access_token的url
        $token_url = $this->combineURL(self::GET_ACCESS_TOKEN_URL, $keysArr);
        $response = Http::get($token_url);

        if(strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response  = substr($response, $lpos + 1, $rpos - $lpos - 1);
            $msg = json_decode($response);

            if(isset($msg->error)) {
                throw new DefaultException($msg->error_description, $msg->error);
            }
        }

        $params = array();
        parse_str($response, $params);

        $this->userData['access_token'] = $params['access_token'];
        return $params['access_token'];

    }

    public function get_openid()
    {

        //-------请求参数列表
        $keysArr = [
            "access_token" => $this->userData['access_token']
        ];

        $graph_url = $this->combineURL(self::GET_OPENID_URL, $keysArr);
        $response = Http::get($graph_url);

        //--------检测错误是否发生
        if(strpos($response, "callback") !== false) {
            $lpos = strpos($response, "(");
            $rpos = strrpos($response, ")");
            $response = substr($response, $lpos + 1, $rpos - $lpos - 1);
        }

        $user = json_decode($response);
        if(isset($user->error)) {
            throw new DefaultException($user->error_description, $user->error);
        }

        //------记录openid
        $this->userData['openid'] = $user->openid;
        return $user->openid;

    }

    /**
    * combineURL
    * 拼接url
    * @param string $baseURL   基于的url
    * @param array  $keysArr   参数列表数组
    * @return string           返回拼接的url
    */
    protected function combineURL($baseUrl, $keysArr)
    {
        $combined = $baseUrl . "?";
        $valueArr = array();

        foreach($keysArr as $key => $val) {
            $valueArr[] = "$key=$val";
        }

        $keyStr = implode("&", $valueArr);
        $combined .= ($keyStr);

        return $combined;
    }
}
