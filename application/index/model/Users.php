<?php
/**
 * @author 马雄飞 <xiongfei.ma@pactera.com>
 * @date   2018/3/11 下午9:34
 */

namespace app\index\model;

use think\Model;

/**
 *
 *
 * @package app\index\model
 */
class Users extends Model
{
    public function getUser()
    {
        return $this::paginate(10);
    }
}