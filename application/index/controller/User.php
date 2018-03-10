<?php

namespace app\index\controller;


class User extends Base
{
    /**
     * 监测¬文件上传队列
     *
     * @author mma5694@gmail.com
     * @date   2018年3月9日18:57:20
     */
    public function index()
    {
        if (self::$redis->llen('filePath') != 0) {
            echo "parse filePath";
            $fileNames = self::$redis->lrange('filePath', 0, -1);
            foreach ($fileNames as $key => $value) {
                $this->handleUserFile($value);
            }
            echo "parse filePath end";
        }
    }

    /**
     * 新文件上传后  数据写入redis 队列
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月10日15:55:31
     */
    public function handleUserFile($fileName = '')
    {
        $excelData = file($fileName);
        $chunkData = array_chunk($excelData, 5000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        for ($i = 0; $i < $count; $i++) {
            foreach ($chunkData[$i] as $value) {
                $encode = mb_detect_encoding($value, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
                $string = mb_convert_encoding(trim(strip_tags($value)), 'UTF-8', $encode);
                self::$redis->lpush('userInfo', json_encode(explode(',', $string)));
            }
        }
    }

    /**
     * 新文件上传后  数据写入redis 队列
     *
     * @author 马雄飞 <mma5694@gmail.com>
     * @date   2018年03月10日15:55:31
     */
    public function handleUserFileBak($fileName = '/data/www/Test/public/uploads/20180309/gsu_users.csv')
    {
        $fileNameArray = explode('/', $fileName);
        $prefixName = explode('.', array_pop($fileNameArray));
        $filePath = implode('/', $fileNameArray);
        shell_exec('cd ' . $filePath);
        shell_exec('gsplit -a 3 -d -l 200000 ' . $fileName . ' ' . $prefixName[0] . '_');
        $fileLength = shell_exec('ls -l | grep " ' . $prefixName[0] . '_*"|wc -l');
        for ($i = 0; $i < $fileLength; $i++) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die("could not fork");
            } elseif ($pid) {
            } else {// 子进程处理
                $excelData = file($filePath . '/' . $prefixName[0] . '_00' . $i . '.' . $prefixName[1]);
                $chunkData = array_chunk($excelData, 5000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
                $count = count($chunkData);
                for ($i = 0; $i < $count; $i++) {
                    foreach ($chunkData[$i] as $value) {
                        $encode = mb_detect_encoding($value, ["ASCII", 'UTF-8', "GB2312", "GBK", 'BIG5']);
                        $string = mb_convert_encoding(trim(strip_tags($value)), 'UTF-8', $encode);
                        self::$redis->lpush('userInfo', json_encode(explode(',', $string)));
                    }
                }
            }
        }
        while (pcntl_waitpid(0, $status) != -1) {
            $status = pcntl_wexitstatus($status);
        }
        exit;
    }

    /**
     * 缓存数据入库
     *
     * @author 马雄飞 <xiongfei.ma@pactera.com>
     * @date   2018年03月10日15:57:28
     */
    public function handleUserStore()
    {
        if (self::$redis->llen('userInfo') != 0) {
            echo "parse userInfo";
            $userData = self::$redis->lrange('userInfo', 0, -1);
            $chunkData = array_chunk($userData, 5000);
            $count = count($chunkData);
            for ($i = 0; $i < $count; $i++) {
                $insertData = [];
                foreach ($chunkData[$i] as $value) {
                    if (!empty($value)) {
                        $user = $this->validateAndSave(json_decode($value, true));
                        if (!empty($user)) {
                            array_push($insertData, $user);
                        }
                    }
                }
                //保存数据库
            }
            echo "parse userInfo end";

        }
    }

    public function validateAndSave($userData)
    {
        //手机号已存在
        if (!(self::$redis->sadd('userPhone', $userData[2])) || empty($userData[2]) || !isset($userData[2])) {
            //存入hash表 值存在 将覆盖
            self::$redis->hset('hasSomethingWrongUser', $userData[1], json_encode($userData));

            return [];
        } else {
            return [
                'name'        => $userData[1],
                'phone'       => $userData[2],
                'sex'         => $userData[3],
                'create_time' => $userData[4],
                'update_time' => $userData[5]
            ];
        }
    }
}
