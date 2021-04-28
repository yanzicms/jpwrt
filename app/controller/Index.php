<?php
/**
 * Jsnpp - A full-chain PHP framework
 * Author: A.J <804644245@qq.com>
 * Copyright: Jsnpp [http://www.jsnpp.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jsnpp
 */
/**
 * JPWRT - Blog program based on jsnpp
 * Author: A.J <804644245@qq.com>
 * Copyright: JPWRT [http://www.jpwrt.com] All rights reserved.
 * Licensed: Apache-2.0
 * GitHub: https://github.com/yanzicms/jpwrt
 */
namespace app\controller;
use app\general\Filter;
use app\general\Ladder;
use jsnpp\Cache;
use jsnpp\Controller;
use jsnpp\Pagination;
use jsnpp\Tools;
class Index extends Controller
{
    private $cachetime = 1200;
    private $access = '';
    private $sitename = '';
    private $keywords = '';
    private $description = '';
    private $home = false;
    private $cache;
    public function index()
    {
        if(!is_file($this->rootDir . $this->DS . 'config' . $this->DS . 'locked.php')){
            $this->route->redirect('install');
            exit();
        }
        $this->home = true;
        if($this->app->getConfig('homepage') == 'static'){
            $this->app->entrance->check('get')->db->table('pages')->field('id,title,slug,keyword,template,thumbnail,views,createtime,summary,content')->where('id', $this->app->getConfig('staticpage'))->where('visibility', '<', 2)->where('status', 3)->box('page')->cache($this->cachetime, 'homepage')->find()->table('pages')->where('id', $this->app->getConfig('staticpage'))->data('views', 'views+1')->update()->event->listen('page', ':box(page)')->output->assign('share')->assign('page', ':box(page)')->display(!empty($this->box->get('page.template')) ? $this->theme('pages/' . $this->box->get('page.template')) : $this->theme('page'))->finish();
        }
        else{
            $this->app->entrance->check('get')->db->table('posts')->alias('posts')->field('id,uid,title,slug,keyword,thumbnail,comment,views,createtime,summary')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('top DESC,id DESC')->join('uid = id')->table('users')->field('username,publicname,avatar')->join('posts.cid = id')->table('categories')->field('slug as catslug')->paging($this->app->getConfig('homeshow'))->box('posts')->cache($this->cachetime, 'home')->endJoin()->check->filter(':box(posts)', 'indexFunc')->event->listen('home', ':box(posts)')->output->assign('share')->assign('posts', ':box(posts)')->display($this->theme('index'))->finish();
        }
    }
    protected function indexFunc($param)
    {
        foreach($param['data'] as $key => $val){
            if(empty($val['avatar'])){
                $param['data'][$key]['avatar'] = $this->route->rootUrl() . 'public/static/images/avatar.png';
            }
            else{
                $param['data'][$key]['avatar'] = $this->route->rootUrl() . $val['avatar'];
            }
            if(!empty($val['thumbnail'])){
                $param['data'][$key]['thumbnail'] = $this->route->rootUrl() . $val['thumbnail'];
            }
            $param['data'][$key]['url'] = $this->route->url('index/archives', ['id' => $val['id'], 'name' => $val['slug'], 'category' => $val['catslug'], 'author' => $val['username'], 'year' => date('Y', strtotime($val['createtime'])), 'month' => date('m', strtotime($val['createtime'])), 'day' => date('d', strtotime($val['createtime']))]);
        }
        return $param;
    }
    public function tag($param)
    {
        $this->access = 'tag';
        $this->app->entrance->check('get')->inbox('tid', $this->gettid($param))->db->table('postags')->field('tid')->where('tid', ':box(tid)')->join('pid = id')->table('posts')->alias('posts')->field('id,uid,title,slug,keyword,thumbnail,comment,views,createtime,summary')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id DESC')->join('uid = id')->table('users')->field('username,publicname,avatar')->join('posts.cid = id')->table('categories')->field('slug as catslug')->paging($this->app->getConfig('pageshow'))->box('tag')->cache($this->cachetime, 'tag')->endJoin()->check->filter(':box(tag)', 'indexFunc')->event->listen('tag', ':box(tag)')->output->assign('share')->assign('tag', ':box(tag)')->display(!empty($this->box->get('tidarr.template')) ? $this->theme('tags/' . $this->box->get('tidarr.template')) : $this->theme('tag'))->finish();
    }
    private function gettid($param)
    {
        if(isset($param['id'])){
            $this->app->db->table('tags')->field('id,name,keyword,template,description')->where('id', $param['id'])->box('tidarr')->cache($this->cachetime)->find()->finish();
            $this->sitename = $this->box->get('tidarr.name');
            $this->keywords = $this->box->get('tidarr.keyword');
            $this->description = $this->box->get('tidarr.description');
            return $param['id'];
        }
        elseif(isset($param['name'])){
            if(is_numeric($param['name'])){
                $this->app->db->table('tags')->field('id,name,keyword,template,description')->where('id', $param['name'])->box('tidarr')->cache($this->cachetime)->find()->finish();
                $this->sitename = $this->box->get('tidarr.name');
                $this->keywords = $this->box->get('tidarr.keyword');
                $this->description = $this->box->get('tidarr.description');
                return $param['name'];
            }
            else{
                $this->app->db->table('tags')->field('id,name,keyword,template,description')->where('slug', $param['name'])->box('tidarr')->cache($this->cachetime)->find()->finish();
                $this->sitename = $this->box->get('tidarr.name');
                $this->keywords = $this->box->get('tidarr.keyword');
                $this->description = $this->box->get('tidarr.description');
                return $this->box->get('tidarr.id');
            }
        }
        return 0;
    }
    public function archives($param)
    {
        $allowcomments = $this->getTake('allowcomments');
        $close = $this->getTake('close');
        $closeday = $this->getTake('closeday');
        if($this->request->isPost()){
            $login = $this->getTake('login');
            $nameemail = $this->getTake('nameemail');
            $commentuser = $this->commentuser($param);
            $commentstatus = $this->commentstatus($param, $commentuser);
            $rejectkeywords = $this->getTake('rejectkeywords');
            $reject = 0;
            if(!empty($rejectkeywords)){
                $rejectarr = explode("\n", strtolower($rejectkeywords));
                if(Tools::hasString(strtolower($param['comment']), $rejectarr) || (isset($commentuser['name']) && Tools::hasString(strtolower($commentuser['name']), $rejectarr)) || (isset($commentuser['email']) && Tools::hasString(strtolower($commentuser['email']), $rejectarr)) || Tools::hasString($this->request->ip(), $rejectarr)){
                    $reject = 1;
                }
            }
            $this->app->entrance->check('post')->check($param['comment'], 'require', $this->lang->translate('The content of the comment must be filled in.'))->check(!($login == 1 && !$this->session->has('id')), $this->lang->translate('You can comment after logging in.'))->check(!($nameemail == 1 && !$this->session->has('id') && (!isset($param['name']) || empty($param['name']))), $this->lang->translate('The name must be filled in.'))->check(!($nameemail == 1 && !$this->session->has('id') && (!isset($param['email']) || empty($param['email']))), $this->lang->translate('E-mail address must be filled in.'))->check(isset($param['email']) ? $param['email'] : '', 'email', $this->lang->translate('Incorrect email format.'))->check($reject == 0, $this->lang->translate('Your comment was rejected.'))->inbox('commentuser', $commentuser)->db->table('posts')->field('id,createtime,commentsoff')->where('id', intval($param['id']))->box('posts')->find()
                ->check->stop($this->box->get('posts.commentsoff') == 1 || $allowcomments != 1 || ($close == 1 && strtotime('+' . $closeday . ' days', strtotime($this->box->get('posts.createtime'))) < time()), $this->lang->translate('The comment channel has been closed. You cannot comment.'))
                ->db->table('comments')->insert([
                    'uid' => ':box(commentuser.uid)',
                    'pid' => intval($param['id']),
                    'createtime' => date('Y-m-d H:i:s'),
                    'editime' => date('Y-m-d H:i:s'),
                    'parent' => isset($param['parent']) ? intval($param['parent']) : 0,
                    'status' => $commentstatus,
                    'publicname' => ':box(commentuser.name)',
                    'email' => ':box(commentuser.email)',
                    'comment' => Filter::html($param['comment'])
                ])->table('users')->where('id', ':box(commentuser.uid)')->data('comments', 'comments+1')->update()
                ->check->deleteCacheTag('posts' . intval($param['id']))->output->display(':ok')->finish();
        }
        $aid = $this->getaid($param);
        $defaultdisplay = $this->getTake('defaultdisplay');
        $ordercomments = $defaultdisplay == 'first' ? 'id ASC' : 'id DESC';
        $commentset = [
            'opennested' => $this->getTake('opennested'),
            'nested' => $this->getTake('nested'),
            'pagingdisplay' => $this->getTake('pagingdisplay'),
            'perdisplay' => $this->getTake('perdisplay'),
            'defaultdisplay' => $defaultdisplay
        ];
        $this->access = 'archives';
        $this->app->entrance->check('get')->inbox('aid', $aid)->inbox('params', $param)->inbox('commentset', $commentset)->db->table('posts')->field('id,uid,cid,title,slug,keyword,template,source,thumbnail,comment,views,likes,dislikes,createtime,commentsoff,visibility,summary,content')->where('id', ':box(aid)')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->box('posts')->cache($this->cachetime, 'posts' . $aid)->find()->table('posts')->field('id,uid,cid,title,slug,thumbnail,createtime,summary')->where('id', '<', ':box(posts.id)')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id DESC')->box('nextpost')->cache($this->cachetime, 'posts' . $aid)->find()->table('posts')->field('id,uid,cid,title,slug,thumbnail,createtime,summary')->where('id', '>', ':box(posts.id)')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id ASC')->box('prevpost')->cache($this->cachetime, 'posts' . $aid)->find()->table('comments')->field('id,uid,pid,editime,parent,publicname,comment')->where('pid', ':box(aid)')->where('status', 3)->order($ordercomments)->leftJoin('uid = id')->table('users')->field('avatar,signature')->box('comments')->cache($this->cachetime, 'posts' . $aid)->endJoin()->table('posts')->where('id', ':box(aid)')->data('views', 'views+1')->update()->check->filter(':box(posts)', 'archivesFunc')->filter(':box(nextpost)', 'nextpostFunc')->filter(':box(prevpost)', 'prevpostFunc')->filter(':box(comments)', 'commentsFunc')->event->listen('posts', ':box(posts)')->listen('nextpost', ':box(nextpost)')->listen('prevpost', ':box(prevpost)')->listen('comments', ':box(comments)')->output->assign('share')->assign('post', ':box(posts)')->assign('prevpost', ':box(prevpost)')->assign('nextpost', ':box(nextpost)')->assign('comments', ':box(comments)')->assign('commentsoff', $this->commentsoff($close, $closeday, $allowcomments))->display(!empty($this->box->get('posts.template')) ? $this->theme('archives/' . $this->box->get('posts.template')) : $this->theme('post'))->finish();
    }
    public function postpassword($param)
    {
        if($this->request->isPost()){
            $this->app->entrance->check('post')->db->table('posts')->field('id,password,content')->where('id', $param['id'])->box('post')->find()->finish();
            if($this->box->get('post.password') == md5($param['password'])){
                return [
                    'result' => 'ok',
                    'message' => $this->box->get('post.content')
                ];
            }
            else{
                return [
                    'result' => 'error',
                    'message' => $this->lang->translate('Wrong password')
                ];
            }
        }
        else{
            return [
                'result' => 'error',
                'message' => ''
            ];
        }
    }
    public function pagepassword($param)
    {
        if($this->request->isPost()){
            $this->app->entrance->check('post')->db->table('pages')->field('id,thumbnail,password,content')->where('id', $param['id'])->box('pages')->find()->finish();
            if($this->box->get('pages.password') == md5($param['password'])){
                return [
                    'result' => 'ok',
                    'message' => $this->box->get('pages.content'),
                    'thumbnail' => $this->box->get('pages.thumbnail')
                ];
            }
            else{
                return [
                    'result' => 'error',
                    'message' => $this->lang->translate('Wrong password')
                ];
            }
        }
        else{
            return [
                'result' => 'error',
                'message' => ''
            ];
        }
    }
    protected function commentsFunc(Cache $cache, Pagination $pagination, $param)
    {
        if($this->box->has('params.page')){
            $page = $this->box->get('params.page');
        }
        elseif($this->box->get('commentset.pagingdisplay') == 1){
            $page = 1;
        }
        else{
            $page = 0;
        }
        $pid = 0;
        if(count($param) > 0){
            $pid = $param[0]['pid'];
        }
        if($cache->has('commentsfunc_' . $pid . '_' . $page . '_' . $this->box->get('commentset.defaultdisplay'))){
            $param = $cache->get('commentsfunc_' . $pid . '_' . $page . '_' . $this->box->get('commentset.defaultdisplay'));
        }
        else{
            foreach($param as $key => $val){
                if(is_null($val['avatar']) || empty($val['avatar'])){
                    $param[$key]['avatar'] = $this->route->rootUrl() . 'public/static/images/avatar.png';
                }
                else{
                    $param[$key]['avatar'] = $this->route->rootUrl() . $val['avatar'];
                }
                if(is_null($val['signature'])){
                    $param[$key]['signature'] = '';
                }
            }
            if($page == 0){
                $paramor = Ladder::makeLadderForHtml($param);
                $totle = count($paramor);
                $param = [
                    'total' => $totle,
                    'per' => $totle,
                    'page' => 1,
                    'pages' => 1,
                    'paging' => '',
                    'simplePaging' => '',
                    'data' => $paramor
                ];
            }
            else{
                $paramor = Ladder::makeLadderForHtml($param);
                $totle = count($paramor);
                $per = $this->box->get('commentset.perdisplay');
                $depth = $this->box->get('commentset.nested');
                $pages = ceil($totle / $per);
                $param = [
                    'total' => $totle,
                    'per' => $per,
                    'page' => $page,
                    'pages' => $pages,
                    'paging' => $pagination->getHtml($page, $pages, '?page='),
                    'simplePaging' => $pagination->getSimpleHtml($page, $pages, '?page=')
                ];
                $start = ($page - 1) * $per;
                $end = $start + $per;
                foreach($paramor as $key => $val){
                    $paramor[$key]['level'] = $val['level'] % $depth;
                    if($key >= $start && $key < $end){
                        $param['data'][] = $val;
                    }
                }
            }
            $cache->tag('comments')->set('commentsfunc_' . $pid . '_' . $page . '_' . $this->box->get('commentset.defaultdisplay'), $param, $this->cachetime);
        }
        return $param;
    }
    public function like($param)
    {
        $this->app->entrance->check('post')->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('posts')->where('id', $param['id'])->data('likes', 'likes+1')->update()->output->display(':ok')->finish();
    }
    public function dislike($param)
    {
        $this->app->entrance->check('post')->check($param['id'], 'require', $this->lang->translate('Parameters are missing.'))->db->table('posts')->where('id', $param['id'])->data('dislikes', 'dislikes+1')->update()->output->display(':ok')->finish();
    }
    private function commentsoff($close, $closeday, $allowcomments)
    {
        if($this->box->get('posts.commentsoff') == 1){
            return true;
        }
        elseif($allowcomments != 1 || ($close == 1 && strtotime('+' . $closeday . ' days', strtotime($this->box->get('posts.createtime'))) < time())){
            return true;
        }
        return false;
    }
    private function commentstatus($param, $commentuser)
    {
        $reviewkeywords = $this->getTake('reviewkeywords');
        if(!empty($reviewkeywords)){
            $reviewarr = explode("\n", strtolower($reviewkeywords));
            if(Tools::hasString(strtolower($param['comment']), $reviewarr) || (isset($commentuser['name']) && Tools::hasString(strtolower($commentuser['name']), $reviewarr)) || (isset($commentuser['email']) && Tools::hasString(strtolower($commentuser['email']), $reviewarr)) || Tools::hasString($this->request->ip(), $reviewarr)){
                return 0;
            }
        }
        if($this->getTake('approval') == 1){
            return 0;
        }
        elseif($this->getTake('previousapproved') == 1){
            if($this->session->has('id')){
                $this->app->db->table('comments')->field('id')->where('uid', $this->session->get('id'))->where('status', 3)->box('commentstatus')->cache($this->cachetime)->find()->finish();
                if(!empty($this->box->get('commentstatus'))){
                    return 3;
                }
            }
        }
        return 0;
    }
    private function commentuser($param)
    {
        if(!$this->session->has('id')){
            $uid = 0;
            $name = isset($param['name']) ? $param['name'] : '';
            $email = isset($param['email']) ? $param['email'] : '';
        }
        else{
            $this->app->db->table('users')->field('id,publicname,email')->where('id', $this->session->get('id'))->box('users')->cache($this->cachetime)->find()->finish();
            $uid = $this->box->get('users.id');
            $name = $this->box->get('users.publicname');
            $email = $this->box->get('users.email');
        }
        return [
            'uid' => $uid,
            'name' => $name,
            'email' => $email
        ];
    }
    protected function archivesFunc(Cache $cache, $param)
    {
        if(!empty($param)){
            $this->sitename = $param['title'];
            $this->keywords = $param['keyword'];
            $this->description = $param['summary'];
            if($cache->has('archivesfunc_' . $param['id'])){
                $param = $cache->get('archivesfunc_' . $param['id']);
            }
            else{
                $this->app->db->table('users')->field('username,publicname')->where('id', $param['uid'])->box('users')->cache($this->cachetime)->find()->finish();
                $param['username'] = $this->box->get('users.username');
                $param['publicname'] = $this->box->get('users.publicname');
                $this->app->db->table('categories')->field('slug')->where('id', $param['cid'])->box('categories')->cache($this->cachetime)->find()->finish();
                $param['catslug'] = $this->box->get('categories.slug');
                $param['url'] = $this->route->url('index/archives', ['id' => $param['id'], 'name' => $param['slug'], 'category' => $param['catslug'], 'author' => $param['username'], 'year' => date('Y', strtotime($param['createtime'])), 'month' => date('m', strtotime($param['createtime'])), 'day' => date('d', strtotime($param['createtime']))]);
                if($param['visibility'] == 1){
                    $param['secret'] = true;
                }
                else{
                    $param['secret'] = false;
                }
                $cache->tag('posts')->set('archivesfunc_' . $param['id'], $param, $this->cachetime);
            }
        }
        return $param;
    }
    protected function nextpostFunc(Cache $cache, $param)
    {
        if(!empty($param)){
            if($cache->has('nextpostfunc_' . $param['id'])){
                $param = $cache->get('nextpostfunc_' . $param['id']);
            }
            else{
                $this->app->db->table('users')->field('username')->where('id', $param['uid'])->box('users')->cache($this->cachetime)->find()->finish();
                $param['username'] = $this->box->get('users.username');
                $this->app->db->table('categories')->field('slug')->where('id', $param['cid'])->box('categories')->cache($this->cachetime)->find()->finish();
                $param['catslug'] = $this->box->get('categories.slug');
                $param['url'] = $this->route->url('index/archives', ['id' => $param['id'], 'name' => $param['slug'], 'category' => $param['catslug'], 'author' => $param['username'], 'year' => date('Y', strtotime($param['createtime'])), 'month' => date('m', strtotime($param['createtime'])), 'day' => date('d', strtotime($param['createtime']))]);
                $cache->tag('posts')->set('nextpostfunc_' . $param['id'], $param, $this->cachetime);
            }
        }
        return $param;
    }
    protected function prevpostFunc(Cache $cache, $param)
    {
        if(!empty($param)){
            if($cache->has('prevpostfunc_' . $param['id'])){
                $param = $cache->get('prevpostfunc_' . $param['id']);
            }
            else{
                $this->app->db->table('users')->field('username')->where('id', $param['uid'])->box('users')->cache($this->cachetime)->find()->finish();
                $param['username'] = $this->box->get('users.username');
                $this->app->db->table('categories')->field('slug')->where('id', $param['cid'])->box('categories')->cache($this->cachetime)->find()->finish();
                $param['catslug'] = $this->box->get('categories.slug');
                $param['url'] = $this->route->url('index/archives', ['id' => $param['id'], 'name' => $param['slug'], 'category' => $param['catslug'], 'author' => $param['username'], 'year' => date('Y', strtotime($param['createtime'])), 'month' => date('m', strtotime($param['createtime'])), 'day' => date('d', strtotime($param['createtime']))]);
                $cache->tag('posts')->set('prevpostfunc_' . $param['id'], $param, $this->cachetime);
            }
        }
        return $param;
    }
    private function getaid($param)
    {
        if(isset($param['id'])){
            return $param['id'];
        }
        elseif(isset($param['name'])){
            $this->app->db->table('posts')->field('id')->where('slug', $param['name'])->box('aidarr')->cache($this->cachetime)->find()->finish();
            return $this->box->get('aidarr.id');
        }
        return 0;
    }
    public function page($param)
    {
        $this->app->entrance->check('get')->inbox('pid', $this->getpid($param))->db->table('pages')->field('id,title,slug,keyword,template,thumbnail,views,createtime,visibility,summary,content')->where('id', ':box(pid)')->where('visibility', '<', 2)->where('status', 3)->box('page')->cache($this->cachetime, 'page')->find()->table('pages')->where('id', ':box(pid)')->data('views', 'views+1')->update()->check->filter(':box(page)', 'pageFunc')->event->listen('page', ':box(page)')->output->assign('share')->assign('page', ':box(page)')->display(!empty($this->box->get('page.template')) ? $this->theme('pages/' . $this->box->get('page.template')) : $this->theme('page'))->finish();
    }
    protected function pageFunc($param)
    {
        $this->sitename = $param['title'];
        $this->keywords = $param['keyword'];
        $this->description = $param['summary'];
        if(!empty($param['thumbnail'])){
            $param['thumbnail'] = $this->route->rootUrl() . $param['thumbnail'];
        }
        if($param['visibility'] == 1){
            $param['secret'] = true;
        }
        else{
            $param['secret'] = false;
        }
        return $param;
    }
    private function getpid($param)
    {
        if(isset($param['id'])){
            return $param['id'];
        }
        elseif(isset($param['name'])){
            $this->app->db->table('pages')->field('id')->where('slug', $param['name'])->box('pidarr')->cache($this->cachetime)->find()->finish();
            return $this->box->get('pidarr.id');
        }
        return 0;
    }
    public function archive($param)
    {
        $this->access = 'archive';
        $start = date('Y-m-d H:i:s', strtotime($param['name']));
        $end = date('Y-m-d H:i:s', strtotime('+1 month', strtotime($param['name'])));
        $this->sitename = $param['name'];
        $this->keywords = $param['name'];
        $this->description = $this->lang->translate('Archive');
        $this->app->entrance->check('get')->db->table('posts')->alias('posts')->field('id,uid,title,slug,keyword,thumbnail,comment,views,createtime,summary')->where('createtime', 'BETWEEN', [$start, $end])->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->order('id DESC')->join('uid = id')->table('users')->field('username,publicname,avatar')->join('posts.cid = id')->table('categories')->field('slug as catslug')->paging($this->app->getConfig('pageshow'))->box('posts')->cache($this->cachetime, 'category')->endJoin()->check->filter(':box(posts)', 'indexFunc')->event->listen('archive', ':box(posts)')->output->assign('share')->assign('posts', ':box(posts)')->display($this->theme('archive'))->finish();
    }
    public function search($param)
    {
        $this->access = 'search';
        $this->sitename = $param['keyword'];
        $this->keywords = $param['keyword'];
        $this->description = $this->lang->translate('Search');
        $this->app->entrance->check('get')->db->table('posts')->alias('posts')->field('id,uid,title,slug,keyword,thumbnail,comment,views,createtime,summary')->where('title', 'LIKE', '%' . $param['keyword'] . '%')->orWhere('content', 'LIKE', '%' . $param['keyword'] . '%')->andWhere('visibility', 0)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id DESC')->join('uid = id')->table('users')->field('username,publicname,avatar')->join('posts.cid = id')->table('categories')->field('slug as catslug')->paging($this->app->getConfig('pageshow'))->box('posts')->cache($this->cachetime, 'search')->endJoin()->check->filter(':box(posts)', 'indexFunc')->event->listen('search', ':box(posts)')->output->assign('share')->assign('posts', ':box(posts)')->display($this->theme('search'))->finish();
    }
    public function category($param)
    {
        $this->app->entrance->check('get')->inbox('cid', $this->getcid($param))->db->table('posts')->alias('posts')->field('id,uid,title,slug,keyword,thumbnail,comment,views,createtime,summary')->where('cid', ':box(cid)')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id DESC')->join('uid = id')->table('users')->field('username,publicname,avatar')->join('posts.cid = id')->table('categories')->field('slug as catslug')->paging($this->app->getConfig('pageshow'))->box('posts')->cache($this->cachetime, 'category')->endJoin()->check->filter(':box(posts)', 'indexFunc')->event->listen('category', ':box(posts)')->output->assign('share')->assign('posts', ':box(posts)')->display(!empty($this->box->get('cidarr.template')) ? $this->theme('categories/' . $this->box->get('cidarr.template')) : $this->theme('category'))->finish();
    }
    private function getcid($param)
    {
        if(isset($param['id'])){
            $this->app->db->table('categories')->field('id,name,keyword,template,description')->where('id', $param['id'])->box('cidarr')->cache($this->cachetime)->find()->finish();
            $this->sitename = $this->box->get('cidarr.name');
            $this->keywords = $this->box->get('cidarr.keyword');
            $this->description = $this->box->get('cidarr.description');
            return $param['id'];
        }
        elseif(isset($param['name'])){
            $this->app->db->table('categories')->field('id,name,keyword,template,description')->where('slug', $param['name'])->box('cidarr')->cache($this->cachetime)->find()->finish();
            $this->sitename = $this->box->get('cidarr.name');
            $this->keywords = $this->box->get('cidarr.keyword');
            $this->description = $this->box->get('cidarr.description');
            return $this->box->get('cidarr.id');
        }
        return 0;
    }
    public function fail()
    {
        $this->view->assign('share')->setCode(404)->display($this->theme('fail'));
    }
    private function theme($name)
    {
        $templatesuffix = $this->app->getConfig('templatesuffix');
        $suffixlen = strlen($templatesuffix) + 1;
        if(substr($name, - $suffixlen) == '.' . $templatesuffix){
            $name = substr($name, 0, - $suffixlen);
        }
        $themePath = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . $this->app->getConfig('template') . $this->DS . str_replace(['\\', '/'], $this->DS, $name) . '.' . $this->app->getConfig('templatesuffix');
        if(is_file($themePath)){
            return $themePath;
        }
        return '';
    }
    public function preview($param)
    {
        $this->app->entrance->check('get')->db->table('posts')->field('id,uid,cid,title,slug,keyword,template,source,thumbnail,comment,views,likes,dislikes,createtime,commentsoff,visibility,summary,content')->where('id', $param['id'])->where('uid', $this->session->get('id'))->box('posts')->find()->table('posts')->field('id,uid,cid,title,slug,thumbnail,createtime,summary')->where('id', '<', ':box(posts.id)')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id DESC')->box('nextpost')->find()->table('posts')->field('id,uid,cid,title,slug,thumbnail,createtime,summary')->where('id', '>', ':box(posts.id)')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id ASC')->box('prevpost')->find()->table('comments')->field('id,uid,pid,editime,parent,publicname,comment')->where('pid', $param['id'])->where('status', 3)->order('id DESC')->leftJoin('uid = id')->table('users')->field('avatar,signature')->box('comments')->endJoin()->check->filter(':box(posts)', 'archivesFunc')->filter(':box(nextpost)', 'nextpostFunc')->filter(':box(prevpost)', 'prevpostFunc')->filter(':box(comments)', 'commentsFunc')->output->assign('share')->assign('post', ':box(posts)')->assign('prevpost', ':box(prevpost)')->assign('nextpost', ':box(nextpost)')->assign('comments', ':box(comments)')->assign('commentsoff', true)->display(!empty($this->box->get('posts.template')) ? $this->theme('archives/' . $this->box->get('posts.template')) : $this->theme('post'))->finish();
    }
    public function callback($param)
    {
        $this->handle->listen('callback', $param);
    }
    protected function share()
    {
        $dateformat = $this->app->getDb('type') == 'sqlite' ? 'strftime(\'%Y-%m\', createtime)' : 'DATE_FORMAT(`createtime`,\'%Y-%m\')';
        $this->app->db->table('menu')->field('id,name,slug,parent,icon,url')->order('slug ASC,sort ASC')->box('menu')->cache($this->cachetime, 'menu')->select()->table('slide')->field('id,name,image,url,description')->order('gid ASC,sort ASC')->leftJoin('gid = id')->table('slidegroup')->field('slug')->box('slide')->cache($this->cachetime, 'slide')->endJoin()->table('tags')->field('id,name,slug,quantity')->order('quantity DESC')->limit(50)->box('tags')->cache($this->cachetime, 'tags')->select()->table('posts')->field('id,' . $dateformat . ' as time,COUNT(*) as quantity')->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->group($dateformat)->order('id DESC')->limit(50)->box('archive')->cache($this->cachetime, 'archive')->select()->table('posts')->alias('posts')->field('id,uid,title,slug,keyword,thumbnail,comment,views,createtime,summary')->where('recommend', 1)->where('visibility', '<', 2)->where('status', 3)->where('trash', 0)->where('createtime', 'BETWEEN', [$this->app->getConfig('creationtime'), $this->totime()])->order('id DESC')->join('uid = id')->table('users')->field('username,publicname,avatar')->join('posts.cid = id')->table('categories')->field('slug as catslug')->box('recommend')->cache($this->cachetime, 'recommend')->endJoin()->table('links')->field('id,name,image,url,home,description')->order('sort DESC')->box('links')->cache($this->cachetime, 'links')->select()->check->filter(':box(menu)', 'menuFunc')->filter(':box(slide)', 'slideFunc')->filter(':box(tags)', 'tagsFunc')->filter(':box(archive)', 'archiveFunc')->filter(':box(recommend)', 'recommendFunc')->filter(':box(links)', 'linksFunc')->event->listen('general')->output->assign('webroot', $this->route->rootUrl())->assign('siteName', $this->app->getConfig('siteTitle'))->assign('siteTitle', empty($this->sitename) ? $this->app->getConfig('siteTitle') : $this->sitename)->assign('tagline', empty($this->sitename) ? $this->app->getConfig('tagline') : $this->app->getConfig('siteTitle'))->assign('siteKeywords', empty($this->keywords) ? $this->app->getConfig('siteKeywords') : $this->keywords)->assign('siteDescription', empty($this->description) ? $this->app->getConfig('siteDescription') : $this->description)->assign('menu', ':box(menu.menu)')->assign('breadcrumb', ':box(menu.breadcrumb)')->assign('slide', ':box(slide)')->assign('slideGroup', ':box(slideGroup)')->assign('tags', ':box(tags)')->assign('archive', ':box(archive)')->assign('recommend', ':box(recommend)')->assign('links', ':box(links)')->assign('logged', $this->session->has('id'))->assign('user', $this->session->has('user') ? $this->session->get('user') : '')->assign('template', $this->app->getConfig('template'))->assign('language', $this->app->getConfig('language'))->assign('membership', $this->getTake('membership'))->assign('filing', $this->getTake('filing'))->assign('customcode', $this->getTake('customcode'))->assign('logo', $this->getlogo())->noAssign('homeTop')->noAssign('homeBottom')->noAssign('categoryTop')->noAssign('categoryBottom')->noAssign('pageTop')->noAssign('pageBottom')->noAssign('searchTop')->noAssign('searchBottom')->noAssign('tagTop')->noAssign('tagBottom')->noAssign('archiveTop')->noAssign('archiveBottom')->noAssign('postTop')->noAssign('postBottom')->noAssign('commentTop')->noAssign('commentBottom')->noAssign('sideTop')->noAssign('sideBottom')->noAssign('footer')->finish();
    }
    private function getlogo()
    {
        $logo = $this->getTake('logo');
        if(!empty($logo)){
            $logo = $this->route->rootUrl() . $logo;
        }
        return $logo;
    }
    protected function linksFunc($param)
    {
        if($this->home){
            foreach($param as $key => $val){
                if($val['home'] != 1){
                    unset($param[$key]);
                }
                else{
                    if(!empty($val['image'])){
                        $param[$key]['image'] = $this->route->rootUrl() . $val['image'];
                    }
                }
            }
        }
        else{
            foreach($param as $key => $val){
                if($val['home'] == 1){
                    unset($param[$key]);
                }
                else{
                    if(!empty($val['image'])){
                        $param[$key]['image'] = $this->route->rootUrl() . $val['image'];
                    }
                }
            }
        }
        return $param;
    }
    protected function recommendFunc($param)
    {
        foreach($param as $key => $val){
            if(empty($val['avatar'])){
                $param[$key]['avatar'] = $this->route->rootUrl() . 'public/static/images/avatar.png';
            }
            else{
                $param[$key]['avatar'] = $this->route->rootUrl() . $val['avatar'];
            }
            if(!empty($val['thumbnail'])){
                $param[$key]['thumbnail'] = $this->route->rootUrl() . $val['thumbnail'];
            }
            $param[$key]['url'] = $this->route->url('index/archives', ['id' => $val['id'], 'name' => $val['slug'], 'category' => $val['catslug'], 'author' => $val['username'], 'year' => date('Y', strtotime($val['createtime'])), 'month' => date('m', strtotime($val['createtime'])), 'day' => date('d', strtotime($val['createtime']))]);
        }
        return $param;
    }
    protected function archiveFunc($param)
    {
        foreach($param as $key => $val){
            $param[$key]['url'] = $this->route->url('index/archive', ['name' => $val['time']]);
            if($this->app->getConfig('language') == 'zh-cn'){
                $param[$key]['time'] = date('Y年n月', strtotime($val['time']));
            }
        }
        return $param;
    }
    protected function tagsFunc($param)
    {
        foreach($param as $key => $val){
            if(empty($val['slug'])){
                $param[$key]['url'] = $this->route->url('index/tag', ['name' => $val['id']]);
            }
            else{
                $param[$key]['url'] = $this->route->url('index/tag', ['name' => $val['slug']]);
            }
        }
        return $param;
    }
    protected function menuFunc(Cache $cache, $param)
    {
        if($cache->has('menucache')){
            $menu = $cache->get('menucache');
        }
        else{
            $menuse = unserialize($this->getTake('jswrt-menu'));
            $primary = $menuse['primary']['slug'];
            $secondary = $menuse['secondary']['slug'];
            $menu = [];
            foreach($param as $key => $val){
                $tourl = $this->tourl(unserialize($val['url']));
                $menu[$val['slug']][] = [
                    'id' => $val['id'],
                    'name' => $val['name'],
                    'parent' => $val['parent'],
                    'icon' => $val['icon'],
                    'url' => $tourl[0],
                    'cid' => $tourl[1],
                    'ishome' => $tourl[2]
                ];
            }
            foreach($menu as $key => $val){
                if($key == $primary){
                    $menu['primary'] = $menu[$key];
                }
                if($key == $secondary){
                    $menu['secondary'] = $menu[$key];
                }
            }
            $cache->tag('menu')->set('menucache', $menu, $this->cachetime);
        }
        $homeicon = '';
        foreach($menu as $mkey => $mitem){
            foreach($mitem as $key => $item){
                if(str_replace('/index.php/', '/', $item['url']) == str_replace('/index.php/', '/', $this->request->requestUri())){
                    $menu[$mkey][$key]['active'] = true;
                }
                else{
                    $menu[$mkey][$key]['active'] = false;
                }
                if($item['ishome'] == 1 && empty($homeicon)){
                    $homeicon = $item['icon'];
                }
                unset($menu[$mkey][$key]['ishome']);
            }
        }
        $breadcrumb = [];
        $findid = 0;
        $level = 0;
        if(isset($menu['primary'])){
            $pmenu = Ladder::makeLadderForHtml($menu['primary']);
            if(!empty($this->access)){
                foreach($pmenu as $key => $val){
                    if($val['cid'] > 0 && $val['cid'] == $this->box->get('posts.cid')){
                        $breadcrumb[] = [
                            'name' => $val['name'],
                            'url' => $val['url'],
                            'icon' => $val['icon'],
                            'active' => false
                        ];
                        if($this->access == 'archives'){
                            $breadcrumb[] = [
                                'name' => $this->box->get('posts.title'),
                                'url' => $this->box->get('posts.url'),
                                'icon' => '',
                                'active' => true
                            ];
                        }
                        $findid = $val['parent'];
                        $level = $val['level'];
                        break;
                    }
                }
            }
            else{
                foreach($pmenu as $key => $val){
                    if($val['active'] == true){
                        $breadcrumb[] = [
                            'name' => $val['name'],
                            'url' => $val['url'],
                            'icon' => $val['icon'],
                            'active' => false
                        ];
                        $findid = $val['parent'];
                        $level = $val['level'];
                        break;
                    }
                }
            }
            if(count($breadcrumb) > 0){
                while($level > 0){
                    foreach($pmenu as $key => $val){
                        if($val['id'] == $findid){
                            array_unshift($breadcrumb, [
                                'name' => $val['name'],
                                'url' => $val['url'],
                                'icon' => $val['icon'],
                                'active' => false
                            ]);
                            $findid = $val['parent'];
                            $level = $val['level'];
                            break;
                        }
                    }
                }
            }
            array_unshift($breadcrumb, [
                'name' => $this->lang->translate('Home'),
                'url' => $this->route->url('/'),
                'icon' => $homeicon,
                'active' => false
            ]);
            if($this->access == 'tag'){
                $breadcrumb[] = [
                    'name' => $this->lang->translate('Tags'),
                    'url' => '',
                    'icon' => '',
                    'active' => true
                ];
            }
            elseif($this->access == 'search'){
                $breadcrumb[] = [
                    'name' => $this->lang->translate('Search'),
                    'url' => '',
                    'icon' => '',
                    'active' => true
                ];
            }
            elseif($this->access == 'archive'){
                $breadcrumb[] = [
                    'name' => $this->lang->translate('Archive'),
                    'url' => '',
                    'icon' => '',
                    'active' => true
                ];
            }
        }
        foreach($menu as $key => $val){
            $menu[$key] = Ladder::makeLadder($val);
        }
        return [
            'menu' => $menu,
            'breadcrumb' => $breadcrumb
        ];
    }
    private function tourl($urlarr)
    {
        $url = '#';
        $cid = 0;
        $ishome = 0;
        switch($urlarr['method']){
            case 'custom':
                $url = $urlarr['url'];
                break;
            case 'home':
                $url = $urlarr['url'];
                $ishome = 1;
                break;
            case 'category':
                if(!empty($urlarr['slug'])){
                    $url = $this->route->url('index/category', ['name' => $urlarr['slug']]);
                }
                else{
                    $url = $this->route->url('index/category', ['id' => $urlarr['id']]);
                }
                $cid = $urlarr['id'];
                break;
            case 'page':
                if(!empty($urlarr['slug'])){
                    $url = $this->route->url('index/page', ['name' => $urlarr['slug']]);
                }
                else{
                    $url = $this->route->url('index/page', ['id' => $urlarr['id']]);
                }
                break;
        }
        return [$url, $cid, $ishome];
    }
    protected function slideFunc($param)
    {
        $slide = [];
        $slidegroup = [];
        foreach($param as $key => $val){
            if(empty($val['slug'])){
                $slide[] = [
                    'name' => $val['name'],
                    'image' => $val['image'],
                    'url' => $val['url'],
                    'description' => $val['description']
                ];
            }
            else{
                $slidegroup[$val['slug']][] = [
                    'name' => $val['name'],
                    'image' => $val['image'],
                    'url' => $val['url'],
                    'description' => $val['description']
                ];
            }
        }
        $this->box->set('slideGroup', $slidegroup);
        return $slide;
    }
    private function getTake($name)
    {
        $this->app->db->table('take')->field('takevalue')->where('takename', $name)->cache(1200, 'take_' . $name)->box('take')->find()->finish();
        return $this->box->get('take.takevalue');
    }
    private function totime()
    {
        if(empty($this->cache)){
            $this->cache = $this->app->get('cache');
        }
        if($this->cache->has('totime')){
            $totime = $this->cache->get('totime');
        }
        else{
            $totime = date('Y-m-d H:i:s');
            $this->cache->set('totime', $totime, $this->cachetime);
        }
        return $totime;
    }
    public function initialize()
    {
        $templang = $this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . $this->app->getConfig('template') . $this->DS . 'lang' . $this->DS . $this->app->getConfig('language') . '.php';
        if(is_file($templang)){
            $this->lang->load($templang);
        }
        $openedplugins = $this->app->getConfig('plugins');
        foreach($openedplugins as $key => $plugin){
            $this->handle->register($plugin);
        }
        $template = $this->app->getConfig('template');
        if(is_file($this->rootDir . $this->DS . 'public' . $this->DS . 'themes' . $this->DS . lcfirst($template) . $this->DS . ucfirst($template) . '.php')){
            $this->handle->register('jpwrt\theme\\' . lcfirst($template) . '\\' . ucfirst($template));
        }
    }
}