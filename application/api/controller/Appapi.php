<?php

namespace app\api\controller;

use think\Db;
use think\Request;

class Appapi extends Common
{
    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        if ($this->istoken(Request::instance()->header('Authorization'))) {
            $this->return_msg(1, 'token值错误', '');
        }
    }


    //返回用户名称
    public function  getUsername($uid) {
        $valueusername = db::name('users')
            ->where('userid', '=', $uid)
            ->value('username');
        if(!$valueusername){
            $this->return_msg(1, 'unitId不存在','');
        }
        return $valueusername;
    }
}
