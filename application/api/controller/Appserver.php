<?php

namespace app\api\controller;

use think\Db;
use think\Request;

class Appserver extends Common
{
    //登录
    public function login()
    {
        $data = $this->params = $this->check_params($this->request->param(true));
        $isusername = db::table('users')->where('username', '=', $data['account'])->find(); //查找是否有当前用户
        if ($isusername) {
            $linData = db::table('users')
                ->where('username', '=', $data['account'])
                ->where('password', '=', sha1($data['pwd']))
                ->find();
            if ($linData) {
                $options = [
                    // 缓存类型为File
                    'type' => 'File',
                    // 缓存有效期为永久有效
                    'expire' => 0,
                    // 指定缓存目录
                    'path' => APP_PATH . 'runtime/cache/',
                ];
//               token 生成
                $token = $this->token($linData);
                cache($options);
                cache($token, $linData);
//               设置返回数据
                $datainfo = [];
                $datainfo['token'] = $token;
                $datainfo['uid'] = $linData['userid'];
                $datainfo['account'] = $linData['username'];
                $datainfo['unitId'] = $linData['userid'];
                $datainfo['unitName'] = $linData['nickname'];
                $this->return_msg(0, '登录成功', $datainfo);
            } else {
                $this->return_msg(1, '密码错误', '');
            }
        } else {
            $this->return_msg(1, '用户不存在', '');
        }


    }

    //获取当前报警
    public function getNowAlarm()
    {
//        验证当前token 值
        if ($this->istoken(Request::instance()->header('Authorization'))) {
            $data = $this->params = $this->check_params($this->request->param(true));
            $valueusername = $this->uid($data['unitId']);
            $devicedata = db::name('devices d')
                ->where('d.username', '=', $valueusername)
                ->where('d.status', 'in', [4, 5, 6, 16, 17, 18])
                ->join('device_type t', 'd.dtype = t.tid')
                ->join('device_address a', 'd.id = a.deviceid')
                ->field(['t.tname as deviceType,a.deviceAddress as deviceAddress,d.id as deviceId,d.nd as alarmValue', 'FROM_UNIXTIME(create_time) as alarmTime'])
                ->select();
            if ($devicedata) {
                $arr = [
                    'unitId' => $data['unitId'],
                ];
                $this->return_msg(0, '获取数据成功', $this->arrpull($devicedata, $arr));
            } else {
                $this->return_msg(0, '暂无数据', '');
            }
        } else {
            $this->return_msg(1, 'token值错误', '');
        }

    }

//    获取设备数据信息
    public function getDeviceTypeInfo()
    {
        /*验证token值是否正确*/
        if ($this->istoken(Request::instance()->header('Authorization'))) {
            $data = $this->params = $this->check_params($this->request->param(true));
            $valueusername = $this->uid($data['unitId']);
            $deviceinfo = db::name('devices d')
                ->where('d.username', '=', $valueusername)
                ->where('d.status', 'in', [1, 9, 4, 5, 6, 16, 17, 19])
                ->join('device_type t', 'd.dtype = t.tid', 'right')
                ->field(["t.tid as deviceTypeId,t.tname as deviceType,d.devicenum,d.status,d.dtype",
                    "CASE WHEN d.dtype='1' THEN 'gas' 
                    WHEN d.dtype='5' THEN 'pressure' 
                    WHEN d.dtype='9' THEN 'liquid' 
                    WHEN d.dtype='10' THEN 'temperature' END" => 'typeCode',
                    "count(d.dtype) as deviceTotal",
                ])
                ->group("d.dtype")
                ->select();
            $devicesattus = db::name('devices d')
                ->where('d.username', '=', $valueusername)
                ->where('d.status', 'in', [1, 9, 4, 5, 6, 16, 17, 19])
                ->join('device_type t', 'd.dtype = t.tid', 'right')
                ->field(["t.tid as deviceTypeId,d.status,d.dtype",
                    "CASE WHEN d.dtype='1' THEN 'gas' 
                    WHEN d.dtype='5' THEN 'pressure' 
                    WHEN d.dtype='9' THEN 'liquid' 
                    WHEN d.dtype='10' THEN 'temperature' END" => 'typeCode',
                    "count(d.dtype) as deviceTotal",
                ])
                ->group("d.dtype,d.status")
                ->select();
            $this->return_msg(0, '获取数据成功', ['deviceTypeList' => $this->mysqlOnOffdevice($deviceinfo, $devicesattus, $data['unitId'])]);
        } else {
            $this->return_msg(1, 'token值错误', '');
        }
    }

//获取某一类型下所有设备的当前数据及24小时内的数据
    public function getDeviceInfo()
    {
        /*验证token值是否正确*/
        if ($this->istoken(Request::instance()->header('Authorization'))) {
            $data = $this->params = $this->check_params($this->request->param(true));
            $uid = $this->uid($data['unitId']);
            $deviceAddressdata = input('deviceAddress');
            if ($deviceAddressdata == '') { //返回全部
                $deviinfo = db::name('devices d')
                    ->where('d.username', '=', $uid)
                    ->where('d.dtype', '=', $data['deviceTypeId'])
                    ->join('status s', 'd.status = s.id', 'right')
                    ->join('device_address a', 'd.id = a.deviceid', 'right')
                    ->join('device_type t', 'd.dtype = t.tid', 'right')
                    ->field(['d.nd as nowValue, s.status_name as deviceStatus,d.id as deviceId,a.deviceAddress as deviceAddress,d.devicenum', "CASE WHEN d.dtype='1' THEN 'gas' 
                    WHEN d.dtype='5' THEN 'pressure' 
                    WHEN d.dtype='9' THEN 'liquid' 
                    WHEN d.dtype='10' THEN 'temperature' END" => 'typeCode'])
                    ->select();
            } else {
                $deviinfo = db::name('devices d')
                    ->join('device_type t', 'd.dtype = t.tid', 'right')
                    ->join('status s', 'd.status = s.id', 'right')
                    ->join('device_address a', 'd.id = a.deviceid', 'right')
                    ->where('d.username', '=', $uid)
                    ->where('d.dtype', '=', $data['deviceTypeId'])
                    ->where('deviceAddress', 'like', '%' . $deviceAddressdata . '%')
                    ->field(['d.nd as nowValue, s.status_name as deviceStatus,d.id as deviceId,a.deviceAddress as deviceAddress,d.devicenum', "CASE WHEN d.dtype='1' THEN 'gas' 
                    WHEN d.dtype='5' THEN 'pressure' 
                    WHEN d.dtype='9' THEN 'liquid' 
                    WHEN d.dtype='10' THEN 'temperature' END" => 'typeCode'])
                    ->select();
            }
            $arr = [
                'selectTime' => date("Y-m-d H:i:s", time()),
                'unitId' => $data['unitId'],
                '24hValue' => '',
            ];
            $deviinfo = $this->arrpull($deviinfo, $arr);
            foreach ($deviinfo as $key => $value) {
                $deviinfo[$key]['24hValue'] = $this->hValuev($value['devicenum']);
            }
            $this->return_msg(0, '获取数据成功', ['deviceList' => $deviinfo]);
        } else {
            $this->return_msg(1, 'token值错误', '');
        }
    }

    /*设备预警数据统计*/
    public function getAlarmStatistics()
    {
        /*验证token值是否正确*/
        if ($this->istoken(Request::instance()->header('Authorization'))) {
            $data = $this->params = $this->check_params($this->request->param(true));
            $uid = $this->uid($data['unitId']);
            $deviceType = db::name($uid . '_alarm d')
                ->join('devices e', 'd.dnum = e.devicenum')
                ->join('device_type t', 'e.dtype = t.tid')
                ->where('e.username', '=', $uid)
                ->field('count(e.id) as alarmNum,t.tname as deviceType')
                ->group('e.dtype')
                ->select();

            $devices = db::name($uid . '_alarm')
                ->alias('a')
                ->join('devices d', 'a.dnum = d.devicenum')
                ->join('device_address s', 'd.id = s.deviceid')
                ->group('d.devicenum')
                ->field('count(a.alarmid) as alarmNum, s.deviceAddress')
                ->select();
            $arr = [
                'selectTime' => date("Y-m-d H:i:s", time()),
                'deviceTypeAlarmStatistics' => $deviceType,
                'deviceAlarmNumList' => $devices,
            ];
            $this->return_msg(0, '获取成功', $arr);
        } else {
            $this->return_msg(1, 'token值错误', '');
        }
    }

    /**
     * 查询设备预警数据
     */
    public function getDeviceAlarm()
    {
        /*验证token值是否正确*/
        if ($this->istoken(Request::instance()->header('Authorization'))) {
            $request = $this->check_params($this->request->param(true));
            $username = $this->uid($request['unitId']);
            $where = array();
            if (isset($request['deviceId'])) {
                $where['d.id'] = ['=', $request['deviceId']];
            }
            $deviceAlarmList = Db::table("{$username}_alarm")->alias('a')
                ->field(["'{$request['unitId']}' as unitId, d.id AS deviceId, d.dtype AS deviceTypeId, dd.deviceAddress,
                    FROM_UNIXTIME(a.alarmstart) AS alarmTime, a.alarmnd AS alarmValue",
                    "CASE WHEN a.hasread = '1' THEN '已消警' WHEN a.hasread = '0' THEN '未消警' END" => 'alarmStatus',
                    "CASE WHEN d.dtype='1' THEN 'gas'
                    WHEN d.dtype='5' THEN 'pressure'
                    WHEN d.dtype='9' THEN 'liquid'
                    WHEN d.dtype='10' THEN 'temperature' END" => 'typeCode'])
                ->join('devices d', "d.devicenum=a.dnum", 'right')
                ->join("device_address dd", "dd.deviceid=d.id", 'left')
                ->where($where)
                ->limit(0, 5000)
                ->order('a.alarmid', 'DESC')
                ->select();
            $this->return_msg(0, '操作成功', compact('deviceAlarmList'));
        } else {
            $this->return_msg(1, 'token错误', '');
        }
    }

    //返回用户名称

    public function uid($uid)
    {
        $valueusername = db::name('users')
            ->where('userid', '=', $uid)
            ->value('username');
        if (!$valueusername) {
            $this->return_msg(1, 'unitId不存在', '');
        }
        return $valueusername;
    }
//    数组拼接
    /*$array 原数组 $newarray 新数组*/
    public function arrpull($array, $newarray)
    {
        $arraywalk = $array;
        array_walk($arraywalk, function (&$v, $k, $p) {
            $v = array_merge($v, $p);
        }, $newarray);
        return $arraywalk;
    }

    //获取24小时数据
    public function hValuev($device_num)
    {
        $host = '192.168.1.230';
//        $host = '10.0.119.100';
        $port = '8086';
        $username = 'influx';
        $password = 'zehong8893@influx';
        $client = new \InfluxDB\Client($host, $port, $username, $password);
        $database = $client->selectDB('zehongcloud');
        $time = date("Y-m-d H:i:s", time() - 23 * 3600);
        $result = $database
            ->query("SELECT LAST(\"value\") AS recordValue,time AS recordTime FROM \"devices\" WHERE \"device_num\" = '{$device_num}' AND time > '{$time}' GROUP BY time(1h) tz('Asia/Shanghai')")
            ->getPoints();
        return $result;
    }

    public function utcTime($uTime)
    {
        $time = str_replace('T', ' ', $uTime);
        $time = str_replace('Z', '', $time);
        return date('Y-m-d H:i:s', strtotime($time) + (8 * 3600));
    }

    /*$deviceinfo
     *  $devicesattus
     * */
    public function mysqlOnOffdevice($deviceinfo, $devicesattus, $unitId)
    {
        $alarmStatus = [4, 5, 6, 16, 17, 19];
        foreach ($deviceinfo as $k => $v) {
            $onlineNum = 0;
            $offlineNum = 0;
            $alarmNum = 0;
            foreach ($devicesattus as $kk => $vv) {
                if ($v['dtype'] == $vv['dtype']) {
                    if ($vv['status'] == 1) {
                        $onlineNum += $vv['deviceTotal'];
                    } elseif ($vv['status'] == 9) {
                        $offlineNum += $vv['deviceTotal'];
                    } elseif (in_array($vv['status'], $alarmStatus)) {
                        $alarmNum += $vv['deviceTotal'];
                    }
                }
            }
            $datas1[$k]['deviceTypeId'] = $v['deviceTypeId'];
            $datas1[$k]['deviceType'] = $v['deviceType'];
            $datas1[$k]['typeCode'] = $v['typeCode'];
            $datas1[$k]['deviceTotal'] = $v['deviceTotal'];
            $datas1[$k]['onlineNum'] = $onlineNum;
            $datas1[$k]['offlineNum'] = $offlineNum;
            $datas1[$k]['alarmNum'] = $alarmNum;
            $datas1[$k]['selectTime'] = date("Y-m-d H:i:s", time());
            $datas1[$k]['unitId'] = $unitId;

        }
        return $datas1;
    }
}