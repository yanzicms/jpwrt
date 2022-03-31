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
use jsnpp\Database;
use jsnpp\Tools;
class Install extends Controller
{
    public function index($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check->param($param['lang'])->run('changlang')->output->display('ok')->finish();
        $this->view->assign('share')->display();
    }
    public function changlang($lang)
    {
        if($this->app->getConfig('language') != $lang){
            $this->app->writeConfig('language', $lang);
        }
    }
    public function detect()
    {
        $this->app->entrance->check('post')->check->run('detectFunc')->box('result')->output->display(':box(result)')->finish();
        $this->view->assign('share')->display();
    }
    public function dbinfo($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')->check->param($param)->run('dbinfoFunc')->box('result')->output->display(':box(result)')->finish();
        $this->view->assign('share')->display();
    }
    public function account($param = '')
    {
        !$this->request->isPost() || $this->app->entrance->check('post')
            ->check($param['username'], [
                'require' => $this->lang->translate('Username must be filled in'),
                'alphaNumUnder' => $this->lang->translate('Username starts with a letter and can only contain letters, numbers and underscores')
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
            ->db->table('users')->insert([
                'id' => 1,
                'username' => $param['username'],
                'nickname' => $param['username'],
                'publicname' => $param['username'],
                'password' => md5($this->box->get('random') . $param['password']),
                'email' => $param['email'],
                'createtime' => date("Y-m-d H:i:s"),
                'lastip' => $this->request->ip(),
                'randomcode' => ':box(random)',
                'usertype' => 'administrator'
            ])->table('take')->data('takename', 'admin-email')->data('takevalue', serialize(['email' => $param['email'], 'active' => 1]))->data('taketype', 'need')->data('backstage', 'general')->insert()->output->display(':ok')->finish();
        $this->view->assign('share')->display();
    }
    public function finish()
    {
        $this->app->writeCustomize('site', [
            'siteTitle' => 'JPWRT',
            'tagline' => '',
            'siteKeywords' => '',
            'siteDescription' => '',
            'siteAddress' => $this->route->rootUrlFull(),
            'template' => 'jpwrt',
            'permalink' => 'numeric',
            'permalinkCustom' => '',
            'permalinkCategory' => '',
            'permalinkPage' => '',
            'permalinkTag' => '',
            'plugins' => [],
            'homepage' => 'newest',
            'staticpage' => 0,
            'homeshow' => 10,
            'pageshow' => 10,
            'homeorder' => 'last',
            'categoryorder' => 'last',
            'archiveorder' => 'last',
            'searchorder' => 'last',
            'tagorder' => 'last',
            'creationtime' => date('Y-m-d H:i:s')
        ]);
        $this->app->db->table('take')->data([
            [
                'takename' => 'jswrt-menu',
                'takevalue' => serialize([
                    'primary' => [
                        'name' => '',
                        'slug' => ''
                    ],
                    'secondary' => [
                        'name' => '',
                        'slug' => ''
                    ],
                    'other' => []
                ]),
                'taketype' => 'load',
                'backstage' => 'menus'
            ],
            [
                'takename' => 'slide-width',
                'takevalue' => 1200,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'slide-height',
                'takevalue' => 500,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'thumbnail-width',
                'takevalue' => 150,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'thumbnail-height',
                'takevalue' => 150,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'medium-width',
                'takevalue' => 350,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'medium-height',
                'takevalue' => 350,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'large-width',
                'takevalue' => 1024,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'large-height',
                'takevalue' => 1024,
                'taketype' => 'need',
                'backstage' => 'media'
            ],
            [
                'takename' => 'membership',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'general'
            ],
            [
                'takename' => 'role',
                'takevalue' => 'subscriber',
                'taketype' => 'need',
                'backstage' => 'general'
            ],
            [
                'takename' => 'logo',
                'takevalue' => '',
                'taketype' => 'load',
                'backstage' => 'general'
            ],
            [
                'takename' => 'filing',
                'takevalue' => '',
                'taketype' => 'need',
                'backstage' => 'general'
            ],
            [
                'takename' => 'customcode',
                'takevalue' => '',
                'taketype' => 'need',
                'backstage' => 'general'
            ],
            [
                'takename' => 'dashboardguide',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'general'
            ],
            [
                'takename' => 'guide',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'general'
            ],
            [
                'takename' => 'feedshow',
                'takevalue' => 10,
                'taketype' => 'need',
                'backstage' => 'reading'
            ],
            [
                'takename' => 'feedinclude',
                'takevalue' => 'summary',
                'taketype' => 'need',
                'backstage' => 'reading'
            ],
            [
                'takename' => 'searchengine',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'reading'
            ],
            [
                'takename' => 'allowcomments',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'nameemail',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'login',
                'takevalue' => 0,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'close',
                'takevalue' => 0,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'closeday',
                'takevalue' => 15,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'opennested',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'nested',
                'takevalue' => 5,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'pagingdisplay',
                'takevalue' => 0,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'perdisplay',
                'takevalue' => 10,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'defaultdisplay',
                'takevalue' => 'last',
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'approval',
                'takevalue' => 0,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'previousapproved',
                'takevalue' => 1,
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'reviewkeywords',
                'takevalue' => '',
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'rejectkeywords',
                'takevalue' => '',
                'taketype' => 'need',
                'backstage' => 'discussion'
            ],
            [
                'takename' => 'smtp',
                'takevalue' => '',
                'taketype' => 'need',
                'backstage' => 'smtp'
            ],
            [
                'takename' => 'activation',
                'takevalue' => '',
                'taketype' => 'need',
                'backstage' => ''
            ],
        ])->insert()->finish();
        if(function_exists('apache_get_modules') && in_array('mod_rewrite', apache_get_modules())){
            $this->app->writeConfig('rewrite', true);
        }
        file_put_contents($this->rootDir . $this->DS . 'config' . $this->DS . 'locked.php', '<?php');
        $this->view->assign('share')->display();
    }
    public function initialize()
    {
        if(is_file($this->rootDir . $this->DS . 'config' . $this->DS . 'locked.php')){
            $this->route->redirect('/');
            exit();
        }
    }
    public function dbinfoFunc(Database $database, $param)
    {
        $this->app->writeDb([
            'type' => $param['dbtype'],
            'hostname' => $param['hostname'],
            'database' => $param['database'],
            'username' => $param['username'],
            'password' => $param['password'],
            'hostport' => $param['hostport'],
            'charset' => $param['charset'],
            'prefix' => $param['prefix'],
        ]);
        if(!$database->hasDb($param['database'])){
            try{
                $database->newDb($param['database']);
            }
            catch(\PDOException $e){
                return [
                    'result' => 'err',
                    'code' => 1,
                    'message' => $this->lang->translate('The database information is wrong or the database does not exist')
                ];
            }
        }
        $data = Tools::load($this->rootDir . $this->DS . 'config' . $this->DS . 'data.php');
        try{
            foreach($data as $key => $val){
                $database->newTable($key, $val);
            }
            return [
                'result' => 'ok',
                'code' => 0,
                'message' => ''
            ];
        }
        catch(\Exception $e){
            if(stripos($e->getMessage(), 'already exists') !== false){
                return [
                    'result' => 'err',
                    'code' => 2,
                    'message' => $this->lang->translate('Database table already exists')
                ];
            }
            return [
                'result' => 'err',
                'code' => 3,
                'message' => $this->lang->translate('Database information error') . $e->getMessage()
            ];
        }
    }
    public function share()
    {
        $this->view->assign('webroot', $this->route->rootUrl())->assign('version', $this->app->getConfig('version'));
    }
    public function detectFunc()
    {
        $result = [];
        $phpver = @phpversion();
        if(version_compare($phpver, '5.6.0', '<')){
            $result[] = [
                'content' => $this->lang->translate('PHP version'),
                'situation' => $phpver,
                'requirement' => '>= 5.6.0',
            ];
        }
        if(!class_exists('pdo') || !extension_loaded('pdo_mysql')){
            $result[] = [
                'content' => $this->lang->translate('PDO support'),
                'situation' => $this->lang->translate('Not support'),
                'requirement' => $this->lang->translate('Request support'),
            ];
        }
        if(!function_exists('gd_info')){
            $result[] = [
                'content' => $this->lang->translate('GD support'),
                'situation' => $this->lang->translate('Not support'),
                'requirement' => $this->lang->translate('Request support'),
            ];
        }
        if(!function_exists('curl_init')){
            $result[] = [
                'content' => $this->lang->translate('Curl support'),
                'situation' => $this->lang->translate('Not support'),
                'requirement' => $this->lang->translate('Request support'),
            ];
        }
        if(!class_exists('ZipArchive')){
            $result[] = [
                'content' => $this->lang->translate('ZipArchive support'),
                'situation' => $this->lang->translate('Not support'),
                'requirement' => $this->lang->translate('Request support'),
            ];
        }
        if(!function_exists('session_start')){
            $result[] = [
                'content' => $this->lang->translate('Session support'),
                'situation' => $this->lang->translate('Not support'),
                'requirement' => $this->lang->translate('Request support'),
            ];
        }
        $farr = [
            'assist', 'assist/cache', 'assist/comp', 'assist/log', 'config', 'public/data'
        ];
        foreach($farr as $key => $val){
            $val = str_replace('/', $this->DS, $val);
            $fpath = $this->rootDir . $this->DS . $val;
            if(!is_dir($fpath)){
                @mkdir($fpath, 0777, true);
            }
            if(!is_writeable($fpath) || !is_readable($fpath)){
                $result[] = [
                    'content' => $this->lang->translate('Read and write') . ': ' . $val,
                    'situation' => $this->lang->translate('Not support'),
                    'requirement' => $this->lang->translate('Request support'),
                ];
            }
        }
        if(count($result) > 0){
            return [
                'result' => 'err',
                'data' => $result
            ];
        }
        return [
            'result' => 'ok',
            'data' => $result
        ];
    }
}