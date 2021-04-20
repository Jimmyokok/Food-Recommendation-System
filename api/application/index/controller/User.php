<?php
namespace app\index\controller;

use think\Controller;
use think\Db;
use think\Request;
use phpmailer\phpmailer;
//use phpmailer\Exception;

class User extends Controller
{
	public function loginApi()
	{
		$db = Db::connect();
		$param = input('post.');
		// +----------------------------------------------------------------------
        // | 解析收到的POST请求
        // | //没有uid：用户id
        // | username：用户名
        // | password：密码
        // +----------------------------------------------------------------------
        if(!empty($param['username'])){
            $where['username'] = $param['username'];
        }else{
        	return json([501, ['status'=>0, 'msg'=>"用户名或密码错误"]]);
        }
        if(!empty($param['password'])){
            $where['password'] = md5($param['password']);
        }else{
        	return json([501, ['status'=>0, 'msg'=>"用户名或密码错误"]]);
        }
        if(isset($where)){
            $res = $db->table('user')  
            	->newQuery()    
               	->where($where)
               	->where('is_delete',0)
                ->field('uid')
                ->select();
        }
        if(count($res) == 0){
            return json([501, ['status'=>0, 'msg'=>"用户名或密码错误"]]);
        }
        return json([200, ['status'=>1, 'uid' => $res[0]['uid']]]);
        // +----------------------------------------------------------------------
        // | 根据是否查询到匹配的用户信息将登陆状态status返回前端
        // +----------------------------------------------------------------------
	}

	public function registerApi()
	{
		$db = Db::connect();
		$param = input('post.');
		// +----------------------------------------------------------------------
        // | 解析收到的POST请求
        // | 
        // | username：用户名
        // | password：密码
        // +----------------------------------------------------------------------
        if(!empty($param['username'])){
            $where['username'] = $param['username'];
            $res = $db->table('user')   
            	->newQuery()   
               	->where($where)
                ->select();
        }else{
        	return json([501, ['status'=>0, 'msg'=>"请输入用户名"]]);
        }
        if(count($res) > 0){
            return json([501, ['status'=>0, 'msg'=>"用户名重复"]]);
        }
        if(!empty($param['password'])){
            $where['password'] = md5($param['password']);
        }else{
            return json([501, ['status'=>0, 'msg'=>"请输入密码"]]);
        } 
        	
        //判重
        if(isset($where)){
            $res = $db->table('user')
            	->newQuery()      
               	->where('username', $param['username'])
               	->where('is_delete',1)
                ->select();
        }
        if(count($res) > 0){
            $data1 = ['is_delete' => 0,'password' => md5($param['password']) ];
            $res = $db->table('user')
                ->newQuery()  
                ->where('username', $param['username'])      
                ->update($data1) ;
            $res = $db->table('user')
                ->newQuery()  
                ->where('username', $param['username'])
                ->select();
            return json([201, ['operation' => 'insert', 'uid' => $res[0]['uid']]]);
        }

        $data['username'] = $param['username'];
        $data['password'] = md5($param['password']);
        $data['userimage'] = "default.jpg";
        $data['is_delete'] = 0;
        $res = $db->table('user')
                ->newQuery()        
                ->insertGetId($data);
        return json([201, ['operation' => 'insert', 'uid' => $res]]);
	}

    public function deleteApi()
    {
        $db = Db::connect();
        $param = input('post.');
        // +----------------------------------------------------------------------
        // | 解析收到的POST请求
        // | username：用户名
        // | password：密码
        // +----------------------------------------------------------------------
        $data = ['is_delete' => '1'];
        if(!empty($param['uid'])){
            $where['uid'] = $param['uid'];
            $res = $db->table('user')   
                ->newQuery()   
                ->where($where)
                ->update($data);
        }else{
            return json([501, ['status'=>0, 'msg'=>"请输入正确的用户id"]]);
        }
    }

    public function emailSendApi()
    {
        $param = input('post.');
        if(empty($param['email_address'])){
            return json([400,['msg' => "未输入邮箱"]]) ;// 输出错误信息
        }
        $toemail = $param['email_address'];//这里写的是收件人的邮箱
        //$toemail = '1125589022@qq.com';
        $mail=new Phpmailer();
        $mail->isSMTP();// 使用SMTP服务（发送邮件的服务）
        $mail->CharSet = "utf8";// 编码格式为utf8，不设置编码的话，中文会出现乱码
        $mail->Host = "smtp.163.com";// 发送方的SMTP服务器地址
        $mail->SMTPAuth = true;// 是否使用身份验证
        $mail->Username = "ruangong_2021@163.com";// 申请了smtp服务的邮箱名（自己的邮箱名）
        $mail->Password = "DCBYIPSVZHHXKDKH";// 发送方的邮箱密码，不是登录密码,是qq的第三方授权登录码,要自己去开启（之前叫你保存的那个密码）
        $mail->SMTPSecure = "ssl";// 使用ssl协议方式,
        $mail->Port = 994;// QQ邮箱的ssl协议方式端口号是465/587
        $mail->setFrom("ruangong_2021@163.com","服务器");// 设置发件人信息，如邮件格式说明中的发件人,
        $mail->addAddress($toemail,'dear client');// 设置收件人信息，如邮件格式说明中的收件人
        $mail->addReplyTo("ruangong_2021@163.com","Reply");// 设置回复人信息，指的是收件人收到邮件后，如果要回复，回复邮件将发送到的邮箱地址
        //$mail->addCC("xxx@163.com");// 设置邮件抄送人，可以只写地址，上述的设置也可以只写地址(这个人也能收到邮件)
        //$mail->addBCC("xxx@163.com");// 设置秘密抄送人(这个人也能收到邮件)
        //$mail->addAttachment("bug0.jpg");// 添加附件
        $mail->Subject = "邮箱验证码";// 邮件标题
        $mail->Body = "您的验证码是：".$param['email_password'];//;// 邮件正文
        //$mail->AltBody = "This is the plain text纯文本";// 这个是设置纯文本方式显示的正文内容，如果不支持Html方式，就会用到这个，基本无用**
        
        if(!$mail->send()){// 发送邮件
            $errmsg = "Mailer Error: ".$mail->ErrorInfo;
            return json([400,['msg' => "Message could not be sent.", 'error' => $errmsg]]) ;// 输出错误信息
        }else{
            return json([200, ['status'=>0, 'msg'=>"邮件发送成功"]]);
        }
    }
    
}


