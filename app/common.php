<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
function dateFormat($date, $language = ''){
    if(!empty($date)){
        $date = strtotime($date);
        if($language == 'zh-cn'){
            $redate = date('Y年n月j日', $date);
        }
        else{
            $redate = date('Y/m/d', $date);
        }
        $ma = date('a', $date);
        if($language == 'zh-cn'){
            $ma = ($ma == 'am') ? '上午' : '下午';
            $redate .= ' ' . $ma . date('g:i', $date);
        }
        else{
            $redate .= ' ' . date('h:i', $date) . ' ' . $ma;
        }
        return $redate;
    }
    return $date;
}