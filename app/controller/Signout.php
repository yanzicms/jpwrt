<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\controller;
use jsnpp\Cookie;
use jsnpp\Route;
use jsnpp\Session;
class Signout
{
    public function index(Session $session, Cookie $cookie, Route $route)
    {
        $session->remove('id')->remove('user')->remove('type');
        $cookie->remove('user')->remove('upd');
        $route->redirect('/');
    }
}