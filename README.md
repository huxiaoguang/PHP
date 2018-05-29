PHP版本Rest客户端使用说明
======================

1.GET 请求得使用

//例如：获取文章ID为70的文章(单一请求)
$Request = new RestClient($this->url . 'v1/article/70', 'get');
$article = $Request->doRequest();

if($article['code']==200)
{
	$this->assign('article', $article['data']);
}

//例如：获取文章ID为100和76的文章(并发请求)
$urls    = [$this->url . 'v1/article/100', $this->url . 'v1/article/76'];	
$Request = new RestClient($urls, 'get');
$article = $Request->doRequest();

if($article[0]['code']==200)
{
	$this->assign('new_articel', $article[0]['data']['content']);
}
if($article[1]['code']==200)
{
	$this->assign('hot_articel', $article[1]['data']['content']);
}

2.POST 请求方法的使用

//例如：Ajax用户收藏商品
$param['uid']	   = 100;
$param['goods_id'] = 1000;

if($param['uid'] && $param['goods_id'])
{
	$Request = new RestClient($this->url . 'v1/user/goods/collect', 'post', $param);
	$result  = $Request->doRequest();
	
	if($result['code']==200)
	{
		$data = ['status'=>1,'msg'=>$result['message']];
	}else{
		$data = ['status'=>0,'msg'=>$result['message']];
	}
}else{
	$data = ['status'=>0,'msg'=>'参数错误'];
}

return json($data);

3.PUT 请求方法的使用

//例如：Ajax用户修改密码
$param['uid']    = session('uid');
$param['oldpwd'] = $_POST['oldpwd'];
$param['newpwd'] = $_POST['newpwd'];
$param['revpwd'] = $_POST['revpwd'];

//密码验证判断省略.....

if($param['uid'])
{
	$rest 	= new RestClient($this->url . 'v1/user/password/'.$uid, 'put', $param);
	$result = $rest->doRequest();

	if($result['code']==200)
	{
		$data = ['status'=>1, 'msg'=>$result['message']];
	}else{
		$data = ['status'=>0, 'msg'=>$result['message']];
	}
	
	return json($data);
}

4.DELETE AJAX请求方法的使用

//例如：删除文章操作
$id = $_POST['id'];
	
if($id)
{
	$rest 	= new RestClient($this->url . 'v1/article/'.$aid, 'delete');
	$result = $rest->doRequest();
	
	if($result['code']==200)
	{
		$data = ['status'=>1, 'msg'=>$result['message']];
	}else{
		$data = ['status'=>0, 'msg'=>$result['message']];
	}
}else{
	$data = ['status'=>0,'msg'=>'参数错误'];
}

return json($data);

接口返回JSON格式
[{
	
	"code": 200,
	"message": "操作成功",
	"data": ""
}]

code 	: 接口状态码
message ：接口提示信息
data	：接口返回数据
