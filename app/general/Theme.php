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
class Theme
{
    public static function set($group, $current, $description, $content, $post = 'post', $groupinfo = '', $show = true)
    {
        if(empty($group)){
            $group = 'appearance';
        }
        $template = Tools::$app->getConfig('template');
        Attach::setPlugin($group, [
            'current' => $current,
            'description' => $description,
            'content' => $content,
            'plugin' => '_non',
            'theme' => $template,
            'post' => $post,
            'show' => $show
        ]);
        if(!in_array($group, ['dashboard', 'posts', 'categories', 'tags', 'pages', 'comments', 'appearance', 'plugins', 'users', 'tools', 'settings'])){
            Attach::setGroup($group, '', $template, $post, $groupinfo);
        }
    }
    public static function folder()
    {
        return Tools::$app->rootDir() . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'themes' . DIRECTORY_SEPARATOR . Tools::$app->getConfig('template') . DIRECTORY_SEPARATOR;
    }
    public static function getVal($name)
    {
        $name = 't_' . $name;
        Tools::$app->db->table('take')->field('takevalue')->where('takename', $name)->cache(1200, 'take_' . $name)->box('plugintake')->find()->finish();
        return Tools::$app->box->get('plugintake.takevalue');
    }
    public static function setVal($name, $value)
    {
        $name = 't_' . $name;
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
        $name = 't_' . $name;
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