<?php
namespace app\index\controller;

use think\Db;
use think\Request;

class Item extends Error
{   
    // +----------------------------------------------------------------------
    // | 响应物品查询请求，可返回多个物品
    // +----------------------------------------------------------------------
    public function listApi()
    {
        // +----------------------------------------------------------------------
        // | 连接数据库并获取POST请求
        // +----------------------------------------------------------------------
        $db = Db::connect();
        $param = input('post.');
        // +----------------------------------------------------------------------
        // | 解析收到的POST请求
        // | uid：上传该物品的用户id
        // | fid：物品id
        // | fname：物品名称，支持模糊查询
        // | fdesc：物品描述，支持模糊查询
        // +----------------------------------------------------------------------
        if(!empty($param['uid'])){
            $where['uid'] = $param['uid'];
        }
        if(!empty($param['fid'])){
            $where['fid'] = $param['fid'];
        }
        if(!empty($param['fname'])){
            $fname = $param['fname'];
        }else{
            $fname = "";
        }
        if(!empty($param['fdesc'])){
            $fdesc = $param['fdesc'];
        }else{
            $fdesc = "";
        }
        // +----------------------------------------------------------------------
        // | 根据请求情况进行SQL查询
        // +----------------------------------------------------------------------
        if(!isset($where)){
            $res = $db->table('item')
                ->newQuery()        
                ->where('fname','like','%'.$fname.'%')
                ->where('fdesc','like','%'.$fdesc.'%')
                ->field('fid,fname,fdesc,fimage,uid')
                ->order('fid')
                ->select();
        }else{
            $res = $db->table('item')
                ->newQuery()        
                ->where($where)
                ->where('fname','like','%'.$fname.'%')
                ->where('fdesc','like',"%".$fdesc."%")
                ->field('fid,fname,fdesc,fimage,uid')
                ->order('fid')
                ->select();
        }
        // +----------------------------------------------------------------------
        // | 根据查询结果的不同将结果返回前端
        // +----------------------------------------------------------------------
        if(count($res) == 0){
            return json([200, ['result_count'=>0, 'data'=>[]], $param]);
        }
        return json([200, ['result_count'=>count($res), 'data'=>$res], $param]);
    }
    // +----------------------------------------------------------------------  
    // | 插入或更新物品数据
    // +----------------------------------------------------------------------
    public function insertApi()
    {
        // +----------------------------------------------------------------------
        // | 连接数据库并获取POST请求
        // +----------------------------------------------------------------------
        $db = Db::connect();
        $param = input('post.');
        // +----------------------------------------------------------------------
        // | 解析并验证POST请求数据的合法性
        // | 插入或更新的物品必须提供上传用户id和物品名称
        // | 用户id必须存在；物品描述可以为空，自动填入默认描述
        // +----------------------------------------------------------------------
        if(empty($param['uid']) || empty($param['fname'])){
            return json([400, ['msg' => 'Lack data!']]);
        }
        if(empty($param['fdesc'])){
            $data['fdesc'] = 'No description';
        }else{
            $data['fdesc'] = $param['fdesc'];
        }
        $data['fname'] = $param['fname'];
        $data['uid'] = $param['uid'];
        $result = $db->table('user')
            ->newQuery()
            ->where('uid', $param['uid'])
            ->select();
        if(count($result) == 0){
            return json([400, ['msg' => 'No such user!']]);
        }
        // +----------------------------------------------------------------------
        // | 获取上传的文件，如果没有则认为该物品使用默认图片
        // | 读取图片后转存为以时间字符串为名称的新文件，路径预先指定
        // | 转存后图片的文件名作为物品属性的一部分存入数据库
        // +----------------------------------------------------------------------
        if(!empty($param['base64'])){
            date_default_timezone_set("PRC");
            $time=date("YmdHis", time());
            $path = "C://PJ/api/public/img/$time.jpg";
            file_put_contents($path, base64_decode($param['base64']));
            $data['fimage'] = "$time.jpg";
        }else{
            $data['fimage'] = "default.jpg";
        }
        // +----------------------------------------------------------------------
        // | 查看POST请求是否提供了物品id
        // | 如果提供了则进行更新操作，否则进行插入操作并返回插入后产生的物品id
        // +----------------------------------------------------------------------
        if(!empty($param['fid'])){
            $db->table('item')
                ->newQuery()        
                ->where('fid', $param['fid'])
                ->update($data);
            return json([201, ['operation' => 'update']]);
        }else{
            $res = $db->table('item')
                ->newQuery()        
                ->insertGetId($data);
            return json([201, ['operation' => 'insert', 'fid' => $res]]);
        }
    }
    // +----------------------------------------------------------------------
    // | 删除物品
    // +----------------------------------------------------------------------
    public function deleteApi()
    {
        // +----------------------------------------------------------------------
        // | 连接数据库并获取POST请求
        // +----------------------------------------------------------------------
        $db = Db::connect();
        $param = input('post.');
        // +----------------------------------------------------------------------
        // | 解析并验证POST请求数据的合法性
        // | 将要删除的物品必须提供物品id
        // | 该物品必须存在
        // +----------------------------------------------------------------------
        if(empty($param['fid'])){
            return json([400, ['msg' => 'Lack data!']]);
        }
        $result = $db->table('item')
            ->newQuery()        
            ->where('fid', $param['fid'])
            ->select();
        if(count($result) == 0){
            return json([400, ['msg' => 'Nothing to delete!']]);
        }
        // +----------------------------------------------------------------------
        // | 从物品表中删除该物品
        // | 同时从收藏记录表中删除所有对该物品的收藏记录
        // +----------------------------------------------------------------------
        $db->table('item')
                ->newQuery()        
                ->where('fid', $param['fid'])
                ->delete();
        $db->table('favourite')
                ->newQuery()        
                ->where('fid', $param['fid'])
                ->delete();
        return json([200, ['operation' => 'delete']]);
    }
    // +----------------------------------------------------------------------
    // | 根据请求的图片文件名返回图片内容
    // +----------------------------------------------------------------------
    public function imageApi()
    {
        // +----------------------------------------------------------------------
        // | 获取POST请求
        // +----------------------------------------------------------------------
        $param = input('post.');
        // +----------------------------------------------------------------------
        // | 解析并验证POST请求数据的合法性
        // | 必须提供图片文件名
        // +----------------------------------------------------------------------
        if(empty($param['fimage'])){
            return json([400, ['msg' => 'Lack data!']]);
        }
        $filename = $param['fimage'];
        // +----------------------------------------------------------------------
        // | 读取文件并转为BASE64格式字符串，包装为HTML元素后返回前端
        // | 文件不存在则返回错误
        // +----------------------------------------------------------------------
        $file = "C://PJ/api/public/img/$filename";
        if(file_exists($file)){
            $fp = fopen($file,"rb", 0);
            $content = fread($fp,filesize($file));
            fclose($fp);
            $base64 = chunk_split(base64_encode($content));
            $encode = '<img src="data:image/jpg/png/gif;base64,' . $base64 .'" >';
            return json([200, ['data'=>['html' => $encode]]]);
        }else{
            return json([400, ['msg' => 'Image doesn\'t exist!']]);
        }
    }
}
