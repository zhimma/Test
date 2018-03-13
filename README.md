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