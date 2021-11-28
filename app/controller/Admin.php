<?php
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\controller;
use app\general\Attach;
use app\general\Distant;
use app\general\Filter;
use app\general\Ladder;
use jsnpp\Cache;
use jsnpp\Controller;
use jsnpp\Database;
use jsnpp\Tools;
class Admin extends Controller
{
    private $per = 10;
    private $linkheight = 70;
    public function index(Cache $cache)
    {
        if($this->editor()){
            $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->box('totalposts')->cache(1200)->count('id')->table('pages')->box('totalpages')->cache(1200)->count('id')->table('comments')->box('totalcomments')->cache(1200)->count('id')->table('posts')->field('id,title,createtime')->where('status', '>', 0)->where('trash', 0)->order('id DESC')->limit(5)->box('posts')->cache(1200)->select()->table('comments')->field('id,createtime,comment')->order('id DESC')->limit(5)->box('comments')->cache(1200)->select()->output->assign('share')->assign('group', 'dashboard')->assign('current', 'home')->assign('version', $this->app->getConfig('version'))->assign('totalposts', ':box(totalposts)')->assign('totalpages', ':box(totalpages)')->assign('totalcomments', ':box(totalcomments)')->assign('posts', ':box(posts)')->assign('comments', ':box(comments)')->assign('usedtheme', $this->usedtheme($cache))->assign('dashboardguide', $this->getTake('dashboardguide'))->display()->finish();
        }
        else{
            $this->app->entrance->check('get')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('uid', $this->session->get('id'))->box('totalposts')->cache(1200)->count('id')->table('comments')->where('uid', $this->session->get('id'))->box('totalcomments')->cache(1200)->count('id')->table('posts')->field('id,title,createtime')->where('uid', $this->session->get('id'))->where('status', '>', 0)->where('trash', 0)->order('id DESC')->limit(5)->box('posts')->cache(1200)->select()->table('comments')->where('uid', $this->session->get('id'))->field('id,createtime,comment')->order('id DESC')->limit(5)->box('comments')->cache(1200)->select()->output->assign('share')->assign('group', 'dashboard')->assign('current', 'home')->assign('version', $this->app->getConfig('version'))->assign('totalposts', ':box(totalposts)')->assign('totalpages', ':box(totalpages)')->assign('totalcomments', ':box(totalcomments)')->assign('posts', ':box(posts)')->assign('comments', ':box(comments)')->assign('usedtheme', $this->usedtheme($cache))->assign('dashboardguide', 0)->display()->finish();
        }
    }
    public function dismiss()
    {
        if($this->request->isPost()){
            $this->setTake('dashboardguide', 0);
        }
    }
    private function usedtheme($cache)
    {
        $template = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . $this->app->getConfig('template');
        $this->lang->load($template . $this->DS . 'lang' . $this->DS . $this->app->getConfig('language') . '.php');
        if($cache->has('jpwrtusedtheme')){
            $themeName = $cache->get('jpwrtusedtheme');
        }
        else{
            $themeName = '';
            $readme = $template . $this->DS . 'readme.txt';
            if(is_file($readme)){
                $lines = file($readme);
                foreach($lines as $lkey => $lval){
                    $pos = strpos($lval, ':');
                    if($pos !== false){
                        $left = strtolower(trim(substr($lval, 0, $pos)));
                        $left = lcfirst(str_replace(' ', '', ucwords($left)));
                        if($left == 'themeName'){
                            $right = substr($lval, $pos + 1);
                            $themeName = trim($right);
                            break;
                        }
                    }
                }
            }
            $cache->set('jpwrtusedtheme', $themeName, 604800);
        }
        return $this->lang->translate($themeName);
    }
    public function draft($param = '')
    {
        $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check($param['title'], 'require', $this->lang->translate('The title of the post must be filled in.'))->check($param['content'], 'require', $this->lang->translate('The content of the post must be filled in.'))
            ->db->beginTransaction()->table('posts')->box('postid')->insert([
                'uid' => $this->session->get('id'),
                'title' => Filter::html($param['title']),
                'createtime' => date('Y-m-d H:i:s'),
                'editime' => date('Y-m-d H:i:s'),
                'status' => 0,
                'content' => Filter::weedout($param['content'])
            ])->table('users')->where('id', $this->session->get('id'))->data('posts', 'posts+1')->update()->endTransaction()
            ->output->display(':ok')->finish();
    }
    public function comments()
    {
        if($this->editor()){
            $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('comments')->field('id,pid,editime,status,publicname,email,comment')->order('status ASC,id DESC')->paging($this->per)->box('comments')->select()->output->assign('share')->assign('group', 'comments')->assign('current', 'comments')->assign('comments', ':box(comments)')->display()->finish();
        }
        else{
            $this->app->entrance->check('get')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('comments')->where('uid', $this->session->get('id'))->field('id,pid,editime,status,publicname,email,comment')->order('status ASC,id DESC')->paging($this->per)->box('comments')->select()->output->assign('share')->assign('group', 'comments')->assign('current', 'comments')->assign('comments', ':box(comments)')->display()->finish();
        }
    }
    public function reviewcomments($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('comments')->where('id', $param['id'])->data('status', $param['review'] == 1 ? 3 : 1)->update()->check->deleteCacheTag('posts' . $param['pid'])->output->display(':ok')->finish();
    }
    public function deletecomments($param = '')
    {
        if($this->editor()){
            $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('comments')->field('uid,parent')->where('id', $param['id'])->box('comments')->find()->beginTransaction()->table('comments')->where('id', $param['id'])->delete()->table('comments')->where('parent', $param['id'])->data('parent', ':box(comments.parent)')->update()->table('users')->where('id', ':box(comments.uid)')->data('comments', 'comments-1')->update()->endTransaction()->output->display(':ok')->finish();
        }
        else{
            $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('comments')->field('parent')->where('id', $param['id'])->where('uid', $this->session->get('id'))->box('comments')->find()->beginTransaction()->table('comments')->where('id', $param['id'])->where('uid', $this->session->get('id'))->delete()->table('comments')->where('parent', $param['id'])->where('uid', $this->session->get('id'))->data('parent', ':box(comments.parent)')->update()->table('users')->where('id', $this->session->get('id'))->data('comments', 'comments-1')->update()->endTransaction()->output->display(':ok')->finish();
        }
    }
    public function originalpost($param = '')
    {
        $this->app->entrance->check('get')->db->table('posts')->field('id,uid,cid,slug,createtime')->where('id', $param['id'])->box('posts')->find()->table('users')->field('username')->where('id', ':box(posts.uid)')->box('users')->find()->table('categories')->field('slug')->where('id', ':box(posts.cid)')->box('categories')->find()->finish();
        $this->route->redirect('index/archives', ['id' => $this->box->get('posts.id'), 'name' => $this->box->get('posts.slug'), 'category' => $this->box->get('categories.slug'), 'author' => $this->box->get('users.username'), 'year' => date('Y', strtotime($this->box->get('posts.createtime'))), 'month' => date('m', strtotime($this->box->get('posts.createtime'))), 'day' => date('d', strtotime($this->box->get('posts.createtime')))]);
        exit();
    }
    public function discussion($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['closeday'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['nested'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['perdisplay'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check->param($param)->run('discussionFunc')->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('take')->field('id,takename,takevalue')->where('backstage', 'discussion')->box('discussion')->select()->check->filter(':box(discussion)', 'filtergeneralFunc')->output->assign('share')->assign('group', 'settings')->assign('current', 'discussion')->assign('discussion', ':box(discussion)')->display()->finish();
    }
    protected function discussionFunc($param)
    {
        $this->setTake('allowcomments', (isset($param['allowcomments']) && $param['allowcomments'] == 'on') ? 1 : 0);
        $this->setTake('nameemail', (isset($param['nameemail']) && $param['nameemail'] == 'on') ? 1 : 0);
        $this->setTake('login', (isset($param['login']) && $param['login'] == 'on') ? 1 : 0);
        $this->setTake('close', (isset($param['close']) && $param['close'] == 'on') ? 1 : 0);
        $this->setTake('closeday', intval($param['closeday']));
        $this->setTake('opennested', (isset($param['opennested']) && $param['opennested'] == 'on') ? 1 : 0);
        $this->setTake('nested', intval($param['nested']));
        $this->setTake('pagingdisplay', (isset($param['pagingdisplay']) && $param['pagingdisplay'] == 'on') ? 1 : 0);
        $this->setTake('perdisplay', intval($param['perdisplay']));
        $this->setTake('defaultdisplay', Filter::html($param['defaultdisplay']));
        $this->setTake('approval', (isset($param['approval']) && $param['approval'] == 'on') ? 1 : 0);
        $this->setTake('previousapproved', (isset($param['previousapproved']) && $param['previousapproved'] == 'on') ? 1 : 0);
        $this->setTake('reviewkeywords', $param['reviewkeywords']);
        $this->setTake('rejectkeywords', $param['rejectkeywords']);
    }
    public function media($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['thumbnail-width'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['thumbnail-height'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['medium-width'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['medium-height'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['large-width'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['large-height'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['slide-width'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check($param['slide-height'], 'positiveInteger', $this->lang->translate('Only positive integers can be filled in.'))->check->param($param)->run('mediaFunc')->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('take')->field('id,takename,takevalue')->where('backstage', 'media')->box('media')->select()->check->filter(':box(media)', 'filtergeneralFunc')->output->assign('share')->assign('group', 'settings')->assign('current', 'media')->assign('media', ':box(media)')->display()->finish();
    }
    protected function mediaFunc($param)
    {
        $this->setTake('thumbnail-width', intval($param['thumbnail-width']));
        $this->setTake('thumbnail-height', intval($param['thumbnail-height']));
        $this->setTake('medium-width', intval($param['medium-width']));
        $this->setTake('medium-height', intval($param['medium-height']));
        $this->setTake('large-width', intval($param['large-width']));
        $this->setTake('large-height', intval($param['large-height']));
        $this->setTake('slide-width', intval($param['slide-width']));
        $this->setTake('slide-height', intval($param['slide-height']));
    }
    public function reading($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check(!($param['homepage'] == 'static' && $param['staticpage'] == 0), $this->lang->translate('The page must be selected.'))->check->param($param)->run('readingFunc')->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('take')->field('id,takename,takevalue')->where('backstage', 'reading')->box('reading')->select()->table('pages')->field('id,title,parent')->order('sort ASC')->box('pages')->select()->check->filter(':box(pages)', 'filterFunc')->filter(':box(reading)', 'filtergeneralFunc')->output->assign('share')->assign('group', 'settings')->assign('current', 'reading')->assign('reading', ':box(reading)')->assign('pages', ':box(pages)')->assign('homepage', $this->app->getConfig('homepage'))->assign('staticpage', $this->app->getConfig('staticpage'))->assign('homeshow', $this->app->getConfig('homeshow'))->assign('pageshow', $this->app->getConfig('pageshow'))->display()->finish();
    }
    protected function readingFunc($param)
    {
        $this->app->writeCustomize('site', [
            'homepage' => Filter::html($param['homepage']),
            'staticpage' => intval($param['staticpage']),
            'homeshow' => intval($param['homeshow']),
            'pageshow' => intval($param['pageshow'])
        ]);
        $searchengine = (isset($param['searchengine']) && $param['searchengine'] == 'on') ? 0 : 1;
        $this->setTake('searchengine', $searchengine);
        $robotPath = $this->rootDir . $this->DS . 'robots.txt';
        @chmod($robotPath, 0777);
        if($searchengine == 1){
            file_put_contents($robotPath, 'User-agent: *' . PHP_EOL . 'Allow: /');
        }
        else{
            file_put_contents($robotPath, 'User-agent: *' . PHP_EOL . 'Disallow: /');
        }
    }
    public function permalinks($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['permalink'], 'require', $this->lang->translate('Permalink must be selected.'))->check($this->incustom($param['custom']), $this->lang->translate('Custom structure tag error.'))->check($param['category'], 'alpha', $this->lang->translate('The category prefix can only contain letters.'))->check($param['tag'], 'alpha', $this->lang->translate('The tag prefix can only contain letters.'))
            ->config->writeRouting([
                'index/archives' => $this->permalink($param['permalink'], $param['custom']),
                'index/category' => empty($param['category']) ? 'category/{name}' : $param['category'] . '/{name}',
                'index/page' => empty($param['page']) ? 'page/{name}' : $param['page'] . '/{name}',
                'index/tag' => empty($param['tag']) ? 'tag/{name}' : $param['tag'] . '/{name}',
            ])->writeCustomize('site', [
                'permalink' => $param['permalink'],
                'permalinkCustom' => $param['custom'],
                'permalinkCategory' => $param['category'],
                'permalinkPage' => $param['page'],
                'permalinkTag' => $param['tag']
            ])
            ->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->output->assign('share')->assign('group', 'settings')->assign('current', 'permalinks')->assign('host', $this->request->host())->assign('http', $this->request->isHttps() ? 'https://' : 'http://')->assign('month', date('Y/m'))->assign('day', date('Y/m/d'))->assign('id', '{id}')->assign('name', '{name}')->assign('year', '{year}')->assign('month', '{month}')->assign('day', '{day}')->assign('category', '{category}')->assign('author', '{author}')->assign('permalink', $this->app->getConfig('permalink'))->assign('permalinkCustom', $this->app->getConfig('permalinkCustom'))->assign('permalinkCategory', $this->app->getConfig('permalinkCategory'))->assign('permalinkPage', $this->app->getConfig('permalinkPage'))->assign('permalinkTag', $this->app->getConfig('permalinkTag'))->display()->finish();
    }
    private function permalink($permalink, $custom)
    {
        switch($permalink){
            case 'numeric':
                $re = 'archives/{id}';
                break;
            case 'name':
                $re = 'archives/{name}';
                break;
            case 'monthname':
                $re = 'archives/{month}/{name}';
                break;
            case 'dayname':
                $re = 'archives/{month}/{day}/{name}';
                break;
            case 'custom':
                $re = $custom;
                break;
            default:
                $re = 'archives/{id}';
                break;
        }
        return $re;
    }
    private function incustom($custom)
    {
        $re = false;
        if(!empty($custom)){
            $carr = explode('/', $custom);
            foreach($carr as $val){
                if(in_array($val, ['{id}', '{name}', '{year}', '{month}', '{day}', '{category}', '{author}'])){
                    $re = true;
                    break;
                }
            }
        }
        return $re;
    }
    public function profile($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->check($param['nickname'], 'require', $this->lang->translate('The nickname must be filled in.'))->check($param['email'], 'require', $this->lang->translate('E-mail must be filled in.'))->check($param['email'], 'email', $this->lang->translate('Incorrect email format.'))
            ->db->table('users')->where('id', $this->session->get('id'))->update([
                'nickname' => Filter::html($param['nickname']),
                'firstname' => Filter::html($param['firstname']),
                'lastname' => Filter::html($param['lastname']),
                'publicname' => Filter::html($param['publicname']),
                'email' => Filter::html($param['email']),
                'url' => Filter::html($param['url']),
                'signature' => Filter::html($param['signature']),
                'language' => Filter::html($param['language']),
            ])
            ->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->field('id,username,nickname,firstname,lastname,publicname,email,url,avatar,signature,usertype,language')->where('id', $this->session->get('id'))->box('users')->find()->check->filter(':box(users)', 'filterEditUsersFunc')->output->assign('share')->assign('group', 'users')->assign('current', 'profile')->assign('users', ':box(users)')->display()->finish();
    }
    public function uploadimgprofile()
    {
        $this->app->entrance->check('post')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->field('id,avatar')->where('id', $this->session->get('id'))->box('users')->find()->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->cut(200, 200, '', 'adapt')->box('imgname')->db->table('users')->where('id', $this->session->get('id'))->data('avatar', ':box(imgname)')->update()->check->run('uploadimgedituserFunc')->output->display(':box(imgname)')->finish();
    }
    public function deleteimgprofile()
    {
        $this->app->entrance->check('post')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->field('id,avatar')->where('id', $this->session->get('id'))->box('users')->find()->check->run('deleteimgedituserFunc')->output->display(':ok')->finish();
    }
    public function deletuser($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->check($param['id'] != 1, $this->lang->translate('Insufficient permissions.'))->check($param['id'] != $this->session->get('id'), $this->lang->translate('You cannot delete yourself.'))->db->table('posts')->field('id')->where('uid', $param['id'])->box('posts')->find()->table('users')->field('id,avatar')->where('id', $param['id'])->box('users')->find()->check->stop(':box(posts)', '!=', 'empty', $this->lang->translate('This user has published posts and cannot be deleted. Please clear the posts before proceeding.'))->db->beginTransaction()->table('users')->where('id', $param['id'])->delete()->table('comments')->where('uid', $param['id'])->delete()->endTransaction()->check->run('uploadimgedituserFunc')->output->display(':ok')->finish();
    }
    public function edituser($param = '')
    {
        if($this->administrator()){
            !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['nickname'], 'require', $this->lang->translate('The nickname must be filled in.'))->check($param['email'], 'require', $this->lang->translate('E-mail must be filled in.'))->check($param['email'], 'email', $this->lang->translate('Incorrect email format.'))
                ->db->table('users')->where('id', $param['id'])->update([
                    'nickname' => Filter::html($param['nickname']),
                    'firstname' => Filter::html($param['firstname']),
                    'lastname' => Filter::html($param['lastname']),
                    'publicname' => Filter::html($param['publicname']),
                    'email' => Filter::html($param['email']),
                    'url' => Filter::html($param['url']),
                    'signature' => Filter::html($param['signature']),
                    'usertype' => Filter::html($param['usertype']),
                    'language' => Filter::html($param['language']),
                ])
                ->output->display(':ok')->finish();
            $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->inbox('id', $param['id'])->db->table('users')->field('id,username,nickname,firstname,lastname,publicname,email,url,avatar,signature,usertype,language')->where('id', $param['id'])->box('users')->find()->check->filter(':box(users)', 'filterEditUsersFunc')->output->assign('share')->assign('group', 'users')->assign('current', 'allusers')->assign('users', ':box(users)')->display()->finish();
        }
        else{
            !$this->request->isPost() || $this->app->entrance->check('post')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->check($param['nickname'], 'require', $this->lang->translate('The nickname must be filled in.'))->check($param['email'], 'require', $this->lang->translate('E-mail must be filled in.'))->check($param['email'], 'email', $this->lang->translate('Incorrect email format.'))
                ->db->table('users')->where('id', $this->session->get('id'))->update([
                    'nickname' => Filter::html($param['nickname']),
                    'firstname' => Filter::html($param['firstname']),
                    'lastname' => Filter::html($param['lastname']),
                    'publicname' => Filter::html($param['publicname']),
                    'email' => Filter::html($param['email']),
                    'url' => Filter::html($param['url']),
                    'signature' => Filter::html($param['signature']),
                    'language' => Filter::html($param['language']),
                ])
                ->output->display(':ok')->finish();
            $this->app->entrance->check('get')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->inbox('id', $this->session->get('id'))->db->table('users')->field('id,username,nickname,firstname,lastname,publicname,email,url,avatar,signature,usertype,language')->where('id', $this->session->get('id'))->box('users')->find()->check->filter(':box(users)', 'filterEditUsersFunc')->output->assign('share')->assign('group', 'users')->assign('current', 'allusers')->assign('users', ':box(users)')->display()->finish();
        }
    }
    public function deleteimgedituser($param = '')
    {
        if($this->request->isPost() && $this->administrator()){
            $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->field('id,avatar')->where('id', $param['id'])->box('users')->find()->check->run('deleteimgedituserFunc')->output->display(':ok')->finish();
        }
        else{
            $this->app->entrance->check('post')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->field('id,avatar')->where('id', $this->session->get('id'))->box('users')->find()->check->run('deleteimgedituserFunc')->output->display(':ok')->finish();
        }
    }
    protected function deleteimgedituserFunc()
    {
        $isdel = false;
        $imgpath = $this->box->get('users.avatar');
        if(!empty($imgpath)){
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            $isdel = true;
        }
        if($isdel){
            $this->app->db->table('users')->where('id', $this->box->get('users.id'))->data('avatar', '')->update()->finish();
        }
    }
    public function uploadimgedituser($param = '')
    {
        if($this->request->isPost() && $this->administrator()){
            $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->field('id,avatar')->where('id', $param['id'])->box('users')->find()->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->cut(200, 200, '', 'adapt')->box('imgname')->db->table('users')->where('id', $param['id'])->data('avatar', ':box(imgname)')->update()->check->run('uploadimgedituserFunc')->output->display(':box(imgname)')->finish();
        }
        else{
            $this->app->entrance->check('post')->check($this->subscriber(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->field('id,avatar')->where('id', $this->session->get('id'))->box('users')->find()->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->cut(200, 200, '', 'adapt')->box('imgname')->db->table('users')->where('id', $this->session->get('id'))->data('avatar', ':box(imgname)')->update()->check->run('uploadimgedituserFunc')->output->display(':box(imgname)')->finish();
        }
    }
    protected function uploadimgedituserFunc()
    {
        if($this->box->has('users.avatar')){
            $imgpath = $this->box->get('users.avatar');
            if(!empty($imgpath)){
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            }
        }
    }
    protected function filterEditUsersFunc($param)
    {
        $publicly[] = $param['nickname'];
        if(!empty($param['firstname'])){
            $publicly[] = $param['firstname'];
        }
        if(!empty($param['lastname'])){
            $publicly[] = $param['lastname'];
        }
        if(!empty($param['firstname']) && !empty($param['lastname'])){
            $publicly[] = $param['firstname'] . ' ' . $param['lastname'];
            $publicly[] = $param['lastname'] . ' ' . $param['firstname'];
        }
        $param['publicly'] = $publicly;
        return $param;
    }
    public function addnewuser($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['username'], 'require', $this->lang->translate('The username must be filled in.'))->check($param['password'], 'require', $this->lang->translate('The password must be filled in.'))->check($param['username'], 'alphaNumHyphen', $this->lang->translate('The username can only contain letters, numbers and hyphens. And start with a letter.'))->check($param['password'], 'regex | .{8,}', $this->lang->translate('Password length must be no less than 8 characters.'))->inbox('random', md5(time() . rand()))
            ->db->table('users')->field('id')->where('username', $param['username'])->box('hasusername')->find()
            ->check->stop(':box(hasusername)', '!=', 'empty', $this->lang->translate('The username already exists, please change and continue.'))
            ->db->table('users')->insert([
                'username' => Filter::html($param['username']),
                'password' => md5($this->box->get('random') . $param['password']),
                'nickname' => Filter::html($param['username']),
                'publicname' => Filter::html($param['username']),
                'firstname' => Filter::html($param['firstname']),
                'lastname' => Filter::html($param['lastname']),
                'url' => Filter::html($param['url']),
                'createtime' => date("Y-m-d H:i:s"),
                'randomcode' => ':box(random)',
                'usertype' => Filter::html($param['usertype']),
                'language' => Filter::html($param['language'])
            ])
            ->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->output->assign('share')->assign('group', 'users')->assign('current', 'addnewuser')->display()->finish();
    }
    public function allusers()
    {
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('users')->where('id', '>', 1)->field('id,username,nickname,firstname,lastname,email,createtime,lastime,usertype,language,posts')->order('id DESC')->paging($this->per)->box('users')->select()->check->filter(':box(users)', 'filterUsersFunc')->output->assign('share')->assign('group', 'users')->assign('current', 'allusers')->assign('users', ':box(users)')->display()->finish();
    }
    protected function filterUsersFunc($param)
    {
        foreach($param['data'] as $key => $val){
            $param['data'][$key]['usertype'] = $this->lang->translate(ucfirst($val['usertype']));
            if($val['lastime'] == '1000-01-01 00:00:00'){
                $param['data'][$key]['lastime'] = '';
            }
        }
        return $param;
    }
    public function links()
    {
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('links')->field('id,name,sort,image,url,home,description')->order('sort ASC')->box('links')->select()->output->assign('share')->assign('group', 'appearance')->assign('current', 'links')->assign('links', ':box(links)')->display()->finish();
    }
    public function addnewlink()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->output->assign('share')->assign('group', 'appearance')->assign('current', 'links')->display()->finish();
    }
    public function addnewlinkexec($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the link must be filled in.'))->check($param['url'], 'require', $this->lang->translate('The url of the link must be filled in.'))->check($param['url'], 'url', $this->lang->translate('The URL format is incorrect.'))
            ->db->table('links')->insert([
                'name' => Filter::html($param['name']),
                'image' => Filter::html($this->session->has('uploadimg') ? $this->session->get('uploadimg') : ''),
                'url' => Filter::html($param['url']),
                'home' => (isset($param['home']) && $param['home'] == 'on') ? 1 : 0,
                'description' => Filter::html($param['description'])
            ])
            ->check->removeSession('uploadimg')->deleteCacheTag('links')->output->display(':ok')->finish();
    }
    public function putonhome($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('links')->where('id', $param['id'])->data('home', $param['home'])->update()->output->display(':ok')->finish();
    }
    public function orderlinkshow($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('links')->where('id', $param['id'])->data('sort', $param['sort'])->update()->output->display(':ok')->finish();
    }
    public function deletelink($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('links')->field('id,image')->where('id', $param['id'])->box('links')->find()->table('links')->where('id', $param['id'])->delete()->check->run('uploadimgeditlinkFunc')->output->display(':ok')->finish();
    }
    public function editlink($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('links')->where('id', $param['id'])->field('id,name,image,url,home,description')->box('links')->find()->output->assign('share')->assign('group', 'appearance')->assign('current', 'links')->assign('links', ':box(links)')->display()->finish();
    }
    public function editlinkexec($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the link must be filled in.'))->check($param['url'], 'require', $this->lang->translate('The url of the link must be filled in.'))->check($param['url'], 'url', $this->lang->translate('The URL format is incorrect.'))
            ->db->table('links')->where('id', $param['id'])->update([
                'name' => Filter::html($param['name']),
                'url' => Filter::html($param['url']),
                'home' => (isset($param['home']) && $param['home'] == 'on') ? 1 : 0,
                'description' => Filter::html($param['description'])
            ])
            ->check->deleteCacheTag('links')->output->display(':ok')->finish();
    }
    public function deleteimgeditlink($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('links')->field('id,image')->where('id', $param['id'])->box('links')->find()->check->run('deleteimgeditlinkFunc')->output->display(':ok')->finish();
    }
    protected function deleteimgeditlinkFunc()
    {
        $isdel = false;
        $imgpath = $this->box->get('links.image');
        if(!empty($imgpath)){
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            $isdel = true;
        }
        if($isdel){
            $this->app->db->table('links')->where('id', $this->box->get('links.id'))->data('image', '')->update()->finish();
        }
    }
    public function uploadimgeditlink($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('links')->field('id,image')->where('id', $param['id'])->box('links')->find()->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut(0, $this->linkheight, '', 'adaptRaw')->db->table('links')->where('id', $param['id'])->data('image', ':box(imgname)')->update()->check->run('uploadimgeditlinkFunc')->output->display(':box(imgname)')->finish();
    }
    protected function uploadimgeditlinkFunc()
    {
        if($this->box->has('links.image')){
            $imgpath = $this->box->get('links.image');
            if(!empty($imgpath)){
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            }
        }
    }
    public function slideshow()
    {
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('slide')->field('id,name,gid,sort,image,url,description')->order('gid ASC,sort ASC')->leftJoin('gid = id')->table('slidegroup')->field('name as groupname')->box('slide')->endJoin()->output->assign('share')->assign('group', 'appearance')->assign('current', 'slideshow')->assign('slide', ':box(slide)')->display()->finish();
    }
    public function slidegroup()
    {
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('slidegroup')->field('id,name,slug,width,height,description')->order('id ASC')->box('slidegroup')->select()->output->assign('share')->assign('group', 'appearance')->assign('current', 'slideshow')->assign('slidegroup', ':box(slidegroup)')->display()->finish();
    }
    public function addnewslidegroup()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->output->assign('share')->assign('group', 'appearance')->assign('current', 'slideshow')->display()->finish();
    }
    public function addnewslidegroupexec($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the slide group must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the slide group must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))->check($param['width'], 'require', $this->lang->translate('The width must be filled in.'))->check($param['height'], 'require', $this->lang->translate('The height must be filled in.'))->check($param['width'], 'integer', $this->lang->translate('The width can only be an integer.'))->check($param['height'], 'integer', $this->lang->translate('The height can only be an integer.'))
            ->db->table('slidegroup')->field('id')->where('slug', $param['slug'])->box('hasslug')->find()
            ->check->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('slidegroup')->insert([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'width' => intval($param['width']),
                'height' => intval($param['height']),
                'description' => Filter::html($param['description'])
            ])
            ->output->display(':ok')->finish();
    }
    public function editslidegroup($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('slidegroup')->where('id', $param['id'])->field('id,name,slug,width,height,description')->box('slidegroup')->find()->output->assign('share')->assign('group', 'appearance')->assign('current', 'slideshow')->assign('slidegroup', ':box(slidegroup)')->display()->finish();
    }
    public function editslidegroupexec($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the slide group must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the slide group must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))->check($param['width'], 'require', $this->lang->translate('The width must be filled in.'))->check($param['height'], 'require', $this->lang->translate('The height must be filled in.'))->check($param['width'], 'integer', $this->lang->translate('The width can only be an integer.'))->check($param['height'], 'integer', $this->lang->translate('The height can only be an integer.'))
            ->db->table('slidegroup')->field('id')->where('slug', $param['slug'])->where('slug', '!=', '')->where('id', '!=', $param['id'])->box('hasslug')->find()
            ->check->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('slidegroup')->where('id', $param['id'])->update([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'width' => intval($param['width']),
                'height' => intval($param['height']),
                'description' => Filter::html($param['description'])
            ])
            ->output->display(':ok')->finish();
    }
    public function deleteslidegroup($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->beginTransaction()->table('slidegroup')->where('id', $param['id'])->delete()->table('slide')->where('gid', $param['id'])->data('gid', 0)->update()->endTransaction()->output->display(':ok')->finish();
    }
    public function addnewslide()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('slidegroup')->field('id,name')->order('id ASC')->box('slidegroup')->select()->output->assign('share')->assign('group', 'appearance')->assign('current', 'slideshow')->assign('slidegroup', ':box(slidegroup)')->display()->finish();
    }
    public function addnewslideexec($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the slide must be filled in.'))->check($this->session->has('uploadimg'), $this->lang->translate('Image must be uploaded.'))
            ->db->table('slide')->insert([
                'name' => Filter::html($param['name']),
                'gid' => Filter::html($param['slidegroup']),
                'image' => Filter::html($this->session->has('uploadimg') ? $this->session->get('uploadimg') : ''),
                'url' => Filter::html($param['url']),
                'description' => Filter::html($param['description'])
            ])
            ->check->param($param['slidegroup'], $this->session->get('uploadimg'))->run('resizeImgFun')->removeSession('uploadimg')->deleteCacheTag('slide')->output->display(':ok')->finish();
    }
    protected function resizeImgFun($slidegroup, $uploadimg)
    {
        if($slidegroup == 0){
            $width = $this->getTake('slide-width');
            $height = $this->getTake('slide-height');
        }
        else{
            $this->app->db->table('slidegroup')->where('id', $slidegroup)->field('width,height')->box('slidegroup')->find()->finish();
            $width = $this->box->get('slidegroup.width');
            $height = $this->box->get('slidegroup.height');
        }
        $this->app->img->cut($width, $height, $this->rootDir . $this->DS . $uploadimg, '', 'adapt')->finish();
    }
    public function orderslideshow($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('slide')->where('id', $param['id'])->data('sort', $param['sort'])->update()->output->display(':ok')->finish();
    }
    public function editslide($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('slidegroup')->field('id,name')->order('id ASC')->box('slidegroup')->select()->table('slide')->where('id', $param['id'])->field('id,name,gid,image,url,description')->box('slide')->find()->output->assign('share')->assign('group', 'appearance')->assign('current', 'slideshow')->assign('slidegroup', ':box(slidegroup)')->assign('slide', ':box(slide)')->display()->finish();
    }
    public function editslideexec($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the slide must be filled in.'))
            ->db->table('slide')->field('image')->where('id', $param['id'])->box('hasimage')->find()
            ->check->stop(':box(hasimage.image)', '=', 'empty', $this->lang->translate('Image must be uploaded.'))
            ->db->table('slide')->where('id', $param['id'])->update([
                'name' => Filter::html($param['name']),
                'gid' => Filter::html($param['slidegroup']),
                'url' => Filter::html($param['url']),
                'description' => Filter::html($param['description'])
            ])
            ->check->param($param['slidegroup'], ':box(hasimage.image)')->run('resizeImgFun')->deleteCacheTag('slide')->output->display(':ok')->finish();
    }
    public function uploadimgeditslide($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('slide')->field('id,image')->where('id', $param['id'])->box('slide')->find()->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->db->table('slide')->where('id', $param['id'])->data('image', ':box(imgname)')->update()->check->run('uploadimgeditslideFunc')->output->display(':box(imgname)')->finish();
    }
    public function deleteimgeditslide($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('slide')->field('id,image')->where('id', $param['id'])->box('slide')->find()->check->run('deleteimgeditslideFunc')->output->display(':ok')->finish();
    }
    protected function deleteimgeditslideFunc()
    {
        $isdel = false;
        $imgpath = $this->box->get('slide.image');
        if(!empty($imgpath)){
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            $isdel = true;
        }
        if($isdel){
            $this->app->db->table('slide')->where('id', $this->box->get('slide.id'))->data('image', '')->update()->finish();
        }
    }
    protected function uploadimgeditslideFunc()
    {
        if($this->box->has('slide.image')){
            $imgpath = $this->box->get('slide.image');
            if(!empty($imgpath)){
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            }
        }
    }
    public function deleteslide($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('slide')->field('id,image')->where('id', $param['id'])->box('slide')->find()->table('slide')->where('id', $param['id'])->delete()->check->run('uploadimgeditslideFunc')->deleteCacheTag('slide')->output->display(':ok')->finish();
    }
    public function attach($param = '')
    {
        if($this->request->isPost()){
            if($param['_theme'] != '_non'){
                $template = $this->app->getConfig('template');
                if(is_file($this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . lcfirst($template) . $this->DS . ucfirst($template) . '.php')){
                    $this->handle->run('jpwrt\theme\\' . lcfirst($template) . '\\' . ucfirst($template), $param['_post'], $param);
                }
            }
            else{
                $this->handle->run($param['_plugin'], $param['_post'], $param);
            }
        }
        if($param['_theme'] != '_non'){
            $template = $this->app->getConfig('template');
            if(is_file($this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . lcfirst($template) . $this->DS . ucfirst($template) . '.php')){
                $this->handle->run('jpwrt\theme\\' . lcfirst($template) . '\\' . ucfirst($template), 'assign', $param);
                $this->handle->run('jpwrt\theme\\' . lcfirst($template) . '\\' . ucfirst($template), $param['_current'] . '_assign', $param);
                $this->handle->run('jpwrt\theme\\' . lcfirst($template) . '\\' . ucfirst($template), $param['_current'], $param);
            }
        }
        else{
            $this->handle->run($param['_plugin'], 'assign', $param);
            $this->handle->run($param['_plugin'], $param['_current'] . '_assign', $param);
            $this->handle->run($param['_plugin'], $param['_current'], $param);
        }
        $this->view->assign('share')->assign('group', $param['_group'])->assign('current', $param['_current'])->assign('attach', $this->view->template(Attach::getContent($param['_group'], $param['_current'])))->display();
    }
    public function menus()
    {
        $this->view->assign('share')->assign('group', 'appearance')->assign('current', 'menus')->assign('menu', unserialize($this->getTake('jswrt-menu')))->display();
    }
    public function saveprimarymenu($param = '')
    {
        if($this->request->isPost() && $this->administrator()){
            if(!preg_match('/^[A-Za-z0-9\-\_]+$/', $param['slug'])){
                echo $this->lang->translate('The slug can only contain letters, numbers and hyphens.');
            }
            else{
                $param['slug'] = trim($param['slug']);
                $menu = unserialize($this->getTake('jswrt-menu'));
                $haslug = false;
                if($menu['secondary']['slug'] == $param['slug']){
                    $haslug = true;
                }
                if(count($menu['other']) > 0){
                    foreach($menu['other'] as $item){
                        if($item['slug'] == $param['slug']){
                            $haslug = true;
                            break;
                        }
                    }
                }
                if($haslug){
                    echo $this->lang->translate('The slug already exists, please change and continue.');
                }
                else{
                    $oldslug = $menu['primary']['slug'];
                    $menu['primary']['name'] = $param['menuname'];
                    $menu['primary']['slug'] = $param['slug'];
                    $this->setTake('jswrt-menu', serialize($menu));
                    if($oldslug != $param['slug']){
                        $this->app->db->table('menu')->where('slug', $oldslug)->data('slug', $param['slug'])->update()->finish();
                    }
                    echo 'ok';
                }
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function savesecondarymenu($param = '')
    {
        if($this->request->isPost() && $this->administrator()){
            if(!preg_match('/^[A-Za-z0-9\-\_]+$/', $param['slug'])){
                echo $this->lang->translate('The slug can only contain letters, numbers and hyphens.');
            }
            else{
                $param['slug'] = trim($param['slug']);
                $menu = unserialize($this->getTake('jswrt-menu'));
                $haslug = false;
                if($menu['primary']['slug'] == $param['slug']){
                    $haslug = true;
                }
                if(count($menu['other']) > 0){
                    foreach($menu['other'] as $item){
                        if($item['slug'] == $param['slug']){
                            $haslug = true;
                            break;
                        }
                    }
                }
                if($haslug){
                    echo $this->lang->translate('The slug already exists, please change and continue.');
                }
                else{
                    $oldslug = $menu['secondary']['slug'];
                    $menu['secondary']['name'] = $param['menuname'];
                    $menu['secondary']['slug'] = $param['slug'];
                    $this->setTake('jswrt-menu', serialize($menu));
                    if($oldslug != $param['slug']){
                        $this->app->db->table('menu')->where('slug', $oldslug)->data('slug', $param['slug'])->update()->finish();
                    }
                    echo 'ok';
                }
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function primarycategoriesmenu()
    {
        if($this->request->isPost() && $this->administrator()){
            $menu = unserialize($this->getTake('jswrt-menu'));
            if(empty($menu['primary']['slug'])){
                echo $this->lang->translate('Please set the menu name and alias first.');
            }
            else{
                $this->app->db->table('categories')->field('id,name,slug,sort,parent')->order('sort ASC')->box('categories')->select()->finish();
                $categories = $this->box->get('categories');
                if(!empty($categories)){
                    $this->app->db->table('menu')->field('id')->order('id DESC')->limit(1)->box('maxid')->find()->finish();
                    $data = [];
                    foreach($categories as $item){
                        $data[] = [
                            'name' => $item['name'],
                            'slug' => $menu['primary']['slug'],
                            'sort' => $item['sort'],
                            'parent' => ($item['parent'] == 0) ? 0 : $item['parent'] + $this->box->get('maxid.id'),
                            'url' => serialize(['method' => 'category', 'id' => $item['id'], 'slug' => $item['slug']])
                        ];
                    }
                    $this->app->db->table('menu')->where('slug', $menu['primary']['slug'])->delete()->table('menu')->data($data)->insert()->finish();
                    echo 'ok';
                }
                else{
                    echo $this->lang->translate('There are no categories that can be used to generate the menu.');
                }
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function secondarycategoriesmenu()
    {
        if($this->request->isPost() && $this->administrator()){
            $menu = unserialize($this->getTake('jswrt-menu'));
            if(empty($menu['secondary']['slug'])){
                echo $this->lang->translate('Please set the menu name and alias first.');
            }
            else{
                $this->app->db->table('categories')->field('id,name,slug,sort,parent')->order('sort ASC')->box('categories')->select()->finish();
                $categories = $this->box->get('categories');
                if(!empty($categories)){
                    $this->app->db->table('menu')->field('id')->order('id DESC')->limit(1)->box('maxid')->find()->finish();
                    $data = [];
                    foreach($categories as $item){
                        $data[] = [
                            'name' => $item['name'],
                            'slug' => $menu['secondary']['slug'],
                            'sort' => $item['sort'],
                            'parent' => ($item['parent'] == 0) ? 0 : $item['parent'] + $this->box->get('maxid.id'),
                            'url' => serialize(['method' => 'category', 'id' => $item['id'], 'slug' => $item['slug']])
                        ];
                    }
                    $this->app->db->table('menu')->where('slug', $menu['secondary']['slug'])->delete()->table('menu')->data($data)->insert()->finish();
                    echo 'ok';
                }
                else{
                    echo $this->lang->translate('There are no categories that can be used to generate the menu.');
                }
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function primarypagesmenu()
    {
        if($this->request->isPost() && $this->administrator()){
            $menu = unserialize($this->getTake('jswrt-menu'));
            if(empty($menu['primary']['slug'])){
                echo $this->lang->translate('Please set the menu name and alias first.');
            }
            else{
                $this->app->db->table('pages')->field('id,title,slug,parent,sort')->order('sort ASC')->box('pages')->select()->finish();
                $pages = $this->box->get('pages');
                if(!empty($pages)){
                    $this->app->db->table('menu')->field('id')->order('id DESC')->limit(1)->box('maxid')->find()->finish();
                    $data = [];
                    foreach($pages as $item){
                        $data[] = [
                            'name' => $item['title'],
                            'slug' => $menu['primary']['slug'],
                            'sort' => $item['sort'],
                            'parent' => ($item['parent'] == 0) ? 0 : $item['parent'] + $this->box->get('maxid.id'),
                            'url' => serialize(['method' => 'page', 'id' => $item['id'], 'slug' => $item['slug']])
                        ];
                    }
                    $this->app->db->table('menu')->where('slug', $menu['primary']['slug'])->delete()->table('menu')->data($data)->insert()->finish();
                    echo 'ok';
                }
                else{
                    echo $this->lang->translate('There is no page available to generate the menu.');
                }
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function secondarypagesmenu()
    {
        if($this->request->isPost() && $this->administrator()){
            $menu = unserialize($this->getTake('jswrt-menu'));
            if(empty($menu['secondary']['slug'])){
                echo $this->lang->translate('Please set the menu name and alias first.');
            }
            else{
                $this->app->db->table('pages')->field('id,title,slug,parent,sort')->order('sort ASC')->box('pages')->select()->finish();
                $pages = $this->box->get('pages');
                if(!empty($pages)){
                    $this->app->db->table('menu')->field('id')->order('id DESC')->limit(1)->box('maxid')->find()->finish();
                    $data = [];
                    foreach($pages as $item){
                        $data[] = [
                            'name' => $item['title'],
                            'slug' => $menu['secondary']['slug'],
                            'sort' => $item['sort'],
                            'parent' => ($item['parent'] == 0) ? 0 : $item['parent'] + $this->box->get('maxid.id'),
                            'url' => serialize(['method' => 'page', 'id' => $item['id'], 'slug' => $item['slug']])
                        ];
                    }
                    $this->app->db->table('menu')->where('slug', $menu['secondary']['slug'])->delete()->table('menu')->data($data)->insert()->finish();
                    echo 'ok';
                }
                else{
                    echo $this->lang->translate('There is no page available to generate the menu.');
                }
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function addmenu($param = '')
    {
        if($this->request->isPost() && $this->administrator()){
            $menutype = $param['menutype'];
            $menu = unserialize($this->getTake('jswrt-menu'));
            if(empty($menu[$menutype]['slug'])){
                echo $this->lang->translate('Please set the menu name and alias first.');
            }
            else{
                $this->app->db->table('menu')->field('id,name,parent')->where('slug', $menu[$menutype]['slug'])->order('sort ASC')->box('menu')->select()->table('categories')->field('id,name,slug,parent')->order('sort ASC')->box('categories')->select()->table('pages')->field('id,title,slug,parent')->order('sort ASC')->box('pages')->select()->check->filter(':box(menu)', 'filterFunc')->filter(':box(categories)', 'filterFunc')->filter(':box(pages)', 'filterFunc')->output->assign('share')->assign('menu', ':box(menu)')->assign('categories', ':box(categories)')->assign('pages', ':box(pages)')->assign('slug', $menu[$menutype]['slug'])->display()->finish();
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function addmenuexec($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the menu must be filled in.'))->check($param['menuurl'], 'require', $this->lang->translate('Menu URL must be selected or filled in.'))->check($this->hascustom($param['menuurl'], $param['custom']), $this->lang->translate('Menu URL must be selected or filled in.'))->check($param['custom'], 'url', $this->lang->translate('The URL format is incorrect.'))
            ->db->table('menu')->insert([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'parent' => intval($param['parent']),
                'icon' => Filter::weedout(str_replace('"', '\'', $param['icon'])),
                'url' => $this->conversionURL($param['menuurl'], $param['custom'])
            ])
            ->output->display(':ok')->finish();
    }
    private function conversionURL($menuurl, $custom)
    {
        if($menuurl == 'custom'){
            if(substr($custom, 0, 4) != 'http'){
                $custom = 'http://' . $custom;
            }
            $data = ['method' => 'custom', 'url' => $custom];
        }
        elseif($menuurl == 'home'){
            $data = ['method' => 'home', 'url' => $this->route->url('/')];
        }
        else{
            $marr = explode('.', $menuurl);
            $data = ['method' => $marr[0], 'id' => $marr[1], 'slug' => $marr[2]];
        }
        return serialize($data);
    }
    private function hascustom($menuurl, $custom)
    {
        if($menuurl == 'custom' && empty($custom)){
            return false;
        }
        return true;
    }
    public function events(Cache $cache)
    {
        if($cache->has('jpwrtevents_cache')){
            $events = $cache->get('jpwrtevents_cache');
        }
        else{
            $events = Distant::get(base64_decode($this->app->getConfig('last') . '=='), [
                'version' => $this->app->getConfig('version'),
                'name' => $this->app->getConfig('siteTitle'),
                'language' => $this->app->getConfig('language'),
                'host' => $this->request->host(),
                'themes' => $this->gethemes(),
                'plugins' => $this->getplugins()
            ]);
            $events = json_decode($events, true);
            $cache->set('jpwrtevents_cache', $events, 604800);
        }
        return $events;
    }
    public function showmenu($param = '')
    {
        if($this->request->isPost() && $this->administrator()){
            $menutype = $param['menutype'];
            $menu = unserialize($this->getTake('jswrt-menu'));
            if(empty($menu[$menutype]['slug'])){
                echo $this->lang->translate('Please set the menu name and alias first.');
            }
            else{
                $this->app->db->table('menu')->field('id,name,sort,parent,icon')->where('slug', $menu[$menutype]['slug'])->order('sort ASC')->box('menu')->select()->check->filter(':box(menu)', 'filterFunc')->output->assign('share')->assign('menu', ':box(menu)')->assign('menutype', $menutype)->display()->finish();
            }
        }
        else{
            echo $this->lang->translate('Insufficient permissions.');
        }
        exit();
    }
    public function ordermenu($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('menu')->where('id', $param['id'])->data('sort', $param['sort'])->update()->output->display(':ok')->finish();
    }
    public function menuicon($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('menu')->where('id', $param['id'])->data('icon', Filter::weedout(str_replace('"', '\'', $param['icon'])))->update()->output->display(':ok')->finish();
    }
    public function deletemenu($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('menu')->field('parent')->where('id', $param['id'])->box('menu')->find()->beginTransaction()->table('menu')->where('id', $param['id'])->delete()->table('menu')->where('parent', $param['id'])->data('parent', ':box(menu.parent)')->update()->endTransaction()->output->display(':ok')->finish();
    }
    private function getplugins()
    {
        $pluginPath = $this->appDir . $this->DS . 'plugins';
        $pluginarr = glob($pluginPath . $this->DS . '*', GLOB_ONLYDIR);
        $rearr = [];
        foreach($pluginarr as $key => $val){
            $plugin = [
                'name' => basename($val),
                'version' => ''
            ];
            $readme = $val . $this->DS . 'readme.txt';
            if(is_file($readme)){
                $lines = file($readme);
                foreach($lines as $lkey => $lval){
                    $pos = strpos($lval, ':');
                    if($pos !== false){
                        $left = strtolower(trim(substr($lval, 0, $pos)));
                        $left = lcfirst(str_replace(' ', '', ucwords($left)));
                        if($left == 'version'){
                            $right = substr($lval, $pos + 1);
                            $plugin['version'] = trim($right);
                            break;
                        }
                    }
                }
            }
            $rearr[] = $plugin;
        }
        return $rearr;
    }
    public function installedplugins()
    {
        if(!$this->administrator()){
            $this->route->redirect('index/fail');
            exit();
        }
        $pluginPath = $this->appDir . $this->DS . 'plugins';
        $pluginarr = glob($pluginPath . $this->DS . '*', GLOB_ONLYDIR);
        $plugins = [];
        $language = $this->app->getConfig('language');
        $pluginconf = $this->app->getConfig('plugins');
        foreach($pluginarr as $key => $val){
            $this->lang->load($val . $this->DS . 'lang' . $this->DS . $language . '.php');
            $pluginfolder = basename($val);
            $plugin['jpwrtplugin'] = $pluginfolder;
            $active = false;
            if(in_array($pluginfolder, $pluginconf)){
                $active = true;
            }
            $readme = $val . $this->DS . 'readme.txt';
            if(is_file($readme)){
                $lines = file($readme);
                foreach($lines as $lkey => $lval){
                    $pos = strpos($lval, ':');
                    if($pos !== false){
                        $left = strtolower(trim(substr($lval, 0, $pos)));
                        $right = substr($lval, $pos + 1);
                        $left = lcfirst(str_replace(' ', '', ucwords($left)));
                        $plugin[$left] = trim($right);
                    }
                }
            }
            if(!isset($plugin['pluginName'])){
                $plugin['pluginName'] = $pluginfolder;
            }
            else{
                $plugin['pluginName'] = $this->lang->translate($plugin['pluginName']);
            }
            if(!isset($plugin['pluginUrl'])){
                $plugin['pluginUrl'] = '';
            }
            if(!isset($plugin['author'])){
                $plugin['author'] = '';
            }
            else{
                $plugin['author'] = $this->lang->translate($plugin['author']);
            }
            if(!isset($plugin['authorUrl'])){
                $plugin['authorUrl'] = '';
            }
            if(!isset($plugin['description'])){
                $plugin['description'] = '';
            }
            else{
                $plugin['description'] = $this->lang->translate($plugin['description']);
            }
            if(!isset($plugin['requiresAtLeast'])){
                $plugin['requiresAtLeast'] = '1.0';
            }
            if(!isset($plugin['requiresPhp'])){
                $plugin['requiresPhp'] = '5.6';
            }
            if(!isset($plugin['version'])){
                $plugin['version'] = '1.0';
            }
            if(!isset($plugin['license'])){
                $plugin['license'] = '';
            }
            else{
                $plugin['license'] = $this->lang->translate($plugin['license']);
            }
            if(!isset($plugin['licenseUrl'])){
                $plugin['licenseUrl'] = '';
            }
            if(!isset($plugin['tags'])){
                $plugin['tags'] = '';
            }
            else{
                $plugin['tags'] = $this->lang->translate($plugin['tags']);
            }
            $plugin['active']= $active;
            if($active){
                array_unshift($plugins, $plugin);
            }
            else{
                $plugins[] = $plugin;
            }
        }
        $this->view->assign('share')->assign('group', 'plugins')->assign('current', 'installedplugins')->assign('plugins', $plugins)->display();
    }
    public function putonplugin($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check->param($param)->run('putonpluginFunc')->output->display(':ok')->finish();
    }
    protected function putonpluginFunc($param)
    {
        $pluginPath = $this->appDir . $this->DS . 'plugins' . $this->DS . $param['plugin'];
        if($param['status'] == 1){
            if(is_dir($pluginPath)){
                $this->handle->run($param['plugin'], 'open');
                $opened = $this->app->getConfig('plugins');
                if(!in_array($param['plugin'], $opened)){
                    $opened[] = $param['plugin'];
                }
                $this->app->writeCustomize('site', 'plugins', $opened);
            }
        }
        elseif($param['status'] == 0){
            if(is_dir($pluginPath)){
                $this->handle->run($param['plugin'], 'close');
            }
            $opened = $this->app->getConfig('plugins');
            foreach($opened as $key => $val){
                if($param['plugin'] == $val){
                    unset($opened[$key]);
                }
            }
            $this->app->writeCustomize('site', 'plugins', $opened);
        }
    }
    public function deleteplugin($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check->param($param)->run('deletepluginFunc')->output->display(':ok')->finish();
    }
    public function deletepluginFunc($param = '')
    {
        $pluginPath = $this->appDir . $this->DS . 'plugins' . $this->DS . $param['plugin'];
        $opened = $this->app->getConfig('plugins');
        if(in_array($param['plugin'], $opened)){
            if(is_dir($pluginPath)){
                $this->handle->run($param['plugin'], 'close');
            }
            foreach($opened as $key => $val){
                if($param['plugin'] == $val){
                    unset($opened[$key]);
                }
            }
            $this->app->writeCustomize('site', 'plugins', $opened);
        }
        $this->delDir($pluginPath);
    }
    private function delDir($dirname)
    {
        if(!is_dir($dirname)){
            return false;
        }
        $items = new \FilesystemIterator($dirname);
        foreach($items as $item){
            if($item->isDir() && !$item->isLink()){
                $this->delDir($item->getPathname());
            }
            else{
                @unlink($item->getPathname());
            }
        }
        @rmdir($dirname);
        return true;
    }
    private function gethemes()
    {
        $themePath = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes';
        $themearr = glob($themePath . $this->DS . '*', GLOB_ONLYDIR);
        $rearr = [];
        foreach($themearr as $key => $val){
            $theme = [
                'name' => basename($val),
                'version' => ''
            ];
            $readme = $val . $this->DS . 'readme.txt';
            if(is_file($readme)){
                $lines = file($readme);
                foreach($lines as $lkey => $lval){
                    $pos = strpos($lval, ':');
                    if($pos !== false){
                        $left = strtolower(trim(substr($lval, 0, $pos)));
                        $left = lcfirst(str_replace(' ', '', ucwords($left)));
                        if($left == 'version'){
                            $right = substr($lval, $pos + 1);
                            $theme['version'] = trim($right);
                            break;
                        }
                    }
                }
            }
            $rearr[] = $theme;
        }
        return $rearr;
    }
    public function themes(Cache $cache, $param)
    {
        if(!$this->administrator()){
            $this->route->redirect('index/fail');
            exit();
        }
        $themePath = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes';
        $template = $this->app->getConfig('template');
        if($this->request->isPost()){
            $newthemefolder = $themePath . $this->DS . $param['theme'];
            if(is_dir($newthemefolder)){
                if(is_file($this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . lcfirst($template) . $this->DS . ucfirst($template) . '.php')){
                    $this->handle->run('jpwrt\theme\\' . lcfirst($template) . '\\' . ucfirst($template), 'close');
                }
                $this->app->writeCustomize('site', 'template', $param['theme']);
                $cache->delete('jpwrtusedtheme');
                if(is_file($this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . lcfirst($param['theme']) . $this->DS . ucfirst($param['theme']) . '.php')){
                    $this->handle->run('jpwrt\theme\\' . lcfirst($param['theme']) . '\\' . ucfirst($param['theme']), 'open');
                }
                echo 'ok';
            }
            else{
                echo $this->lang->translate('No valid theme file was found.');
            }
            exit();
        }
        $themearr = glob($themePath . $this->DS . '*', GLOB_ONLYDIR);
        $themes = [];
        $language = $this->app->getConfig('language');
        foreach($themearr as $key => $val){
            $this->lang->load($val . $this->DS . 'lang' . $this->DS . $language . '.php');
            $themefolder = basename($val);
            $theme['jpwrtheme'] = $themefolder;
            $active = false;
            if($themefolder == $template){
                $active = true;
            }
            $readme = $val . $this->DS . 'readme.txt';
            $screenshot = $val . $this->DS . 'screenshot.png';
            $screenshotfolder = $val . $this->DS . 'screenshot';
            if(is_file($readme)){
                $lines = file($readme);
                foreach($lines as $lkey => $lval){
                    $pos = strpos($lval, ':');
                    if($pos !== false){
                        $left = strtolower(trim(substr($lval, 0, $pos)));
                        $right = substr($lval, $pos + 1);
                        $left = lcfirst(str_replace(' ', '', ucwords($left)));
                        $theme[$left] = trim($right);
                    }
                }
            }
            if(!isset($theme['themeName'])){
                $theme['themeName'] = $themefolder;
            }
            else{
                $theme['themeName'] = $this->lang->translate($theme['themeName']);
            }
            if(!isset($theme['themeUrl'])){
                $theme['themeUrl'] = '';
            }
            if(!isset($theme['author'])){
                $theme['author'] = '';
            }
            else{
                $theme['author'] = $this->lang->translate($theme['author']);
            }
            if(!isset($theme['authorUrl'])){
                $theme['authorUrl'] = '';
            }
            if(!isset($theme['description'])){
                $theme['description'] = '';
            }
            else{
                $theme['description'] = $this->lang->translate($theme['description']);
            }
            if(!isset($theme['requiresAtLeast'])){
                $theme['requiresAtLeast'] = '1.0';
            }
            if(!isset($theme['requiresPhp'])){
                $theme['requiresPhp'] = '5.6';
            }
            if(!isset($theme['version'])){
                $theme['version'] = '1.0';
            }
            if(!isset($theme['license'])){
                $theme['license'] = '';
            }
            else{
                $theme['license'] = $this->lang->translate($theme['license']);
            }
            if(!isset($theme['licenseUrl'])){
                $theme['licenseUrl'] = '';
            }
            if(!isset($theme['tags'])){
                $theme['tags'] = '';
            }
            else{
                $theme['tags'] = $this->lang->translate($theme['tags']);
            }
            if(is_dir($screenshotfolder) && is_file($screenshotfolder . $this->DS . $language . '.png')){
                $theme['screenshot'] = $this->route->rootUrl() . 'public/themes/' . $themefolder . '/screenshot/' . $language . '.png';
            }
            elseif(is_file($screenshot)){
                $theme['screenshot'] = $this->route->rootUrl() . 'public/themes/' . $themefolder . '/screenshot.png';
            }
            else{
                $theme['screenshot'] = $this->route->rootUrl() . 'public/static/screenshot/' . $this->app->getConfig('language') . '.png';
            }
            $theme['active']= $active;
            if($active){
                array_unshift($themes, $theme);
            }
            else{
                $themes[] = $theme;
            }
        }
        $this->view->assign('share')->assign('group', 'appearance')->assign('current', 'themes')->assign('themes', $themes)->display();
    }
    public function allpages()
    {
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('pages')->field('id,title,template,thumbnail,views,createtime,editime,status,parent,sort')->order('sort ASC')->box('pages')->select()->check->filter(':box(pages)', 'filterFunc')->output->assign('share')->assign('group', 'pages')->assign('current', 'allpages')->assign('pages', ':box(pages)')->display()->finish();
    }
    public function editpage($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check($param['title'], 'require', $this->lang->translate('The title of the page must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the page must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))->check($param['content'], 'require', $this->lang->translate('The content of the page must be filled in.'))
            ->db->table('pages')->field('id')->where('slug', $param['slug'])->where('slug', '!=', '')->where('id', '!=', $param['id'])->box('hasslug')->find()
            ->check->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('pages')->where('id', $param['id'])->update([
                'title' => Filter::html($param['title']),
                'slug' => Filter::html($param['slug']),
                'keyword' => Filter::html(str_replace('，', ',', $param['keyword'])),
                'template' => Filter::html($param['template']),
                'editime' => empty($param['date']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($param['date'] . ' ' . $param['time'])),
                'visibility' => Filter::html($param['visibility']),
                'password' => ($param['visibility'] == '1') ? md5($param['password']) : '',
                'status' => ($this->editor() && $param['status'] == 1) ? 3 : intval($param['status']),
                'parent' => intval($param['parent']),
                'summary' => Filter::html($param['summary']),
                'content' => Filter::weedout($param['content'])
            ])
            ->event->listen('editPage', ['id' => $param['id'], 'publish' => ($this->editor() && $param['status'] == 1) ? true : false])->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('pages'))->inbox('id', $param['id'])->db->table('pages')->field('id,title,parent')->box('pages')->order('sort ASC')->select()->table('pages')->field('id,title,slug,keyword,template,thumbnail,editime,visibility,password,status,parent,summary,content')->where('id', $param['id'])->box('page')->find()->check->filter(':box(pages)', 'filternochildFunc')->filter(':box(page)', 'postFunc')->output->assign('share')->assign('group', 'pages')->assign('current', 'allpages')->assign('pages', ':box(pages)')->assign('templates', ':box(templates)')->assign('page', ':box(page)')->display()->finish();
    }
    public function addnewpage($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check($param['title'], 'require', $this->lang->translate('The title of the page must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the page must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))->check($param['content'], 'require', $this->lang->translate('The content of the page must be filled in.'))
            ->db->table('pages')->field('id')->where('slug', $param['slug'])->where('slug', '!=', '')->box('hasslug')->find()
            ->check->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('pages')->box('pageid')->insert([
                'title' => Filter::html($param['title']),
                'slug' => Filter::html($param['slug']),
                'keyword' => Filter::html(str_replace('，', ',', $param['keyword'])),
                'template' => Filter::html($param['template']),
                'thumbnail' => Filter::html($this->session->has('uploadimg') ? $this->session->get('uploadimg') : ''),
                'createtime' => date('Y-m-d H:i:s'),
                'editime' => empty($param['date']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($param['date'] . ' ' . $param['time'])),
                'visibility' => Filter::html($param['visibility']),
                'password' => ($param['visibility'] == '1') ? md5($param['password']) : '',
                'status' => ($this->editor() && $param['status'] == 1) ? 3 : intval($param['status']),
                'parent' => intval($param['parent']),
                'summary' => Filter::html($param['summary']),
                'content' => Filter::weedout($param['content'])
            ])
            ->check->removeSession('uploadimg')->event->listen('addNewPage', ['id' => $this->box->get('pageid'), 'publish' => ($this->editor() && $param['status'] == 1) ? true : false])->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('pages'))->db->table('pages')->field('id,title,parent')->order('sort ASC')->box('pages')->select()->check->filter(':box(pages)', 'filterFunc')->removeSession('uploadimg')->output->assign('share')->assign('group', 'pages')->assign('current', 'addnewpage')->assign('pages', ':box(pages)')->assign('templates', ':box(templates)')->display()->finish();
    }
    public function deletepage($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('pages')->field('thumbnail,parent,content')->where('id', $param['id'])->box('page')->find()->beginTransaction()->table('pages')->where('id', $param['id'])->delete()->table('pages')->where('parent', $param['id'])->data('parent', ':box(page.parent)')->update()->endTransaction()->check->run('delpageimgFunc', ':box(transactionIsOk)', true)->output->display(':ok')->finish();
    }
    public function orderpages($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('pages')->where('id', $param['id'])->data('sort', $param['sort'])->update()->output->display(':ok')->finish();
    }
    public function recyclebin()
    {
        if($this->editor()){
            $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->field('id,title,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->where('trash', 1)->orWhere('trashuid', $this->session->get('id'))->order('id DESC')->join('uid = id')->table('users')->field('username')->join('posts.cid = id')->table('categories')->field('name')->paging($this->per)->box('posts')->endJoin()
                ->output->assign('share')->assign('group', 'posts')->assign('current', 'recyclebin')->assign('posts', ':box(posts)')->display()->finish();
        }
        else{
            $this->app->entrance->check('get')->check($this->author(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->where('uid', $this->session->get('id'))->where('trash', 1)->field('id,title,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->order('id DESC')->join('uid = id')->table('users')->field('username')->join('posts.cid = id')->table('categories')->field('name')->paging($this->per)->box('posts')->endJoin()
                ->output->assign('share')->assign('group', 'posts')->assign('current', 'recyclebin')->assign('posts', ':box(posts)')->display()->finish();
        }
    }
    public function deletepermanently($param = '')
    {
        if($this->editor()){
            $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('posts')->field('id,uid,thumbnail,content')->where('id', $param['id'])->data('trash', 1)->data('trashuid', $this->session->get('id'))->box('post')->find()->db->table('postags')->where('pid', $param['id'])->field('id,tid')->box('postags')->select()->check->stop(':box(post)', '=', 'empty', $this->lang->translate('Insufficient permissions.'))->filter(':box(postags)', 'postagsFunc')->db->beginTransaction()->table('posts')->where('id', $param['id'])->box('delpost')->delete()->table('postags')->where('pid', $param['id'])->delete()->table('tags')->where('id', 'IN', ':box(postags)')->data('quantity', 'quantity-1')->update()->table('users')->where('id', ':box(post.uid)')->data('posts', 'posts-1')->update()->endTransaction()->check->run('delpostimgFunc', ':box(transactionIsOk)', true)->output->display(':ok')->finish();
        }
        else{
            $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('posts')->field('id,uid,thumbnail,content')->where('id', $param['id'])->where('uid', $this->session->get('id'))->data('trash', 1)->box('post')->find()->db->table('postags')->where('pid', $param['id'])->field('id,tid')->box('postags')->select()->check->stop(':box(post)', '=', 'empty', $this->lang->translate('Insufficient permissions.'))->filter(':box(postags)', 'postagsFunc')->db->beginTransaction()->table('posts')->where('id', $param['id'])->delete()->table('postags')->where('pid', $param['id'])->delete()->table('tags')->where('id', 'IN', ':box(postags)')->data('quantity', 'quantity-1')->update()->table('users')->where('id', ':box(post.uid)')->data('posts', 'posts-1')->update()->endTransaction()->check->run('delpostimgFunc', ':box(transactionIsOk)', true)->output->display(':ok')->finish();
        }
    }
    protected function postagsFunc($param)
    {
        $idstr = '';
        foreach($param as $item){
            $idstr .= empty($idstr) ? $item['tid'] : ',' . $item['tid'];
        }
        return $idstr;
    }
    protected function delpostimgFunc()
    {
        if($this->box->has('post.thumbnail')){
            $imgpath = $this->box->get('post.thumbnail');
            $imgpath = $this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath);
            if(is_file($imgpath)){
                @unlink($imgpath);
            }
        }
    }
    protected function delpageimgFunc()
    {
        if($this->box->has('page.thumbnail')){
            $imgpath = $this->box->get('page.thumbnail');
            $imgpath = $this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath);
            if(is_file($imgpath)){
                @unlink($imgpath);
            }
        }
    }
    public function restore($param = '')
    {
        if($this->editor()){
            $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('id', $param['id'])->data('trash', 0)->data('trashuid', 0)->update()->check->deleteCacheTag('posts' . $param['id'])->output->display(':ok')->finish();
        }
        else{
            $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->field('id')->where('id', $param['id'])->where('uid', $this->session->get('id'))->box('hasmy')->find()->check->stop(':box(hasmy)', '==', 'empty', $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('id', $param['id'])->data('trash', 0)->data('trashuid', 0)->update()->check->deleteCacheTag('posts' . $param['id'])->output->display(':ok')->finish();
        }
    }
    public function totrash($param = '')
    {
        if($this->editor()){
            $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('id', $param['id'])->data('trash', 1)->data('trashuid', $this->session->get('id'))->update()->check->deleteCacheTag('posts' . $param['id'])->output->display(':ok')->finish();
        }
        else{
            $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->field('id')->where('id', $param['id'])->where('uid', $this->session->get('id'))->box('hasmy')->find()->check->stop(':box(hasmy)', '==', 'empty', $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('id', $param['id'])->data('trash', 1)->data('trashuid', $this->session->get('id'))->update()->check->deleteCacheTag('posts' . $param['id'])->output->display(':ok')->finish();
        }
    }
    public function allposts($param = '')
    {
		unset($param['page']);
        if($this->editor()){
            if(empty($param)){
                $param = [
                    'keywords' => '',
                    'author' => '',
                    'categories' => 0,
                    'startdate' => '',
                    'enddate' => ''
                ];
                $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->field('id,uid,cid,title,slug,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->where('trash', 0)->whereAnd('uid', $this->session->get('id'))->whereOr('uid', '!=', $this->session->get('id'))->where('status', '>', 0)->where('visibility', '<', 2)->order('id DESC')->leftJoin('uid = id')->table('users')->field('username')->leftJoin('posts.cid = id')->table('categories')->field('name,slug as catslug')->paging($this->per)->box('posts')->endJoin()->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->cache(600, 'bgcategories')->select()->check->filter(':box(posts)', 'allpostsFunc')->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('posts', ':box(posts)')->assign('categories', ':box(categories)')->assign('search', $param)->assign('isearch', false)->display()->finish();
            }
            else{
                $agparam = $this->arrangeparam($param);
                if($agparam['categories'] == 0){
                    $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->field('id,uid,cid,title,slug,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->where('trash', 0)->where('title', 'LIKE', '%' . $agparam['keywords'] . '%')->where('createtime', 'BETWEEN', [$agparam['startdate'], $agparam['enddate']])->whereAnd('uid', $this->session->get('id'))->whereOr('uid', '!=', $this->session->get('id'))->where('status', '>', 0)->where('visibility', '<', 2)->order('id DESC')->leftJoin('uid = id')->table('users')->field('username')->where('username', 'LIKE', '%' . $agparam['author'] . '%')->leftJoin('posts.cid = id')->table('categories')->field('name,slug as catslug')->paging($this->per)->box('posts')->endJoin()->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->cache(600, 'bgcategories')->select()->check->filter(':box(posts)', 'allpostsFunc')->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('posts', ':box(posts)')->assign('categories', ':box(categories)')->assign('search', $param)->assign('isearch', true)->display()->finish();
                }
                else{
                    $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->field('id,uid,cid,title,slug,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->where('trash', 0)->where('cid', $agparam['categories'])->where('title', 'LIKE', '%' . $agparam['keywords'] . '%')->where('createtime', 'BETWEEN', [$agparam['startdate'], $agparam['enddate']])->whereAnd('uid', $this->session->get('id'))->whereOr('uid', '!=', $this->session->get('id'))->where('status', '>', 0)->where('visibility', '<', 2)->order('id DESC')->leftJoin('uid = id')->table('users')->field('username')->where('username', 'LIKE', '%' . $agparam['author'] . '%')->leftJoin('posts.cid = id')->table('categories')->field('name,slug as catslug')->paging($this->per)->box('posts')->endJoin()->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->cache(600, 'bgcategories')->select()->check->filter(':box(posts)', 'allpostsFunc')->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('posts', ':box(posts)')->assign('categories', ':box(categories)')->assign('search', $param)->assign('isearch', true)->display()->finish();
                }
            }
        }
        else{
            if(empty($param)){
                $param = [
                    'keywords' => '',
                    'author' => '',
                    'categories' => 0,
                    'startdate' => '',
                    'enddate' => ''
                ];
                $this->app->entrance->check('get')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->where('uid', $this->session->get('id'))->where('trash', 0)->field('id,uid,cid,title,slug,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->order('id DESC')->leftJoin('uid = id')->table('users')->field('username')->leftJoin('posts.cid = id')->table('categories')->field('name,slug as catslug')->paging($this->per)->box('posts')->endJoin()->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->cache(600, 'bgcategories')->select()->check->filter(':box(posts)', 'allpostsFunc')->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('posts', ':box(posts)')->assign('categories', ':box(categories)')->assign('search', $param)->assign('isearch', false)->display()->finish();
            }
            else{
                $agparam = $this->arrangeparam($param);
                if($agparam['categories'] == 0){
                    $this->app->entrance->check('get')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->where('uid', $this->session->get('id'))->where('trash', 0)->where('title', 'LIKE', '%' . $agparam['keywords'] . '%')->where('createtime', 'BETWEEN', [$agparam['startdate'], $agparam['enddate']])->field('id,uid,cid,title,slug,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->order('id DESC')->leftJoin('uid = id')->table('users')->field('username')->leftJoin('posts.cid = id')->table('categories')->field('name,slug as catslug')->paging($this->per)->box('posts')->endJoin()->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->cache(600, 'bgcategories')->select()->check->filter(':box(posts)', 'allpostsFunc')->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('posts', ':box(posts)')->assign('categories', ':box(categories)')->assign('search', $param)->assign('isearch', true)->display()->finish();
                }
                else{
                    $this->app->entrance->check('get')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->alias('posts')->where('uid', $this->session->get('id'))->where('trash', 0)->where('cid', $agparam['categories'])->where('title', 'LIKE', '%' . $agparam['keywords'] . '%')->where('createtime', 'BETWEEN', [$agparam['startdate'], $agparam['enddate']])->field('id,uid,cid,title,slug,thumbnail,comment,views,createtime,editime,recommend,top,commentsoff,visibility,status')->order('id DESC')->leftJoin('uid = id')->table('users')->field('username')->leftJoin('posts.cid = id')->table('categories')->field('name,slug as catslug')->paging($this->per)->box('posts')->endJoin()->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->cache(600, 'bgcategories')->select()->check->filter(':box(posts)', 'allpostsFunc')->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('posts', ':box(posts)')->assign('categories', ':box(categories)')->assign('search', $param)->assign('isearch', true)->display()->finish();
                }
            }
        }
    }
    private function arrangeparam($param)
    {
        if(empty($param['startdate'])){
            $param['startdate'] = $this->app->getConfig('creationtime');
        }
        else{
            $param['startdate'] = date('Y-m-d H:i:s', strtotime($param['startdate']));
        }
        if(empty($param['enddate'])){
            $param['enddate'] = date('Y-m-d H:i:s');
        }
        else{
            $param['enddate'] = date('Y-m-d H:i:s', strtotime($param['enddate']));
        }
        return $param;
    }
    protected function allpostsFunc($param)
    {
        foreach($param['data'] as $key => $val){
            $param['data'][$key]['year'] = date('Y', strtotime($val['createtime']));
            $param['data'][$key]['month'] = date('m', strtotime($val['createtime']));
            $param['data'][$key]['day'] = date('d', strtotime($val['createtime']));
        }
        return $param;
    }
    public function review($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('id', $param['id'])->data('status', $param['review'] == 1 ? 3 : 2)->update()->event->listen('review', ['id' => $param['id'], 'review' => $param['review'] == 1 ? true : false])->output->display(':ok')->finish();
    }
    public function putontop($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('id', $param['id'])->where('status', 3)->data('top', $param['istop'])->update()->output->display(':ok')->finish();
    }
    public function recommended($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->where('id', $param['id'])->where('status', 3)->data('recommend', $param['recommended'])->update()->output->display(':ok')->finish();
    }
    public function editpost($param = '')
    {
        if($this->editor()){
            !$this->request->isPost() || $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['title'], 'require', $this->lang->translate('The title of the post must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the post must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))->check($param['content'], 'require', $this->lang->translate('The content of the post must be filled in.'))->check($param['category'], 'require', $this->lang->translate('The category must be selected.'))->inbox('tags', $param['tags'])->inbox('oldtags', $param['oldtags'])->inbox('postid', $param['id'])
                ->db->table('posts')->field('id')->where('slug', $param['slug'])->where('slug', '!=', '')->where('id', '!=', $param['id'])->box('hasslug')->find()
                ->check->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
                ->db->table('posts')->where('id', $param['id'])->box('updateok')->update([
                    'cid' => intval($param['category']),
                    'title' => Filter::html($param['title']),
                    'slug' => Filter::html($param['slug']),
                    'keyword' => Filter::html($param['tags']),
                    'template' => Filter::html($param['template']),
                    'editime' => empty($param['date']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($param['date'] . ' ' . $param['time'])),
                    'recommend' => (isset($param['recommend']) && $param['recommend'] == 'on') ? 1 : 0,
                    'top' => (isset($param['stick']) && $param['stick'] == 'on') ? 1 : 0,
                    'visibility' => Filter::html($param['visibility']),
                    'password' => ($param['visibility'] == '1') ? md5($param['password']) : '',
                    'status' => ($this->editor() && $param['status'] == 1) ? 3 : intval($param['status']),
                    'summary' => Filter::html($param['summary']),
                    'content' => Filter::weedout($param['content'])
                ])
                ->check->run('edittagsFunc', ':box(updateok)', '>', 0)->deleteCacheTag('posts' . $param['id'])->deleteCache('totime')->event->listen('editPost', ['id' => $param['id'], 'publish' => ($this->editor() && $param['status'] == 1) ? true : false])->output->display(':ok')->finish();
            $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('archives'))->db->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->select()->table('posts')->field('id,cid,title,slug,keyword,template,source,thumbnail,editime,recommend,top,visibility,password,status,summary,content')->where('id', $param['id'])->box('post')->find()->check->filter(':box(categories)', 'filterFunc')->filter(':box(post)', 'postFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('categories', ':box(categories)')->assign('templates', ':box(templates)')->assign('post', ':box(post)')->display()->finish();
        }
        else{
            !$this->request->isPost() || $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check($param['title'], 'require', $this->lang->translate('The title of the post must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the post must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))->check($param['content'], 'require', $this->lang->translate('The content of the post must be filled in.'))->check($param['category'], 'require', $this->lang->translate('The category must be selected.'))->inbox('tags', $param['tags'])->inbox('oldtags', $param['oldtags'])->inbox('postid', $param['id'])
                ->db->table('posts')->field('id')->where('slug', $param['slug'])->where('slug', '!=', '')->where('id', '!=', $param['id'])->box('hasslug')->find()
                ->check->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
                ->db->table('posts')->where('id', $param['id'])->where('uid', $this->session->get('id'))->box('updateok')->update([
                    'cid' => intval($param['category']),
                    'title' => Filter::html($param['title']),
                    'slug' => Filter::html($param['slug']),
                    'keyword' => Filter::html($param['tags']),
                    'template' => Filter::html($param['template']),
                    'editime' => empty($param['date']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($param['date'] . ' ' . $param['time'])),
                    'recommend' => (isset($param['recommend']) && $param['recommend'] == 'on') ? 1 : 0,
                    'top' => (isset($param['stick']) && $param['stick'] == 'on') ? 1 : 0,
                    'visibility' => Filter::html($param['visibility']),
                    'password' => ($param['visibility'] == '1') ? md5($param['password']) : '',
                    'status' => intval($param['status']),
                    'summary' => Filter::html($param['summary']),
                    'content' => Filter::weedout($param['content'])
                ])
                ->check->run('edittagsFunc', ':box(updateok)', '>', 0)->deleteCacheTag('posts' . $param['id'])->event->listen('editPost', ['id' => $param['id'], 'publish' => ($param['status'] == 3) ? true : false])->output->display(':ok')->finish();
            $this->app->entrance->check('get')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('archives'))->db->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->select()->table('posts')->field('id,cid,title,slug,keyword,template,source,thumbnail,editime,recommend,top,visibility,password,status,summary,content')->where('id', $param['id'])->where('uid', $this->session->get('id'))->box('post')->find()->check->stop(':box(post)', '=', 'empty', $this->lang->translate('Insufficient permissions.'))->filter(':box(categories)', 'filterFunc')->filter(':box(post)', 'postFunc')->output->assign('share')->assign('group', 'posts')->assign('current', 'allposts')->assign('categories', ':box(categories)')->assign('templates', ':box(templates)')->assign('post', ':box(post)')->display()->finish();
        }
    }
    protected function postFunc($param)
    {
        $param['date'] = date('Y-m-d', strtotime($param['editime']));
        $param['time'] = date('H:i', strtotime($param['editime']));
        $param['content'] = str_replace('&', '&amp;', $param['content']);
        return $param;
    }
    public function addnewpost($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check($param['title'], 'require', $this->lang->translate('The title of the post must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the post must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))->check(!$this->inreserved($param['slug']), $this->lang->translate('The slug is not available, please change and continue.'))->check($param['content'], 'require', $this->lang->translate('The content of the post must be filled in.'))->check($param['category'], 'require', $this->lang->translate('The category must be selected.'))->inbox('tags', isset($param['tags']) ? $param['tags'] : '')
            ->db->table('posts')->field('id')->where('slug', $param['slug'])->where('slug', '!=', '')->box('hasslug')->find()
            ->check->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->beginTransaction()->table('posts')->box('postid')->insert([
                'uid' => $this->session->get('id'),
                'cid' => intval($param['category']),
                'title' => Filter::html($param['title']),
                'slug' => Filter::html($param['slug']),
                'keyword' => Filter::html(isset($param['tags']) ? $param['tags'] : ''),
                'template' => Filter::html(isset($param['template']) ? $param['template'] : ''),
                'thumbnail' => Filter::html($this->session->has('uploadimg') ? $this->session->get('uploadimg') : ''),
                'createtime' => date('Y-m-d H:i:s'),
                'editime' => empty($param['date']) ? date('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime($param['date'] . ' ' . $param['time'])),
                'recommend' => (isset($param['recommend']) && $param['recommend'] == 'on') ? 1 : 0,
                'top' => (isset($param['stick']) && $param['stick'] == 'on') ? 1 : 0,
                'visibility' => Filter::html($param['visibility']),
                'password' => ($param['visibility'] == '1') ? md5($param['password']) : '',
                'status' => ($this->editor() && $param['status'] == 1) ? 3 : intval($param['status']),
                'summary' => Filter::html($param['summary']),
                'content' => Filter::weedout($param['content'])
            ])->table('users')->where('id', $this->session->get('id'))->data('posts', 'posts+1')->update()->endTransaction()
            ->check->removeSession('uploadimg')->deleteCacheTag('home')->deleteCache('totime')->run('tagsFunc')->event->listen('addNewPost', ['id' => $this->box->get('postid'), 'publish' => ($this->editor() && $param['status'] == 1) ? true : false])->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('archives'))->db->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->select()->check->filter(':box(categories)', 'filterFunc')->removeSession('uploadimg')->output->assign('share')->assign('group', 'posts')->assign('current', 'addnewpost')->assign('categories', ':box(categories)')->assign('templates', ':box(templates)')->display()->finish();
    }
    private function inreserved($slug)
    {
        if(!empty($slug) && (in_array(strtolower($slug), Tools::arraytolower($this->app->getConfig('controller'))) || in_array(strtolower($slug), Tools::arraytolower($this->app->getConfig('alone'))))){
            return true;
        }
        return false;
    }
    protected function edittagsFunc()
    {
        $tags = $this->box->get('tags');
        $tagarr = explode(',', $tags);
        $oldtags = $this->box->get('oldtags');
        $oldtagsarr = explode(',', $oldtags);
        $newtags = [];
        foreach($tagarr as $tag){
            if(!in_array($tag, $oldtagsarr)){
                $newtags[] = $tag;
            }
        }
        $deltags = [];
        foreach($oldtagsarr as $tag){
            if(!in_array($tag, $tagarr)){
                $deltags[] = $tag;
            }
        }
        foreach($newtags as $tag){
            $this->app->entrance->inbox('tagname', $tag)->db->table('tags')->where('name', $tag)->field('id')->box('hasname')->find()->check->run('hastagFunc', ':box(hasname)', '!=', 'empty')->run('notagFunc', ':box(hasname)', 'empty')->finish();
        }
        foreach($deltags as $tag){
            $this->app->db->table('tags')->where('name', $tag)->field('id')->box('hasname')->find()->table('tags')->where('id', ':box(hasname.id)')->data('quantity', 'quantity-1')->update()->table('postags')->where('pid', $this->box->get('postid'))->where('tid', ':box(hasname.id)')->delete()->finish();
        }
    }
    protected function tagsFunc()
    {
        $tags = $this->box->get('tags');
        if(!empty($tags)){
            $tagarr = explode(',', $tags);
            foreach($tagarr as $tag){
                $this->app->entrance->inbox('tagname', $tag)->db->table('tags')->where('name', $tag)->field('id')->box('hasname')->find()->check->run('hastagFunc', ':box(hasname)', '!=', 'empty')->run('notagFunc', ':box(hasname)', 'empty')->finish();
            }
        }
    }
    protected function hastagFunc()
    {
        $this->app->db->table('tags')->where('id', ':box(hasname.id)')->data('quantity', 'quantity+1')->update()->table('postags')->where('pid', $this->box->get('postid'))->where('tid', ':box(hasname.id)')->field('id')->box('haspostags')->find()->check->stop(':box(haspostags)', '!=', 'empty')->db->table('postags')->data('pid', ':box(postid)')->data('tid', ':box(hasname.id)')->insert()->finish();
    }
    protected function notagFunc()
    {
        $this->app->db->table('tags')->data('name', ':box(tagname)')->data('quantity', 1)->box('tagid')->insert()->table('postags')->data('pid', ':box(postid)')->data('tid', ':box(tagid)')->insert()->finish();
    }
    public function heupload()
    {
        $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->output->display($this->route->rootUrl() . $this->box->get('imgname'))->finish();
    }
    public function heuploadeditor()
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->output->display($this->route->rootUrl() . $this->box->get('imgname'))->finish();
    }
    public function uploadimgslide()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->check->session('uploadimg', ':box(imgname)')->output->display(':box(imgname)')->finish();
    }
    public function uploadimglink()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut(0, $this->linkheight, '', 'adaptRaw')->check->session('uploadimg', ':box(imgname)')->output->display(':box(imgname)')->finish();
    }
    public function uploadimg()
    {
        $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut($this->getTake('thumbnail-width'), $this->getTake('thumbnail-height'), $this->rootDir . $this->DS . str_replace('.', '_sm.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('large-width'), $this->getTake('large-height'), $this->rootDir . $this->DS . str_replace('.', '_lg.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('medium-width'), $this->getTake('medium-height'), '', 'adaptRaw')->check->session('uploadimg', ':box(imgname)')->output->display(':box(imgname)')->finish();
    }
    public function uploadimgeditor()
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut($this->getTake('thumbnail-width'), $this->getTake('thumbnail-height'), $this->rootDir . $this->DS . str_replace('.', '_sm.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('large-width'), $this->getTake('large-height'), $this->rootDir . $this->DS . str_replace('.', '_lg.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('medium-width'), $this->getTake('medium-height'), '', 'adaptRaw')->check->session('uploadimg', ':box(imgname)')->output->display(':box(imgname)')->finish();
    }
    public function deleteimgslide()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check->run('deleteimgslideFunc')->output->display(':ok')->finish();
    }
    public function deleteimglink()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check->run('deleteimgslideFunc')->output->display(':ok')->finish();
    }
    protected function deleteimgslideFunc()
    {
        $imgpath = $this->session->get('uploadimg');
        if(!empty($imgpath)){
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
        }
        $this->session->remove('uploadimg');
    }
    public function deleteimg()
    {
        $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->check->run('deleteimgFunc')->output->display(':ok')->finish();
    }
    public function deleteimgeditor()
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check->run('deleteimgFunc')->output->display(':ok')->finish();
    }
    public function uploadimgeditpage($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('pages')->field('id,thumbnail')->where('id', $param['id'])->box('pages')->find()->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut($this->getTake('thumbnail-width'), $this->getTake('thumbnail-height'), $this->rootDir . $this->DS . str_replace('.', '_sm.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('large-width'), $this->getTake('large-height'), $this->rootDir . $this->DS . str_replace('.', '_lg.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('medium-width'), $this->getTake('medium-height'), '', 'adaptRaw')->db->table('pages')->where('id', $param['id'])->data('thumbnail', ':box(imgname)')->update()->check->run('deleteuploadimgeditpageFunc')->output->display(':box(imgname)')->finish();
    }
    public function uploadimgedit($param = '')
    {
        if($this->editor()){
            $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->field('id,uid,thumbnail')->where('id', $param['id'])->box('post')->find()->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut($this->getTake('thumbnail-width'), $this->getTake('thumbnail-height'), $this->rootDir . $this->DS . str_replace('.', '_sm.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('large-width'), $this->getTake('large-height'), $this->rootDir . $this->DS . str_replace('.', '_lg.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('medium-width'), $this->getTake('medium-height'), '', 'adaptRaw')->db->table('posts')->where('id', $param['id'])->data('thumbnail', ':box(imgname)')->update()->check->run('deleteuploadimgeditFunc')->output->display(':box(imgname)')->finish();
        }
        else{
            $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->field('id,uid,thumbnail')->where('id', $param['id'])->where('uid', $this->session->get('id'))->box('post')->find()->check->stop(':box(post)', '=', 'empty', $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut($this->getTake('thumbnail-width'), $this->getTake('thumbnail-height'), $this->rootDir . $this->DS . str_replace('.', '_sm.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('large-width'), $this->getTake('large-height'), $this->rootDir . $this->DS . str_replace('.', '_lg.', $this->box->get('imgname')), 'adaptRaw')->cut($this->getTake('medium-width'), $this->getTake('medium-height'), '', 'adaptRaw')->db->table('posts')->where('id', $param['id'])->data('thumbnail', ':box(imgname)')->update()->check->run('deleteuploadimgeditFunc')->output->display(':box(imgname)')->finish();
        }
    }
    protected function deleteuploadimgeditpageFunc()
    {
        if($this->box->has('pages.thumbnail')){
            $imgpath = $this->box->get('pages.thumbnail');
            if(!empty($imgpath)){
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            }
        }
    }
    protected function deleteuploadimgeditFunc()
    {
        if($this->box->has('post.thumbnail')){
            $imgpath = $this->box->get('post.thumbnail');
            if(!empty($imgpath)){
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            }
        }
    }
    public function deleteimgeditpage($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('pages')->field('id,thumbnail')->where('id', $param['id'])->box('pages')->find()->check->run('deleteimgeditpageFunc')->output->display(':ok')->finish();
    }
    protected function deleteimgeditpageFunc()
    {
        $isdel = false;
        $imgpath = $this->box->get('pages.thumbnail');
        if(!empty($imgpath)){
            $sm =str_replace('.', '_sm.', $imgpath);
            $lg =str_replace('.', '_lg.', $imgpath);
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $sm));
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $lg));
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            $isdel = true;
        }
        if($isdel){
            $this->app->db->table('pages')->where('id', $this->box->get('pages.id'))->data('thumbnail', '')->update()->finish();
        }
    }
    public function deleteimgedit($param = '')
    {
        $this->app->entrance->check('post')->check($this->contributor(), $this->lang->translate('Insufficient permissions.'))->db->table('posts')->field('id,uid,thumbnail')->where('id', $param['id'])->box('post')->find()->check->run('deleteimgeditFunc')->output->display(':ok')->finish();
    }
    protected function deleteimgeditFunc()
    {
        $isdel = false;
        $imgpath = $this->box->get('post.thumbnail');
        if($this->editor()){
            if(!empty($imgpath)){
                $sm =str_replace('.', '_sm.', $imgpath);
                $lg =str_replace('.', '_lg.', $imgpath);
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $sm));
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $lg));
                @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
                $isdel = true;
            }
        }
        else{
            if($this->session->get('id') == $this->box->get('post.uid')){
                if(!empty($imgpath)){
                    @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
                    $isdel = true;
                }
            }
        }
        if($isdel){
            $this->app->db->table('posts')->where('id', $this->box->get('post.id'))->data('thumbnail', '')->update()->finish();
        }
    }
    protected function deleteimgFunc()
    {
        $imgpath = $this->session->get('uploadimg');
        if(!empty($imgpath)){
            $sm =str_replace('.', '_sm.', $imgpath);
            $lg =str_replace('.', '_lg.', $imgpath);
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $sm));
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $lg));
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
        }
        $this->session->remove('uploadimg');
    }
    public function addnewtag($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the tag must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the tag must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))
            ->db->table('tags')->field('id')->where('name', $param['name'])->box('hasname')->find()
            ->db->table('tags')->field('id')->where('slug', $param['slug'])->box('hasslug')->find()
            ->check->stop(':box(hasname)', '!=', 'empty', $this->lang->translate('The tag name already exists, please change and continue.'))
            ->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('tags')->insert([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'keyword' => isset($param['keyword']) ? Filter::html($param['keyword']) : Filter::html($param['name']),
                'template' => Filter::html($param['template']),
                'description' => Filter::html($param['description'])
            ])
            ->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('tags'))->output->assign('share')->assign('group', 'tags')->assign('current', 'addnewtag')->assign('templates', ':box(templates)')->display()->finish();
    }
    public function alltags($param = '')
    {
		unset($param['page']);
        if(empty($param)){
            $param = [
                'keywords' => '',
                'slug' => ''
            ];
            $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('tags')->field('id,name,slug,template,quantity,description')->order('id DESC')->paging($this->per)->box('tags')->select()->output->assign('share')->assign('group', 'tags')->assign('current', 'alltags')->assign('tags', ':box(tags)')->assign('search', $param)->assign('isearch', false)->display()->finish();
        }
        else{
            $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('tags')->field('id,name,slug,template,quantity,description')->where('name', 'LIKE', '%' . $param['keywords'] . '%')->where('slug', 'LIKE', '%' . $param['slug'] . '%')->order('id DESC')->paging($this->per)->box('tags')->select()->output->assign('share')->assign('group', 'tags')->assign('current', 'alltags')->assign('tags', ':box(tags)')->assign('search', $param)->assign('isearch', true)->display()->finish();
        }
    }
    public function deletetag($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->beginTransaction()->table('tags')->where('id', $param['id'])->delete()->table('postags')->where('tid', $param['id'])->delete()->endTransaction()->output->display(':ok')->finish();
    }
    public function edittag($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the tag must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the tag must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))
            ->db->table('tags')->field('id')->where('name', $param['name'])->where('id', '!=', $param['id'])->box('hasname')->find()
            ->db->table('tags')->field('id')->where('slug', $param['slug'])->where('id', '!=', $param['id'])->box('hasslug')->find()
            ->check->stop(':box(hasname)', '!=', 'empty', $this->lang->translate('The tag name already exists, please change and continue.'))
            ->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('tags')->where('id', $param['id'])->update([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'keyword' => isset($param['keyword']) ? Filter::html($param['keyword']) : Filter::html($param['name']),
                'template' => Filter::html($param['template']),
                'description' => Filter::html($param['description'])
            ])
            ->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('tags'))->db->table('tags')->field('id,name,slug,keyword,template,description')->where('id', $param['id'])->box('tag')->find()->output->assign('share')->assign('group', 'tags')->assign('current', 'addnewtag')->assign('templates', ':box(templates)')->assign('tag', ':box(tag)')->display()->finish();
    }
    public function addnewcategory($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the category must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the category must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))
            ->db->table('categories')->field('id')->where('name', $param['name'])->box('hasname')->find()
            ->db->table('categories')->field('id')->where('slug', $param['slug'])->box('hasslug')->find()
            ->check->stop(':box(hasname)', '!=', 'empty', $this->lang->translate('The category name already exists, please change and continue.'))
            ->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('categories')->insert([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'keyword' => isset($param['keyword']) ? Filter::html($param['keyword']) : Filter::html($param['name']),
                'template' => Filter::html($param['template']),
                'parent' => intval($param['parent']),
                'description' => Filter::html($param['description'])
            ])
            ->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('categories'))->db->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->select()->check->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'categories')->assign('current', 'addnewcategory')->assign('categories', ':box(categories)')->assign('templates', ':box(templates)')->display()->finish();
    }
    public function addfirstcategory($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the category must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the category must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))
            ->db->table('categories')->field('id')->where('name', $param['name'])->box('hasname')->find()
            ->db->table('categories')->field('id')->where('slug', $param['slug'])->box('hasslug')->find()
            ->check->stop(':box(hasname)', '!=', 'empty', $this->lang->translate('The category name already exists, please change and continue.'))
            ->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('categories')->box('categoryid')->insert([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'keyword' => isset($param['keyword']) ? Filter::html($param['keyword']) : Filter::html($param['name']),
                'description' => Filter::html($param['description'])
            ])
            ->output->display(':ok', ['id' => ':box(categoryid)'])->finish();
    }
    public function allcategories()
    {
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->db->table('categories')->field('id,name,slug,template,description,sort,parent')->order('sort ASC')->box('categories')->select()->check->filter(':box(categories)', 'filterFunc')->output->assign('share')->assign('group', 'categories')->assign('current', 'allcategories')->assign('categories', ':box(categories)')->display()->finish();
    }
    public function editcategory($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['name'], 'require', $this->lang->translate('The name of the category must be filled in.'))->check($param['slug'], 'require', $this->lang->translate('The slug of the category must be filled in.'))->check($param['slug'], 'alphadash', $this->lang->translate('The slug can only contain letters, numbers and hyphens.'))
            ->db->table('categories')->field('id')->where('name', $param['name'])->where('id', '!=', $param['id'])->box('hasname')->find()
            ->db->table('categories')->field('id')->where('slug', $param['slug'])->where('id', '!=', $param['id'])->box('hasslug')->find()
            ->check->stop(':box(hasname)', '!=', 'empty', $this->lang->translate('The category name already exists, please change and continue.'))
            ->stop(':box(hasslug)', '!=', 'empty', $this->lang->translate('The slug already exists, please change and continue.'))
            ->db->table('categories')->where('id', $param['id'])->update([
                'name' => Filter::html($param['name']),
                'slug' => Filter::html($param['slug']),
                'keyword' => isset($param['keyword']) ? Filter::html($param['keyword']) : Filter::html($param['name']),
                'template' => Filter::html($param['template']),
                'parent' => intval($param['parent']),
                'description' => Filter::html($param['description'])
            ])
            ->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->inbox('templates', $this->gettemplate('categories'))->inbox('id', $param['id'])->db->table('categories')->field('id,name,parent')->order('sort ASC')->box('categories')->select()->table('categories')->field('id,name,slug,keyword,template,description,parent')->where('id', $param['id'])->box('category')->find()->check->filter(':box(categories)', 'filternochildFunc')->output->assign('share')->assign('group', 'categories')->assign('current', 'addnewcategory')->assign('categories', ':box(categories)')->assign('category', ':box(category)')->assign('templates', ':box(templates)')->display()->finish();
    }
    public function deletecategory($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('categories')->field('parent')->where('id', $param['id'])->box('parent')->find()->beginTransaction()->table('categories')->where('id', $param['id'])->delete()->table('categories')->where('parent', $param['id'])->data('parent', ':box(parent.parent)')->update()->table('posts')->where('cid', $param['id'])->data('cid', 0)->update()->endTransaction()->output->display(':ok')->finish();
    }
    public function ordercategories($param = '')
    {
        $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('categories')->where('id', $param['id'])->data('sort', $param['sort'])->update()->output->display(':ok')->finish();
    }
    private function gettemplate($field)
    {
        $templang = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . $this->app->getConfig('template') . $this->DS . 'lang' . $this->DS . $this->app->getConfig('language') . '.php';
        if(is_file($templang)){
            $this->lang->load($templang);
        }
        $dir = glob($this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . $this->app->getConfig('template') . $this->DS . $field . $this->DS .'*.' . $this->app->getConfig('templatesuffix'));
        $temparr = [];
        foreach($dir as $key => $file){
            $description = '';
            $filearr = file($file);
            if(preg_match('/\<\!--(.*)--\>/i', $filearr[0], $metchs)){
                $description = $this->lang->translate(trim($metchs[1]));
            }
            $temparr[] = [
                'name' => basename($file),
                'description' => empty($description) ? '' : '（' . $description . '）'
            ];
        }
        return $temparr;
    }
    protected function filterFunc($param)
    {
        return Ladder::makeLadderForHtml($param, '--');
    }
    protected function filternochildFunc($param)
    {
        $ladder = Ladder::makeLadderForHtml($param, '--');
        $ignore = -1;
        foreach($ladder as $key => $val){
            if($val['id'] == $this->box->get('id')){
                $ignore = strlen($val['level']);
                unset($ladder[$key]);
                continue;
            }
            if($ignore >= 0){
                if(strlen($val['level']) > $ignore){
                    unset($ladder[$key]);
                }
                else{
                    $ignore = -1;
                }
            }
        }
        return $ladder;
    }
    public function clearcache()
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->editor(), $this->lang->translate('Insufficient permissions.'))->check->run('clearcacheFunc')->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->output->assign('share')->assign('group', 'tools')->assign('current', 'clearcache')->display()->finish();
    }
    protected function clearcacheFunc(Cache $cache)
    {
        $cache->clear();
    }
    public function databasebackup()
    {
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->output->assign('share')->assign('group', 'tools')->assign('current', 'databasebackup')->assign('backups', $this->getbackups())->display()->finish();
    }
    private function getbackupath()
    {
        $backup = trim($this->getTake('dbackup'));
        if(empty($backup)){
            $backup = 'public/backup';
            $this->setTake('dbackup', 'public/backup', 'need', 'general');
        }
        $backup = str_replace('\\', '/', $backup);
        return trim(trim($backup, '/'));
    }
    private function newdir($dirpath, $permissions = 0777)
    {
        @mkdir($dirpath, $permissions, true);
        if(is_dir($dirpath)){
            @file_put_contents($dirpath . $this->DS . 'index.html', base64_decode('PCFET0NUWVBFIGh0bWw+DQo8aHRtbCBsYW5nPSJ6aC1jbiI+DQo8aGVhZD4NCiAgICA8bWV0YSBjaGFyc2V0PSJVVEYtOCI+DQogICAgPHRpdGxlPjQwNDwvdGl0bGU+DQo8L2hlYWQ+DQo8Ym9keT4NClBhZ2Ugbm90IGZvdW5kDQo8L2JvZHk+DQo8L2h0bWw+'));
        }
    }
    private function getbackups()
    {
        $backupu = $this->getbackupath();
        $backup = str_replace('/', $this->DS, $backupu);
        $backupath = $this->rootDir . $this->DS . $backup;
        if(!is_dir($backupath)){
            $this->newdir($backupath);
        }
        $backupdir = glob($backupath . $this->DS . '*.zip', GLOB_NOSORT);
        if(!empty($backupdir)){
            usort($backupdir,function($a, $b){
                return filemtime($b) - filemtime($a);
            });
        }
        $backuparr = [];
        foreach($backupdir as $val){
            $name = basename($val);
            $backuparr[] = [
                'file' => $name,
                'size' => filesize($val),
                'path' => $this->route->rootUrl() . $backupu . '/' . $name
            ];
        }
        return $backuparr;
    }
    public function newbackup()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check->run('newbackupFunc')->output->display(':ok')->finish();
    }
    protected function newbackupFunc(Database $database)
    {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', -1);
        $tables = $database->getTables($this->app->getDb('database'));
        $prefix = $this->app->getDb('prefix');
        $prefixlen = strlen($prefix);
        $bkstr = '';
        foreach($tables as $table){
            $tablename = substr($table, $prefixlen);
            $fields = $database->getFields($table);
            $field = '';
            foreach($fields as $val){
                if(empty($field)){
                    $field = '`'.$val.'`';
                }
                else{
                    $field .= ', `'.$val.'`';
                }
            }
            $this->app->db->table($tablename)->field(implode(',', $fields))->box($tablename)->select()->finish();
            $result = $this->box->get($tablename);
            $tmp = '';
            if(is_array($result) && count($result) > 0){
                $i = 0;
                foreach($result as $rec){
                    $str = '';
                    foreach($rec as $key => $srec){
                        if(empty($str)){
                            $str = $this->strint($srec);
                        }
                        else{
                            $str .= ', '.$this->strint($srec);
                        }
                    }
                    if(empty($tmp)){
                        $tmp .= '('.$str.')';
                    }
                    else{
                        $tmp .= ',('.$str.')';
                    }
                    $i ++ ;
                    if($i > 50){
                        $this->harfinsert($tablename, $field, $tmp, $bkstr);
                        $tmp = '';
                        $i = 0;
                    }
                }
                if(!empty($tmp)){
                    $this->harfinsert($tablename, $field, $tmp, $bkstr);
                }
            }
        }
        $name = 'jpwrt' . substr(md5(time() . '-' . $prefix . '-' . rand()), 16) . '_' . date('YmdHis') . '.zip';
        $bkstr = '-- ' . $name . PHP_EOL . '-- JPWRT database backup' . PHP_EOL . '-- Generated date：' . date('Y-m-d H: i: s') . PHP_EOL . $bkstr;
        $backup = $this->getbackupath();
        $backup = str_replace('/', $this->DS, $backup);
        $backupath = $this->rootDir . $this->DS . $backup;
        if(!is_dir($backupath)){
            $this->newdir($backupath);
        }
        $backupfile = $backupath . $this->DS . $name;
        file_put_contents($backupfile, gzcompress($bkstr));
    }
    private function strint($si)
    {
        if($si === null){
            return 'NULL';
        }
        elseif(is_int($si)){
            return intval($si);
        }
        else{
            return '\''.str_replace('\'','\'\'',$si).'\'';
        }
    }
    private function delimiter()
    {
        return '--BASE-JSNPP-FRAMEWORK->JPWRT';
    }
    protected function harfinsert($tablename, $field, &$value, &$bkstr)
    {
        $restr = $tablename.'` ('.$field.') VALUES'.$value.';' . PHP_EOL;
        $bkstr .= $this->delimiter() . PHP_EOL . $restr;
    }
    public function uploadbackup()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'zip')->save('assist/backup')->box('zipname')->check->param(':box(zipname)')->run('uploadbackupFunc')->box('isrestore')->output->display(':box(isrestore)')->finish();
    }
    protected function uploadbackupFunc(Database $database, $file)
    {
        $file = str_replace('/', $this->DS, $file);
        $backupfile = $this->rootDir . $this->DS . $file;
        if(is_file($backupfile)){
            $re = $this->restoredb($database, $backupfile);
            if($re == 'ok'){
                @unlink($backupfile);
            }
            return $re;
        }
        else{
            return $this->lang->translate('The file was not found.');
        }
    }
    public function restorebackup($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check->param($param['file'])->run('restorebackupFunc')->box('isrestore')->output->display(':box(isrestore)')->finish();
    }
    protected function restorebackupFunc(Database $database, $file)
    {
        $backupu = $this->getbackupath();
        $backup = str_replace('/', $this->DS, $backupu);
        $backupfile = $this->rootDir . $this->DS . $backup . $this->DS . $file;
        if(is_file($backupfile)){
            return $this->restoredb($database, $backupfile);
        }
        else{
            return $this->lang->translate('The file was not found.');
        }
    }
    private function restoredb(Database $database, $file)
    {
        $bkf = gzuncompress(file_get_contents($file));
        $bkarr = explode($this->delimiter(), $bkf);
        $firststr = array_shift($bkarr);
        $annotation = explode('--', trim($firststr, '-'));
        $annotation = array_map(function($v){
            return trim($v);
        },$annotation);
        if($annotation[0] == basename($file) || $annotation[1] == 'JPWRT database backup'){
            ini_set('max_execution_time', 0);
            ini_set('memory_limit', -1);
            $tables = $database->getTables($this->app->getDb('database'));
            $prefix = $this->app->getDb('prefix');
            try{
                foreach($tables as $table){
                    $database->clearTable($table);
                }
                foreach($bkarr as $sqlstr){
                    $database->sql('INSERT INTO `' . $prefix . trim($sqlstr));
                }
                return 'ok';
            }
            catch(\Exception $e){
                return $e->getMessage();
            }
        }
        else{
            return $this->lang->translate('Invalid file.');
        }
    }
    public function deletebackup($param = '')
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check->param($param['file'])->run('deletebackupFunc')->output->display(':ok')->finish();
    }
    protected function deletebackupFunc($file)
    {
        $backupu = $this->getbackupath();
        $backup = str_replace('/', $this->DS, $backupu);
        $backupfile = $this->rootDir . $this->DS . $backup . $this->DS . $file;
        if(is_file($backupfile)){
            @unlink($backupfile);
        }
    }
    public function visit($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['siteAddress'], 'url', $this->lang->translate('Incorrect site address format'))->config->writeCustomize('site', [
            'siteTitle' => $param['siteTitle'],
            'tagline' => $param['tagline'],
            'siteKeywords' => str_replace('，', ',', $param['siteKeywords']),
            'siteDescription' => $param['siteDescription'],
            'siteAddress' => substr(trim($param['siteAddress']), -1) != '/' ? trim($param['siteAddress']) . '/' : trim($param['siteAddress']),
        ])->writeConfig([
            'language' => $param['siteLanguage'],
            'timezone' => $param['timezone']
        ])->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->output->assign('share')->assign('group', 'settings')->assign('current', 'visit')->assign('siteTitle', $this->app->getConfig('siteTitle'))->assign('tagline', $this->app->getConfig('tagline'))->assign('siteKeywords', $this->app->getConfig('siteKeywords'))->assign('siteDescription', $this->app->getConfig('siteDescription'))->assign('siteAddress', $this->app->getConfig('siteAddress'))->assign('language', $this->app->getConfig('language'))->assign('timezone', $this->app->getConfig('timezone'))->display()->finish();
    }
    public function general($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->check($param['admin-email'], 'require', $this->lang->translate('E-mail must be filled in.'))->check($param['admin-email'], 'email', $this->lang->translate('Incorrect email format.'))->check($param['role'], 'require', $this->lang->translate('The default role of the new user must be selected.'))->inbox('oemail', $this->getTake('admin-email'))->check->param($param)->run('generalFunc')->output->display(':ok')->finish();
        $this->app->entrance->check('get')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->db->table('take')->field('id,takename,takevalue')->where('backstage', 'general')->box('general')->select()->check->filter(':box(general)', 'filtergeneralFunc')->output->assign('share')->assign('group', 'settings')->assign('current', 'general')->assign('general', ':box(general)')->assign('rewrite', $this->app->getConfig('rewrite'))->display()->finish();
    }
    protected function generalFunc($param)
    {
        if($param['admin-email'] != $this->box->get('oemail')){
            $this->setTake('admin-email', serialize(['email' => Filter::html($param['admin-email']), 'active' => 0]));
        }
        $this->setTake('membership', (isset($param['membership']) && $param['membership'] == 'on') ? 1 : 0);
        $this->setTake('role', Filter::html($param['role']));
        $this->setTake('filing', $param['filing']);
        $this->setTake('customcode', $param['customcode']);
        $this->app->writeConfig('rewrite', (isset($param['rewrite']) && $param['rewrite'] == 'on') ? true : false);
    }
    protected function filtergeneralFunc($param)
    {
        $re = [];
        foreach($param as $val){
            if(false !== $unval = @unserialize($val['takevalue'])){
                $re[$val['takename']] = $unval;
            }
            else{
                $re[$val['takename']] = $val['takevalue'];
            }
        }
        return $re;
    }
    public function uploadimggeneral()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->upload->setName('file')->check('ext', 'png,jpg,jpeg,gif,webp')->save()->box('imgname')->cut(0, 150, '', 'adaptRaw')->check->run('uploadimggeneralFunc')->output->display(':box(imgname)')->finish();
    }
    protected function uploadimggeneralFunc()
    {
        $this->setTake('logo', $this->box->get('imgname'));
    }
    public function deleteimggeneral()
    {
        $this->app->entrance->check('post')->check($this->administrator(), $this->lang->translate('Insufficient permissions.'))->inbox('imgname', $this->getTake('logo'))->check->run('deleteimggeneralFunc')->output->display(':ok')->finish();
    }
    protected function deleteimggeneralFunc()
    {
        $imgpath = $this->box->get('imgname');
        if(!empty($imgpath)){
            @unlink($this->rootDir . $this->DS . str_replace('/', $this->DS, $imgpath));
            $this->setTake('logo', '');
        }
    }
    private function administrator()
    {
        return in_array($this->session->get('type'), ['administrator']) ? true : false;
    }
    private function editor()
    {
        return in_array($this->session->get('type'), ['administrator', 'editor']) ? true : false;
    }
    private function author()
    {
        return in_array($this->session->get('type'), ['administrator', 'editor', 'author']) ? true : false;
    }
    private function contributor()
    {
        return in_array($this->session->get('type'), ['administrator', 'editor', 'author', 'contributor']) ? true : false;
    }
    private function subscriber()
    {
        return in_array($this->session->get('type'), ['administrator', 'editor', 'author', 'contributor', 'subscriber']) ? true : false;
    }
    public function initialize()
    {
        if(!$this->session->has('id') || !$this->contributor()){
            $this->route->redirect('/');
            exit();
        }
        $language = $this->session->get('language');
        if(!empty($language)){
            $this->app->setConfig('language', $language);
        }
        $openedplugins = $this->app->getConfig('plugins');
        foreach($openedplugins as $key => $plugin){
            $this->handle->register($plugin);
            $pluginlang = $this->appDir . $this->DS . $this->app->getConfig('handle') . $this->DS . $plugin . $this->DS . 'lang' . $this->DS . $this->app->getConfig('language') . '.php';
            if(is_file($pluginlang)){
                $this->lang->load($pluginlang);
            }
        }
        $template = $this->app->getConfig('template');
        if(is_file($this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . lcfirst($template) . $this->DS . ucfirst($template) . '.php')){
            $themelang = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . lcfirst($template) . $this->DS . 'lang' . $this->DS . $this->app->getConfig('language') . '.php';
            if(is_file($themelang)){
                $this->lang->load($themelang);
            }
            $this->handle->register('jpwrt\theme\\' . lcfirst($template) . '\\' . ucfirst($template));
        }
        $this->handle->listen('addPlugin');
    }
    private function getTake($name)
    {
        $this->app->db->table('take')->field('takevalue')->where('takename', $name)->cache(1200, 'take_' . $name)->box('take')->find()->finish();
        return $this->box->get('take.takevalue');
    }
    private function setTake($name, $value, $type = '', $backstage = '')
    {
        $this->app->db->table('take')->field('id')->where('takename', $name)->box('take_' . $name)->find()->finish();
        if(empty($this->box->get('take_' . $name))){
            $this->app->db->table('take')->data('takename', $name)->data('takevalue', $value)->data('taketype', $type)->data('backstage', $backstage)->removeCache('take_' . $name)->insert()->finish();
        }
        else{
            $this->app->db->table('take')->where('takename', $name)->data('takevalue', $value)->removeCache('take_' . $name)->update()->finish();
        }
    }
    protected function share()
    {
        $this->view->assign('webroot', $this->route->rootUrl())->assign('language', $this->app->getConfig('language'))->assign('administrator', $this->administrator())->assign('editor', $this->editor())->assign('author', $this->author())->assign('contributor', $this->contributor())->assign('user', $this->session->get('user'))->assign('plugin', Attach::getPlugin())->assign('groups', Attach::getGroup());
    }
}