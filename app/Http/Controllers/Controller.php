<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    public function curl_post($param, $url, $authorization = false) {
        $postdata = json_encode($param);

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        $headerArray = array('Content-Type: application/json');
        if($authorization != false) {
            $headerArray = array(
                'Content-Type: application/json',
                'authorization: '.$authorization
            );
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}
