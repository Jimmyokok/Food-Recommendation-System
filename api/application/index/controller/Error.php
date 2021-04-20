<?php
namespace app\index\controller;

use think\Request;
use think\Controller;

class Error extends Controller
{
    public function index(Request $request)
    {
        return [404, 'not found '.$request->controller().' controller'];
    }
    public function _empty($func)
    {
        return [400, 'no such service as '.$func];
    }
}