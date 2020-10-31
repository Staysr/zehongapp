<?php
namespace app\api\controller;
use phpmailer\phpmailer;
use submail\messagexsend;

class Code extends Common {
    public function get_code() {
        $username      = $this->params['username'];
        $exist         = $this->params['is_exist'];
        $username_type = $this->check_username($username);
        switch ($username_type) {
        case 'phone':
            $this->get_code_by_username($username, 'phone', $exist);
            break;
        case 'email':
            $this->get_code_by_username($username, 'email', $exist);
            break;
        }
    }
    /**
     * 通过手机/邮箱获取验证码
     * @param  [string] $phone [手机号/邮箱]
     * @param  [int] $exist [手机号/邮箱是否应该存在于数据库中 1:是 0:否]
     * @return [json]        [api返回的json数据]
     */
    public function get_code_by_username($username, $type, $exist) {
        if ($type == 'phone') {
            $type_name = '手机';
        } else {
            $type_name = '邮箱';
        }
        /*********** 检测手机号/邮箱是否存在  ***********/
        $this->check_exist($username, $type, $exist);
        /*********** 检查验证码请求频率 30秒一次  ***********/
        if (session("?" . $username . '_last_send_time')) {
            if (time() - session($username . '_last_send_time') < 30) {
                $this->return_msg(400, $type_name . '验证码,每30秒只能发送一次!');
            }
        }
        /*********** 生成验证码  ***********/
        $code = $this->make_code(6);
        /*********** 使用session存储验证码, 方便比对, md5加密   ***********/
        session($username . '_code', $code);
        /*********** 使用session存储验证码的发送时间  ***********/
        session($username . '_last_send_time', time());
        /*********** 发送验证码  ***********/
        if ($type == 'phone') {
            $this->send_code_to_phone($username, $code);
        } else {
            $this->send_code_to_email($username, $code);
        }
    }
    /**
     * 生成验证码
     * @param  [int] $num [验证码的位数]
     * @return [int]      [生成的验证码]
     */
    public function make_code($num) {
        $max = pow(10, $num) - 1;
        $min = pow(10, $num - 1);
        return rand($min, $max);
    }
    /**
     * 向手机号发送验证码(使用接口)
     * @param  [string] $phone [手机号]
     * @param  [int] $code  [生成的验证码]
     * @return [json]        [返回的json信息]
     */
    // public function send_code_to_phone($phone, $code) {
    //     $curl = curl_init();
    //     curl_setopt($curl, CURLOPT_URL, 'https://api.mysubmail.com/message/xsend');
    //     curl_setopt($curl, CURLOPT_HEADER, 0);
    //     curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    //     curl_setopt($curl, CURLOPT_POST, 1);
    //     $data = [
    //         'appid'   => '15180',
    //         'to'      => $phone,
    //         'project' => '9CTTG2',
    //         'vars'    => '{"code":' . $code . ',"time":"60"}',
    //         'signature'=>'76a9e82484c83345b7850395ceb818fb',
    //     ];
    //     curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    //     $res = curl_exec($curl);
    //     curl_close($curl);
    //     $res = json_decode($res);
    //     if ($res->status != 'success') {
    //         $this->return_msg(400,$res->msg);
    //     }else{
    //         $this->return_msg(200,'手机验证码已发送, 每天发送5次, 请在一分钟内验证!');
    //     }
    //     dump($res->staus);die;
    // }
    /**
     * 向手机号发送验证码(使用SDK)
     * @param  [string] $phone [手机号]
     * @param  [int] $code  [生成的验证码]
     * @return [json]        [返回的json信息]
     */
    public function send_code_to_phone($phone, $code) {
        $submail = new MESSAGEXsend();
        $submail->SetTo($phone);
        $submail->SetProject('9CTTG2');
        $submail->AddVar('code', $code);
        $submail->AddVar('time', 60);
        $xsend = $submail->xsend();
        if ($xsend['status'] !== 'success') {
            $this->return_msg(400, $xsend['msg']);
        } else {
            $this->return_msg(200, '手机验证码已发送, 每天发送5次, 请在一分钟内验证!');
        }
    }
    /**
     * 向邮箱发送验证码
     * @param  [string] $email [目标email]
     * @param  [int] $code  [验证码]
     * @return [json]        [返回的json数据]
     */
    public function send_code_to_email($email, $code) {
        $toemail = $email;
        $mail    = new PHPMailer();
        $mail->isSMTP();
        $mail->CharSet    = 'utf8';
        $mail->Host       = 'smtp.126.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = "xujunhao_api@126.com";
        $mail->Password   = "xujunhao890518";
        $mail->SMTPSecure = 'ssl';
        $mail->Port       = 994;
        $mail->setFrom('xujunhao_api@126.com', '接口测试');
        $mail->addAddress($toemail, 'test');
        $mail->addReplyTo('xujunhao_api@126.com', 'Reply');
        $mail->Subject = "您有新的验证码!";
        $mail->Body    = "这是一个测试邮件,您的验证码是$code,验证码的有效期为1分钟,本邮件请勿回复!";
        if (!$mail->send()) {
            $this->return_msg(400, $mail->ErrorInfo);
        } else {
            $this->return_msg(200, '验证码已经发送成功,请注意查收!');
        }
    }
}