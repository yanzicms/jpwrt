<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\controller;
use jsnpp\Controller;
class Register extends Controller
{
    public function index($param)
    {
        $this->app->entrance->check('post')
            ->check($param['username'], [
                'require' => $this->lang->translate('Username must be filled in'),
                'alphaNumHyphen' => $this->lang->translate('Username starts with a letter and can only contain letters, numbers and hyphens')
            ])
            ->check($param['password'], [
                'require' => $this->lang->translate('Password must be filled in'),
                'regex | .{8,}' => $this->lang->translate('Password length must be no less than 8 characters'),
            ])
            ->check($param['email'], [
                'require' => $this->lang->translate('E-mail must be filled in'),
                'email' => $this->lang->translate('Incorrect email format'),
            ])
            ->inbox('random', md5(time() . rand()))
            ->db->table('take')->field('takevalue')->where('takename', 'membership')->box('membership')->find()
            ->table('users')->field('id')->where('username', $param['username'])->box('hasname')->find()
            ->table('users')->field('id')->where('email', $param['email'])->box('hasemail')->find()
            ->check->stop(':box(membership.takevalue)', '!=', 1, $this->lang->translate('Registration has been closed.'))
            ->stop(':box(hasname)', '!=', 'empty', $this->lang->translate('The user name has been registered, please change and continue'))
            ->stop(':box(hasemail)', '!=', 'empty', $this->lang->translate('The e-mail address has been used, please change and continue'))
            ->db->table('take')->field('takevalue')->where('takename', 'role')->box('role')->find()->table('users')->insert([
                'username' => $param['username'],
                'nickname' => $param['username'],
                'publicname' => $param['username'],
                'password' => md5($this->box->get('random') . $param['password']),
                'email' => $param['email'],
                'createtime' => date("Y-m-d H:i:s"),
                'lastip' => $this->request->ip(),
                'randomcode' => ':box(random)',
                'usertype' => ':box(role.takevalue)',
            ])->output->display(':ok')->finish();
        $this->view->assign('webroot', $this->route->rootUrl())->display($this->theme('register'));
    }
    private function theme($name)
    {
        $themePath = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . $this->app->getConfig('template') . $this->DS . str_replace(['\\', '/'], $this->DS, $name) . '.' . $this->app->getConfig('templatesuffix');
        if(is_file($themePath)){
            return $themePath;
        }
        return '';
    }
    private function getTake($name)
    {
        $this->app->db->table('take')->field('takevalue')->where('takename', $name)->cache(1200, 'take_' . $name)->box('take')->find()->finish();
        return $this->box->get('take.takevalue');
    }
    public function initialize()
    {
        if($this->session->has('id') || $this->getTake('membership') != 1){
            $this->route->redirect('/');
            exit();
        }
    }
}