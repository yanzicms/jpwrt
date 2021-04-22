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

class Plugin
{
    public static function set($group, $current, $description, $content, $post = 'post', $groupinfo = '', $show = true)
    {
        if(empty($group)){
            $group = 'plugins';
        }
        $detr = debug_backtrace();
        $classArr = explode('\\', str_replace('/', '\\', $detr[1]['class']));
        $class = lcfirst(end($classArr));
        Attach::setPlugin($group, [
            'current' => $current,
            'description' => $description,
            'content' => $content,
            'plugin' => $class,
            'theme' => '_non',
            'post' => $post,
            'show' => $show
        ]);
        if(!in_array($group, ['dashboard', 'posts', 'categories', 'tags', 'pages', 'comments', 'appearance', 'plugins', 'users', 'tools', 'settings'])){
            Attach::setGroup($group, $class, '', $post, $groupinfo);
        }
    }
    public static function url($group, $current, $post = 'post', $param = [])
    {
        if(empty($group)){
            $group = 'plugins';
        }
        $detr = debug_backtrace();
        $classArr = explode('\\', str_replace('/', '\\', $detr[1]['class']));
        $class = lcfirst(end($classArr));
        $arr = ['_group' => $group, '_current' => $current, '_plugin' => $class, '_post' => $post];
        if(!empty($param)){
            $arr = array_merge($arr, $param);
        }
        return Tools::$app->route->url('admin/attach', $arr);
    }
    public static function folder()
    {
        return Tools::$app->appDir() . DIRECTORY_SEPARATOR . Tools::$app->getConfig('handle') . DIRECTORY_SEPARATOR;
    }
    public static function result($result = 'ok', $message = '')
    {
        Tools::$app->response->receive([
            'result' => $result,
            'code' => 0,
            'message' => $message,
            'list' => []
        ])->output();
        exit();
    }
    public static function getVal($name)
    {
        $name = 'p_' . $name;
        Tools::$app->db->table('take')->field('takevalue')->where('takename', $name)->cache(1200, 'take_' . $name)->box('plugintake')->find()->finish();
        return Tools::$app->box->get('plugintake.takevalue');
    }
    public static function setVal($name, $value)
    {
        $name = 'p_' . $name;
        Tools::$app->db->table('take')->field('takevalue')->where('takename', $name)->box('plugintake')->find()->finish();
        if(empty(Tools::$app->box->get('plugintake'))){
            Tools::$app->db->table('take')->data('takename', $name)->data('takevalue', $value)->removeCache('take_' . $name)->insert()->finish();
        }
        else{
            Tools::$app->db->table('take')->where('takename', $name)->data('takevalue', $value)->removeCache('take_' . $name)->update()->finish();
        }
    }
    public static function delVal($name)
    {
        $name = 'p_' . $name;
        Tools::$app->db->table('take')->where('takename', $name)->removeCache('take_' . $name)->delete()->finish();
    }
    public static function administrator()
    {
        return in_array(Tools::$app->session->get('type'), ['administrator']) ? true : false;
    }
    public static function editor()
    {
        return in_array(Tools::$app->session->get('type'), ['administrator', 'editor']) ? true : false;
    }
    public static function author()
    {
        return in_array(Tools::$app->session->get('type'), ['administrator', 'editor', 'author']) ? true : false;
    }
    public static function contributor()
    {
        return in_array(Tools::$app->session->get('type'), ['administrator', 'editor', 'author', 'contributor']) ? true : false;
    }
    public static function subscriber()
    {
        return in_array(Tools::$app->session->get('type'), ['administrator', 'editor', 'author', 'contributor', 'subscriber']) ? true : false;
    }
}