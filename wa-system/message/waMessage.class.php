<?php
// VADIM CODE FILE

class waMessage {
    public static function setMessage($text, $type = '', $url = '') {
        if($type=='')$type = 'message';
        if($url=='')$url = $_SERVER['REQUEST_URI'];
        $session = wa()->getStorage();
        $session->write("_msg:$type:$url", array('type'=>$type, 'text'=>$text));
    }

    public static function getMessage($type = '') {
        if($type=='')$type = 'message';
        $url = $_SERVER['REQUEST_URI'];
        $message =  wa()->getStorage()->get("_msg:$type:$url");
        wa()->getStorage()->del("_msg:$type:$url");
        return $message['text'];
    }
} 