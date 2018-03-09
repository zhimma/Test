<?php
namespace app\index\controller;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Predis\Client;

class User
{
    public function index()
    {
        echo url('index/index/upload');
       echo "user index";
    }

    public function upload()
    {
        echo "user upload";
    }
}
