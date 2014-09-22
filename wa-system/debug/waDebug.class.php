<?php

class waDebug {

    static $time_points = array();
    static $timer = 0;

    static function enabled(){
        if(isset($_COOKIE['show_debug'])){
            if($_COOKIE['show_debug']>=1)
                return true;
        }
        return false;
    }

    static function addTimePoint($string = ''){
        if(!self::enabled()) return false;
        $curtime = microtime(1);
        if(self::$timer==0){
            self::$timer = $curtime;
        }
        $trace = debug_backtrace();;
        $file = $trace[1]['file'];
        $root_dir = $_SERVER['DOCUMENT_ROOT'];
        $file = str_replace($root_dir, '', $file);
        self::$time_points[] = array(
            'file'=>$file,
            'line'=>$trace[1]['line'],
            'time_diff' => $curtime - self::$timer,
            'time_total' => self::$timer,
            'comment' => $string,
        );
        self::$timer = microtime(1);
    }

    static function showTimePoints(){
        if(!self::enabled()) return false;
        $str = "";
        foreach(self::$time_points as $point){
            $str .= '<div style="margin-top: 5px">';
            $str .= (round($point['time_diff']*1000)/1000)."\n";
            $str .= '<b>'.$point['comment']."</b> ";
            $str .= '<span style="font-size: 11px">'.$point['file'].':'.$point['line']."</span><br>";
            //$str .= $point['time_total']."\n";
            $str .= '</div>';
        }
        return $str;
    }

    /*
    static function query($sql, $result, $error_code, $error, $debug_backtrace){
        if(self::enabled()){
            $time_end = microtime(1);
            $time = $time_end - self::$timer;

            $tv = array();
            $tv['type'] = 'query';
            $tv['query'] = $sql;
            $tv['backtrace'] = debug_backtrace();
            $tv['time'] = $time;
            $tv['err_code'] = $error_code;
            $tv['err_str'] = $error;
            self::$objects[] = $tv;
            self::$timer = 0;
        }
    }

    static function queryStart(){
        if(self::enabled()){
            self::$timer = microtime(1);
        }
    }

    static function describe($table, $keys){
    }

    static function sql($sql){
    }

    private static function getViewBacktrace($arr){
        $backtrace = '';
        $n = 0;
        foreach($arr as $b){
            $file = $b['file'];
            $line = $b['line'];
            if($n>0){
                $backtrace .= "$file ($line) -> ";
            }
            $n++;
        }
        return $backtrace;
    }

    private static function getViewQuery($obj){
        $html = "<div class='query'>{$obj['query']}</div>";
        $html .= "<div class='time'>".sprintf("%01.5f", $obj['time'])."</div>";
        $html .= "<div class='backtrace'>".self::getViewBacktrace($obj['backtrace'])."</div>";
        if($obj['err_str']!=''){
            $html = "<div class='query_error'>$html</div>";
        }
        return $html;
    }

    private static function getVariableQuery($obj){
        $html = "<div class='query'>{$obj['var']}</div>";
        $html .= "<div class='backtrace'>".self::getViewBacktrace($obj['backtrace'])."</div>";
        return $html;
    }

    static function variable($obj){
        if(self::enabled()){
            $tv = array();
            $tv['type'] = 'variable';
            $tv['var'] = $obj;
            $tv['backtrace'] = debug_backtrace();
            self::$objects[] = $tv;
        }
    }

    static function getHtml(){
        if(self::enabled()){
            $str = '';
            $str .= '<link href="/debug.css" rel="stylesheet" type="text/css">';
            $str .= "<div class='debug'>";
            foreach(self::$objects as $obj){
                if($obj['type']=='query')       $str .= self::getView(self::getViewQuery($obj));
                if($obj['type']=='variable')    $str .= self::getView(self::getVariableQuery($obj));
            }
            $str .= "</div>";
            return $str;
        }
    }

    private static function getView($html){
        return "<div class='debug_obj'>$html</div>";
    }

    static function breakpoint(){
        throw new waException("Breakpoint", 404);
    }

    static function trace(){
        echo "<pre>";
        debug_print_backtrace();
        echo "</pre>";
        die();
    }

    static function showQueries(){
        if(self::enabled()){
            foreach(self::$objects as $obj){
                if($obj['type']=='query'){
                    echo $obj['query']."\n";
                    echo sprintf("%01.5f", $obj['time'])."\n\n";
                }
            }
        }
    }

    static function showTime(){
        $backtrace = debug_backtrace();
        $line_str = '';
        //print_r($backtrace);
        if(isset($backtrace[0])){
            $line_str = basename($backtrace[0]['file'])."::".$backtrace[0]['line'];
        }
        global $_gl_time_start;
        $_gl_time_end = microtime(1);
        $time = (intval(($_gl_time_end - $_gl_time_start)*1000))/1000;
        echo "Timer: ".$time." sec, file: $line_str\n";
    }
    */
}