<?php

/**
 * PHP 版本Rest客户端
 * 
 * 用于PHP对接Rest微服务接口使用
 * @author    huxg(905988767@qq.com)
 * @version   v1.0.1
 */
class RestClient
{
	//请求的APPID
	const APPID     = ;
	
	//请求的密钥
	const APPSECRET = '';
		
	//请求url
	private $url;
	
	//请求的类型
	private $requestType;

	//请求的数据
	private $data  = array();
	
	//生成密钥参数
	private $param = array();
	
	//curl实例
	private $curl;
	
	//并发请求
	public  $multi;
	
	private $headers = array();
	
	/**
	 * [__construct 构造方法,     初始化数据]
	 * @param [type] $url         请求的服务器地址
	 * @param [type] $requestType 发送请求的方法
	 * @param [type] $data        POST方法提交所需参数
	 */
	public function __construct($url = '', $requestType = 'get', $data = array()) 
	{	
		if(!$url) return false;
		
		$this->requestType = strtolower($requestType);
		
		//判断是否属于并发请求
		if(is_array($url) && $requestType=='get')
		{
			$this->multi = true;
			$urls = array();
			foreach($url as $key=>$url)
			{
				$urls[] = $this->setUrl($url, $data[$key]);
			}
			$this->url  = $urls;
			$this->curl = curl_multi_init(); 
		}else{
			$this->url = $this->setUrl($url, $data);
			try{
				if(!$this->curl = curl_init())
				{
					throw new Exception('curl初始化错误：');
				}
			}catch (Exception $e)
			{
				return $e->getMessage();
			}
		}
	}
	
	/**
	 * curl参数配置
	 * @return void 
	 */
	private function curlSetopt($curl, $url) 
	{
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); //发起连接等待时间
		curl_setopt($curl, CURLOPT_TIMEOUT, 10); //发送超时
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/34.0.1847.116 Safari/537.36');
	}
	
	/**
	 * curl参数配置
	 * @return void
	 */
	private function setUrl($url, $data) 
	{
		$this->data  = $data;
		$this->param = $this->getParam($this->data);
		
		if(!empty($this->param)) 
		{
			foreach($this->param as $key => $value) 
			{
				$array[] = $key . '=' . $value;
			}
			
			$paramurl  = implode('&',$array);
			return $url .'?'. $paramurl;
		}
	}
	
	/**
	 * 设置get请求的参数
	 * @return [type] [description]
	 */
	private function get() 
	{
		$this->curlSetopt($this->curl, $this->url); 
		curl_setopt($this->curl, CURLOPT_HTTPGET, true);
	}
	
	/**
	 * 设置post请求的参数
	 * @return void
	 */
	private function post() 
	{
		$this->curlSetopt($this->curl, $this->url); 
		curl_setopt($this->curl, CURLOPT_POST, 1);
		curl_setopt($this->curl, CURLOPT_POSTFIELDS, $this->data);
	}
	
	/**
	 * 设置put请求
	 * @return void
	 */
	private function put() 
	{
		$this->curlSetopt($this->curl, $this->url); 
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'PUT');
	}

	/**
	 * 删除资源
	 * @return void
	 */
	private function delete() 
	{
		$this->curlSetopt($this->curl, $this->url); 
		curl_setopt($this->curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
	}
	
	/**
	 * 执行发送请求
	 * @return void
	 */
	public function doRequest() 
	{
		//判断是否并发请求
		if($this->multi)
		{
			return $this->mutiRequest($this->url);
		}
		
		//发送请求方式
		switch ($this->requestType) 
		{
			case 'post':
				$this->post();
				break;
			case 'put':
				$this->put();
				break;
			case 'delete':
				$this->delete();
				break;
			default:
				$this->get();
			break;
		}
		
		//执行curl请求
		$info = curl_exec($this->curl);
		
		if($info=== false)
		{
			$data['code']    = 400;
			$data['message'] = curl_error($this->curl);
			return $data;
		}
		
		return $this->getData($info);
	}

	/**
	 * 设置发送的头部信息
	 * @return void
	 */
	private function setHeader()
	{	
		curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
	}
	
	/**
	 * 获取curl中的状态信息
	 * @return void
	 */
	private function getInfo()
	{
		return curl_getinfo($this->curl);
	}
		
	/**
	 * 生成签名
	 * @param $args为请求参数，
	 * @param $key为私钥
	 */
	private function makeSignature($args, $key)
	{
		$requestString = '';

		if(isset($args['sign'])) 
		{
			unset($args['sign']);
		}
		
		ksort($args);
		
		foreach($args as $k => $v) {
			$requestString .= $k . '=' . urlencode($v);
		}
		
		return hash_hmac("md5",strtolower($requestString) , $key);
	}

	/**
	 * 获取签名所需参数
	 * @param  $data POST方法提交所需参数,其他方法忽略
	 * @param  appid 设备号或者机器码
	 * @param  sign  签名
	 */
	private function getParam($data)
	{
		$param = Request::instance()->param();
		unset($param['__token__']);
		$param = array_merge($param, $data);
		$param['appid'] 	= self::APPID;
		$param['timestamp'] = time();
		
		$param['sign'] 		= $this->makeSignature($param,self::APPSECRET);
		
		return $param;
	}
	
	/**
	 * 获取签名所需参数
	 */
	private function getData($data=array())
	{
		//获取curl执行状态信息
		$status = $this->getInfo();
			
		if($status['http_code']==200)
		{
			return json_decode($data,true);
		}else
		{
			//记录到错误日志使用Mongodb,这里使用TP5自带的日志记录
			\think\Log::write($status,'error');
		}
	}
	
	/**
	 * 并发url请求只适合GET请求
	 * @param $urls array url 请求地址
	 */
	private function mutiRequest($urls)
	{ 
        $curl_array = array(); 
        foreach($urls as $i => $url) 
        { 
			$curl_array[$i] = curl_init();
			$this->curlSetopt($curl_array[$i], $url);
            curl_multi_add_handle($this->curl, $curl_array[$i]); 
        }
        $running = NULL; 
        do { 
            usleep(10000); 
            curl_multi_exec($this->curl,$running); 
        } while($running > 0); 
        
        $res = array(); 
        foreach($urls as $i => $url) 
        { 
			$content = curl_multi_getcontent($curl_array[$i]);
			$res[$i] = json_decode($content, true);
        } 
        
        foreach($urls as $i => $url){ 
            curl_multi_remove_handle($this->curl, $curl_array[$i]); 
        }
        curl_multi_close($this->curl);
        return $res;
	}
		
	/**
	 * 关闭curl连接
	 */
	public function __destruct()
	{
		curl_close($this->curl);
	}
}
