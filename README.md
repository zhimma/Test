##README

1. 修改Nginx配置文件

>413 Request Entity Too Large

Nginx 设置上传文件大小，打开Nginx主配置文件nginx.conf,找到http{}段并修改以下内容：
`client_max_body_size 128m;` 设置为指定的大小

2. 修改php配置文件

PHP设置上传文件大小，打开php.ini文件，修改下面参数
```
post_max_size = 256M  
upload_max_filesize = 128M 
```

3. 定时任务

```
*/1 * * * * /usr/local/bin/php /data/www/Test/public/index.php index/user/parseUserFile
*/1 * * * * /usr/local/bin/php /data/www/Test/public/index.php index/user/userDataStore
```
4. 截取文件

```
$fileName = '/data/www/Test/public/user.csv';
shell_exec('gsplit -a 3 -d -l 20000 ' . $fileName . ' '  . 'user_')
```

5. 接口访问

获取第一页数据：

`domain.com/user/1`  
> /user/1 、/user/2  传入页码
获取上传详情：

`domain.com/user/detail`

6. 运行图

![运行流程](http://somethings.oss-cn-shanghai.aliyuncs.com/logic.png)



2018.3.23
## swoole 处理异步任务
> 需要swoole扩展,redis扩展
### 运行swoole服务
`php think swoole`
### 上传文件，
文件上传成功后，具体处理如下：

```PHP
向服务器传递文件名和具体处理的类名称

Index.PHP
public function upload(Client $client)
{
        ...
    $client->sendTo(DemoTask::class,$fileName);
        ...
}
```
```PHP
server 接收任务，并调用具体处理类

Server.php
public function onReceive(\swoole_server $server, $fd, $from_id, $data)
{
    echo "worker接收任务" .PHP_EOL;
    $server->task($data);
}

public function onTask($server, $task_id, $from_id, $data)
{
    echo "Task接收任务，调用handle" .PHP_EOL;
    $task = json_decode($data);
    $class = $task->class;
    $object = new $class($server,$task->data);
    $object->handle();
}
```
```php
具体处理任务类

DemoTask.php
public function handle()
{
    echo "handle接收任务" .PHP_EOL;
    if($this->cacheUserData($this->data)){
       $this->userDataStore();
   }
}
```

