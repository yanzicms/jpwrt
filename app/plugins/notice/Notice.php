<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\plugins\notice;
use app\general\Plugin;
use jsnpp\Controller;
class Notice extends Controller
{
    private $notice = 'notice_content';
    public function open()
    {
        Plugin::setVal($this->notice, '');
    }
    public function close()
    {
        Plugin::delVal($this->notice);
    }
    public function general()
    {
        $notice = Plugin::getVal($this->notice);
        if(!empty($notice)){
            $this->view->appendAssign('homeTop', '<div style="background-color: #ffffff;margin-bottom: 10px;padding: 10px">' . $notice . '</div>');
        }
    }
    public function assign()
    {
        $this->view->assign('notice', Plugin::getVal($this->notice));
    }
    public function addPlugin()
    {
        Plugin::set('', 'notice', $this->lang->translate('Notice'), $this->html());
    }
    private function html()
    {
        return '<div class="h3 mb-3">{lang("Notice")}</div>
<form method="post">
  <div class="form-group">
    <label for="content">{lang("Content")}</label>
    <textarea class="form-control" id="content" name="content" rows="5">{$notice}</textarea>
  </div>
  <button type="submit" class="btn btn-outline-success">{lang("Submit")}</button>
</form>';
    }
    public function post($param)
    {
        Plugin::setVal($this->notice, $param['content']);
    }
}