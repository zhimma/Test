<?php

namespace app\index\controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Predis\Client;

class User extends Base
{
    /**
     *
     *
     * @author mma5694@gmail.com
     * @date 2018年3月9日18:57:20
     */
    public function index()
    {
        if (self::$redis->llen('filePath') != 0) {
            $fileNames = self::$redis->lrange('filePath', 0, -1);
            foreach ($fileNames as $key => $value) {
               $this->handleUserFile($value);
            }
        }
    }

    public function upload()
    {
        echo "user upload";
    }

    public function handleUserFile($fileName)
    {

    }
}
