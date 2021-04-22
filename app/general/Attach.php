<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\general;

use jsnpp\Tools;

class Attach
{
    private static $plugin = [];
    private static $group = [];
    public static function setPlugin($group, $val)
    {
        self::$plugin[$group][] = $val;
    }
    public static function setGroup($group, $pluginclass, $themeclass, $post = 'post', $groupinfo = '')
    {
        if(is_array($groupinfo)){
            $groupinfo['plugin'] = $pluginclass;
            $groupinfo['theme'] = $themeclass;
            $groupinfo['post'] = $post;
            if(false !== $gkey = self::ingroup($group)){
                self::$group[$gkey] = [
                    'group' => $group,
                    'info' => $groupinfo,
                    'plugin' => self::$plugin[$group]
                ];
            }
            else{
                self::$group[] = [
                    'group' => $group,
                    'info' => $groupinfo,
                    'plugin' => self::$plugin[$group]
                ];
            }
        }
        else{
            if(false !== $gkey = self::ingroup($group)){
                self::$group[$gkey]['plugin'] = self::$plugin[$group];
            }
        }
    }
    public static function getPlugin()
    {
        return self::$plugin;
    }
    public static function getGroup()
    {
        foreach(self::$group as $key => $val){
            if(isset($val['info']['allow'])){
                self::$group[$key]['info']['allow'] = self::allow($val['info']['allow']);
            }
            else{
                self::$group[$key]['info']['allow'] = self::allow('administrator');
            }
        }
        return self::$group;
    }
    private static function allow($name)
    {
        $arr = [
            'administrator' => ['administrator'],
            'editor' => ['administrator', 'editor'],
            'author' => ['administrator', 'editor', 'author'],
            'contributor' => ['administrator', 'editor', 'author', 'contributor'],
            'subscriber' => ['administrator', 'editor', 'author', 'contributor', 'subscriber'],
        ];
        return in_array(Tools::$app->session->get('type'), $arr[$name]) ? true : false;
    }
    public static function getContent($group, $current)
    {
        if(isset(self::$plugin[$group])){
            foreach(self::$plugin[$group] as $key => $val){
                if($val['current'] == $current){
                    return $val['content'];
                }
            }
        }
        return '';
    }
    private static function ingroup($group){
        foreach(self::$group as $key => $val){
            if($val['group'] == $group){
                return $key;
            }
        }
        return false;
    }
}