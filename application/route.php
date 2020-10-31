<?php
use think\Route;

// api.tp5.com ===> www.tp5.com/index.php/api
Route::domain('api','api');
// post api.tp5.com/user  --->  user.php login()
Route::post('user','user/login');
// 获取验证码
Route::get('code/:time/:token/:username/:is_exist','code/get_code');
// 用户注册
Route::post('user/register','user/register');
// 用户登录
Route::post('user/login','user/login');
// 用户上传你头像
Route::post('user/icon','user/upload_head_img');
// 用户修改密码
Route::post('user/change_pwd','user/change_pwd');
// 用户找回密码
Route::post('user/find_pwd','user/find_pwd');
// 用户绑定手机号
// Route::post('user/bind_phone','user/bind_phone');
// 用户绑定邮箱
// Route::post('user/bind_email','user/bind_email');
// 用户绑定邮箱/手机
Route::post('user/bind_username','user/bind_username');
// 用户修改昵称
Route::post('user/nickname','user/set_nickname');

/*********** article  ***********/
// 新增文章
Route::post('article','article/add_article');
// 查看文章列表
Route::get('articles/:time/:token/:user_id/[:num]/[:page]','article/article_list');
// 获取单个文章信息
Route::get('article/:time/:token/:article_id','article/article_detail');
// 修改/更新文章
Route::put('article','article/update_article');
// 删除文章
Route::delete('article/:time/:token/:article_id','article/del_article');

/*********** 正式接口  ***********/
//app登录接口
Route::post('appserver/login','appserver/login');
//获取当前报警接口（1分钟循环调用一次)
Route::get('appserver/getNowAlarm','appserver/getNowAlarm');
/*测试地址*/
//Route::get('appserver/text','appserver/text');
Route::get('appserver/getDeviceTypeInfo','appserver/getDeviceTypeInfo');
Route::get('appserver/getDeviceInfo','appserver/getDeviceInfo');

Route::get('appserver/getAlarmStatistics','appserver/getAlarmStatistics');
// 查询设备预警数据
Route::get('appserver/getDeviceAlarm', 'appserver/getDeviceAlarm');
