<?php
$http = new swoole_http_server("0.0.0.0", 9501);
//redis 存储任务处理结果和进度
$redis = new \Redis();
$redis->connect("127.0.0.1", 6379);

$http->set([
		'worker_num' => 2,
		'open_tcp_nodelay' => true,
		'task_worker_num' => 2,
		'daemonize' => true,
		'log_file' => '/tmp/swoole_http_server.log',
		]);

$http->on('request', function(swoole_http_request $request, swoole_http_response $response) use ($http, $redis) {
		//请求过滤
		if($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico'){
		return $response->end();
		}   
		$taskId = isset($request->get['taskId']) ? $request->get['taskId']: ''; 
		if($taskId !== ''){
		//返回任务状态
		$status = $redis->get($taskId);
		return $response->end("task: $taskId;status: $status");
		}   
		$params = json_encode(array(111,222));//此处处理requst请求数据作为任务执行的数据，根据需要修改
		$taskId = $http->task($params);
		$response->end("

			<h1>Do task:$taskId.</h1>

			");
		});
$http->on('Finish', function($serv, $taskId, $data){
		//TDDO 任务结束之后处理任务或者回调
		echo "$taskId task finish";
		});
$http->on('task', function($serv, $taskId, $fromId, $data) use($redis){
		//任务处理，可以把处理结果和状态在redis里面实时更新，便于获取任务状态
		for($i = 0; $i < 100;$i++){
		$redis->set($taskId, $i);
		sleep(1);
		}
		return $i;//必须有return 否则不会调用onFinish
		});

$http->start();
