1. 修改nginx配置文件

打开nginx主配置文件nginx.conf，一般在`/usr/local/nginx/conf/nginx.conf`这个位置，找到http{}段并修改以下内容：
`client_max_body_size 120m;`

2. 修改php配置文件

```post_max_size = 2M  
upload_max_filesize = 2M 
```
3. 定时任

```
*/1 * * * * /usr/local/bin/php /data/www/Test/public/index.php index/user/parseUserFile
*/1 * * * * /usr/local/bin/php /data/www/Test/public/index.php index/user/userDataStore
```
4. 截取文件

```
$fileName = '/data/www/Test/public/user.csv';
shell_exec('gsplit -a 3 -d -l 20000 ' . $fileName . ' '  . 'user_')
```