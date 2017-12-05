<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//require_once __DIR__ . '/vendor/autoload.php';
use \LINE\LINEBot\HTTPClient\CurlHTTPClient;
use \LINE\LINEBot;
use \LINE\LINEBot\MessageBuilder\TextMessageBuilder;

use App\remind;
use App\timetable;


class ApiController extends Controller
{
    //    
    function index(Request $request)
    {
        // LINE api 利用のためのパラメータを設定
        $access_token = getenv('CHANNEL_ACCESS_TOKEN');
        $channel_secret = getenv('CHANNEL_SECRET');
        $http_client = new CurlHTTPClient($access_token);
        $bot = new LINEBot($http_client, ['channelSecret' => $channel_secret]);

        // ユーザーから受け取った情報を取得する
        $reply_token = $request['events'][0]['replyToken'];
        $text = $request['events'][0]['message']['text'];
        $user_id = $request['events'][0]['source']['userId'];

        \Log::debug('user_id: '.$user_id);

        // 条件分岐を行う
        if(strpos($text,'を登録') !== false){
            $this -> setRemind($http_client, $bot, $reply_token, $text, $user_id);
        } else {
            $message = '「~~~を登録」で「~~~」をリマインドするよ」'.PHP_EOL.'曜日から始めるとその曜日にリマインドするよ';
            $bot->replyText($reply_token, $message);    
        }

    }

    // リマインド用のメッセージを登録する
    function setRemind($http_client, $bot, $reply_token, $text, $user_id)
    {
        $save_text = explode('を登録',$text)[0];
        \Log::debug('message_を登録:' . $save_text);
        //$remind_week = timetable::firstOrNew(['user_id' => $user_id]);
        //$arr['user_id'] = $user_id;
        $arr = Array();

        if(strpos($save_text, '月曜') !== false){
            \Log::debug('message: '.explode('月曜',$text)[1]);
            $arr['mon'] = explode('月曜', $save_text)[1];
            
        }else if(strpos($save_text,'火曜') !== false){
            $arr['tue'] = explode('火曜', $save_text)[1];
            
        }else if(strpos($save_text,'水曜') !== false){
            $arr['wed'] = explode('水曜', $save_text)[1];
            
        }else if(strpos($save_text,'木曜') !== false){
            $arr['thu'] = explode('木曜', $save_text)[1];
            
        }else if(strpos($save_text,'金曜') !== false){
            $arr['fri'] = explode('金曜', $save_text)[1];
            
        }else{
            $arr = ['user_id' => $user_id, 'message' => $save_text];
            remind::insert(
                $arr
            );
        }
        //データが有ればupdate、なければ作成
        timetable::updateOrCreate(
            ['user_id' => $user_id],
            $arr
        );

        $test = timetable::distinct()->get();
        \Log::debug($test);
        $bot->replyText($reply_token, "リマインド登録したよ");
    }

    // リマインドを実行する
    function remind()
    {   
        //サーバーはアメリカにあるのでタイムゾーンを日本にする
        date_default_timezone_set('Asia/Tokyo');
        $week_day = date('w');
        \Log::debug($week_day);
        $user_id = '';
        $message = '';
        // 月曜〜金曜だったら
        if( 5 >= $week_day && $week_day >= 1){
            \Log::debug('曜日判定できてる');
            
            // 個人利用を想定して作る。複数人対応するにはもう少し作り込みが必要
            $remind_data = timetable::first()->get();
            $user_id = $remind_data[0]['user_id'];
            
            switch($week_day){
                case 1:
                $message = $remind_data[0]['mon'];                
                \Log::debug('mon');
                break;

                case 2:
                $message = $remind_data[0]['tue'];                
                \Log::debug('tue');
                break;

                case 3:
                $message = $remind_data[0]['wed'];
                \Log::debug('wed');                
                break;

                case 4:
                $message = $remind_data[0]['thu'];
                \Log::debug('thu');
                break;

                case 5:
                $message = $remind_data[0]['fri'];
                \Log::debug('fri');                
                break;
            }

        }else{
            $remind_data = remind::take(1)->get();
            $user_id = $remind_data[0]['user_id'];
            $message = $remind_data[0]['message'];
            
            //リマインドを実行したらすべての登録された情報を消す（複数ユーザーに対応していない）
            remind::truncate();    
        }
        \Log::debug($user_id);
        \Log::debug($message);
        
        $this->push_message($user_id, $message);
        
    }


    // pushメッセージを送信する
    function push_message($user_id, $message)
    {
        $access_token = getenv('CHANNEL_ACCESS_TOKEN');
        $channel_secret = getenv('CHANNEL_SECRET');
        $http_client = new CurlHTTPClient($access_token);
        $bot = new LINEBot($http_client, ['channelSecret' => $channel_secret]);
        $url = 'https://api.line.me/v2/bot/message/push';

        $push_message = new TextMessageBuilder($message);
        $push_message->buildMessage();

        $bot->pushMessage($user_id, $push_message);
    }

}
