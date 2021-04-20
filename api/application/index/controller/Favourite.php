<?php
namespace app\index\controller;

use think\Db;
use think\Request;

class Favourite extends Error
{   
    public function listApi()
    {
        $db = Db::connect();
        $param = input('post.');
        //$page = $param['page'] ?? 1;
        if(!empty($param['uid'])){
            $where['uid'] = $param['uid'];
        }
        if(!empty($param['fid'])){
            $where['fid'] = $param['fid'];
        }

        if(!isset($where)){
            $res = $db->table('favourite')
                ->newQuery()
                ->field('fid,uid')
                ->order('uid')
                ->select();
        }else{
            $res = $db->table('favourite')
                ->newQuery()
                ->where($where)
                ->field('fid,uid')
                ->order('uid')
                ->select();
        }
        if(count($res) == 0){
            return json([200, ['result_count'=>0, 'data'=>[]]]);
        }
        return json([200, ['result_count'=>count($res), 'data'=>$res]]);
    }

    public function insertApi()
    {
        $db = Db::connect();
        $param = input('post.');
        //$page = $param['page'] ?? 1;
        if(empty($param['uid']) || empty($param['fid'])){
            return json([400, ['msg' => 'Lack data!']]);
        }
        $data['fid'] = $param['fid'];
        $data['uid'] = $param['uid'];
        $result = $db->table('user')
            ->newQuery()
            ->where('uid', $data['uid'])
            ->select();
        if(count($result) == 0){
            return json([400, ['msg' => 'No such user!']]);
        }
        $result1 = $db->table('item')
            ->newQuery()
            ->where('fid', $data['fid'])
            ->select();
        if(count($result) == 0){
            return json([400, ['msg' => 'No such item!']]);
        }
        $result = $db->table('favourite')
            ->newQuery()
            ->where($data)
            ->select();
        if(count($result) != 0){
            return json([400, ['msg' => 'Already exist!']]);
        }
        $res = $db->table('favourite')
                ->insertGetId($data);
        return json([201, ['operation' => 'insert']]);
    }

    public function deleteApi()
    {
        $db = Db::connect();
        $param = input('post.');
        if(empty($param['uid']) || empty($param['fid'])){
            return json([400, ['msg' => 'Lack data!']]);
        }
        $data['fid'] = $param['fid'];
        $data['uid'] = $param['uid'];
        $result = $db->table('favourite')
            ->newQuery()
            ->where($data)
            ->select();
        if(count($result) == 0){
            return json([400, ['msg' => 'Nothing to delete!']]);
        }
        $db->table('favourite')
                ->newQuery()
                ->where($data)
                ->delete();
        return json([200, ['operation' => 'delete']]);
    }

    
}
