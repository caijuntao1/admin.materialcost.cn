<?php

namespace App\Service;

use Validator;
use Log;

class ValidatorService
{
    public function Validator($request, $data)
    {
        $datas = array();
        foreach ($data as $value) {
            $datas[$value] = 'required';
        }
        $validate = Validator::make($request->all(), $datas);
        if($validate->fails())
        {
             Log::error('缺少必要参数!!');
             return false;
        }
        return true;
    }
}