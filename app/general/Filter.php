<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\general;

class Filter
{
    public static function html($str)
    {
        return htmlspecialchars($str, ENT_QUOTES);
    }
    public static function weedout($str)
    {
        $reg = '/(<.*?)(((\s|"|\')on[A-Za-z]+\s*=\s*((".*?")|(\'.*?\')))+)(.*?>)/i';
        while(preg_match($reg, $str)){
            $str = preg_replace($reg, '$1$4$8', $str);
        }
        $str = preg_replace('/<script\s*>.*?<\/script\s*>/is', '', $str);
        return $str;
    }
}