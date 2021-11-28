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
use jsnpp\Db;
class Login extends Controller
{
    public function index($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($param['username'], [
            'require' => $this->lang->translate('Username must be filled in'),
            'alphaNumHyphen' => $this->lang->translate('Username does not exist')
        ])
            ->check($param['password'], [
                'require' => $this->lang->translate('Password must be filled in'),
                'regex | .{8,}' => $this->lang->translate('Wrong password'),
            ])->check($param['captcha'], 'captcha', $this->lang->translate('Verification code error'))
            ->db->table('users')->field('id,password,randomcode,usertype,language')->where('username', $param['username'])->where('status', 1)->box('user')->find()
            ->check->stop(':box(user)', 'empty', $this->lang->translate('Username or password is incorrect'))->stop(':box(user.password)', '!=', md5($this->box->get('user.randomcode') . $param['password']), $this->lang->translate('Username or password is incorrect'))
            ->db->table('users')->where('id', ':box(user.id)')->update([
                'lastime' => date("Y-m-d H:i:s"),
                'lastip' => $this->request->ip()
            ])
            ->check->session('id', ':box(user.id)')->session('user', $param['username'])->session('type', ':box(user.usertype)')->session('language', ':box(user.language)')->cookie('user', $param['username'], 604800, isset($param['rememberme']) && $param['rememberme'] == 'on')->cookie('upd', md5($this->box->get('user.randomcode') . $this->box->get('user.password') . $param['username']), 604800, isset($param['rememberme']) && $param['rememberme'] == 'on')->output->display(':ok')->finish();
        $this->view->assign('webroot', $this->route->rootUrl())->assign('membership', $this->getTake('membership'))->display($this->theme('login'));
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
        if($this->session->has('id')){
            $this->route->redirect('admin');
            exit();
        }
    }
}