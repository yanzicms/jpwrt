<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\general;

class Ladder
{
    protected static $primary_key = 'id';
    protected static $parent_key = 'parent';
    protected static $children_key = 'child';
    protected static $levelstr = null;
    protected static $result = [];
    protected static $level = [];
    public static function makeLadder($data){
        if(empty($data)){
            return $data;
        }
        $dataset = self::buildData($data);
        $rarr = self::makeLadderCore(0,$dataset,'normal');
        return $rarr;
    }
    public static function makeLadderForHtml($data, $level = null){
        if(empty($data)){
            return $data;
        }
        self::$result = [];
        self::$levelstr = $level;
        $dataset = self::buildData($data);
        $rarr = self::makeLadderCore(0,$dataset,'linear');
        return $rarr;
    }
    private static function buildData($data){
        $rarr = [];
        foreach($data as $item){
            $id = $item[self::$primary_key];
            $parent_id = $item[self::$parent_key];
            $rarr[$parent_id][$id] = $item;
        }
        return $rarr;
    }
    private static function makeLadderCore($index,$data,$type='linear')
    {
        $rarr = [];
        foreach($data[$index] as $id=>$item)
        {
            if($type=='normal'){
                if(isset($data[$id]))
                {
                    $item[self::$children_key]= self::makeLadderCore($id,$data,$type);
                }
                $rarr[] = $item;
            }
            elseif($type=='linear'){
                $parent_id = $item[self::$parent_key];
                self::$level[$id] = $index == 0 ? 0 : self::$level[$parent_id] + 1;
                $item['level'] = is_null(self::$levelstr) ? self::$level[$id] : str_repeat(self::$levelstr, self::$level[$id]);
                self::$result[] = $item;
                if(isset($data[$id])){
                    self::makeLadderCore($id,$data,$type);
                }
                $rarr = self::$result;
            }
        }
        return $rarr;
    }
}