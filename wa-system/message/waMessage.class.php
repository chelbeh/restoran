<?php
// VADIM CODE FILE

class waMessage {
    public static function setMessage($text, $type = '', $url = '') {
        if($type=='')$type = 'message';
        if($url=='')$url = $_SERVER['REQUEST_URI'];
        $session = wa()->getStorage();
        $session->write("_msg:$url", array('type'=>$type, 'text'=>$text));
    }

    public static function getMessage($type = '') {
        if($type=='')$type = 'message';
        $url = $_SERVER['REQUEST_URI'];
        $message =  wa()->getStorage()->get("_msg:$url");
        wa()->getStorage()->del("_msg:$url");
        if($message['type']==$type){
            return $message['text'];
        }
        return '';
    }
} 