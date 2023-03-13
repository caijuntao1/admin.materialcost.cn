<?php


namespace App\Http\Controllers\Wechat;


use Illuminate\Http\Request;
use EasyWeChat\Factory;

class WechatReplyController
{
    protected $app;
    public function __construct()
    {
        $config = [
            'app_id' => getenv('WECHAT_OFFICIAL_ACCOUNT_APPID'),
            'secret' => getenv('WECHAT_OFFICIAL_ACCOUNT_SECRET'),

            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',

            //...
        ];
        $this->app = Factory::officialAccount($config);
    }

    public function reply(Request $request){
        $this->app->server->push(function ($message) {
            switch ($message['MsgType']) {
                case 'event':
                    return '收到事件消息';
                    break;
                case 'text':
                    return '收到文字消息';
                    break;
                case 'image':
                    return '收到图片消息';
                    break;
                case 'voice':
                    return '收到语音消息';
                    break;
                case 'video':
                    return '收到视频消息';
                    break;
                case 'location':
                    return '收到坐标消息';
                    break;
                case 'link':
                    return '收到链接消息';
                    break;
                case 'file':
                    return '收到文件消息';
                // ... 其它消息
                default:
                    return '收到其它消息';
                    break;
            }
        });
    }
}
