<?php
namespace modules\imagehoster;                      //Make sure namespace is same structure with parent directory

use \classes\Auth as Auth;                          //For authentication internal user
use \classes\JSON as JSON;                          //For handling JSON in better way
use \classes\CustomHandlers as CustomHandlers;      //To get default response message
use PDO;                                            //To connect with database

	/**
     * ImageHoster
     *
     * @package    modules/imagehoster
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/reSlim-modules-imagehoster/blob/master/LICENSE.md  MIT License
     */
    class ImageHoster {

        // model data
        var $id,$username,$token;

        // database var
        protected $db;

        //base var
        protected $basepath,$baseurl,$basemod;
        
        //construct database object
        function __construct($db=null) {
            if (!empty($db)) $this->db = $db;
            $this->baseurl = (($this->isHttps())?'https://':'http://').$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
            $this->basepath = $_SERVER['DOCUMENT_ROOT'].dirname($_SERVER['PHP_SELF']);
			$this->basemod = dirname(__FILE__);
        }

        //Detect scheme host
        function isHttps() {
            $whitelist = array(
                '127.0.0.1',
                '::1'
            );
            
            if(!in_array($_SERVER['REMOTE_ADDR'], $whitelist)){
                if (!empty($_SERVER['HTTP_CF_VISITOR'])){
                    return isset($_SERVER['HTTPS']) ||
                    ($visitor = json_decode($_SERVER['HTTP_CF_VISITOR'])) &&
                    $visitor->scheme == 'https';
                } else {
                    return isset($_SERVER['HTTPS']);
                }
            } else {
                return 0;
            }
        }

        /**
         * Build database table 
         */
        public function install(){
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $role = Auth::getRoleID($this->db,$this->token);
                if ($role == 1){
                    try {
                        $this->db->beginTransaction();
                        $sql = file_get_contents(dirname(__FILE__).'/imagehoster.sql');
                        $stmt = $this->db->prepare($sql);
                        if ($stmt->execute()) {
                            $data = [
                                'status' => 'success',
                                'code' => 'RS101',
                                'message' => CustomHandlers::getreSlimMessage('RS101',$this->lang)
                            ];	
                        } else {
                            $data = [
                                'status' => 'error',
                                'code' => 'RS201',
                                'message' => CustomHandlers::getreSlimMessage('RS201',$this->lang)
                            ];
                        }
                        $this->db->commit();
                    } catch (PDOException $e) {
                        $data = [
                            'status' => 'error',
                            'code' => $e->getCode(),
                            'message' => $e->getMessage()
                        ];
                        $this->db->rollBack();
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS404',
                        'message' => CustomHandlers::getreSlimMessage('RS404',$this->lang)
                    ];
                }
            } else {
                $data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
            }

			return JSON::encode($data,true);
			$this->db = null;
        }

        /**
         * Remove database table 
         */
        public function uninstall(){
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $role = Auth::getRoleID($this->db,$this->token);
                if ($role == 1){
                    try {
                        $this->db->beginTransaction();
                        $sql = "DROP TABLE IF EXISTS imagehoster_data;";
                        $stmt = $this->db->prepare($sql);
                        if ($stmt->execute()) {
                            $data = [
                                'status' => 'success',
                                'code' => 'RS104',
                                'message' => CustomHandlers::getreSlimMessage('RS104',$this->lang)
                            ];	
                            Auth::deleteCacheAll('deposit-*',30);
                        } else {
                            $data = [
                                'status' => 'error',
                                'code' => 'RS204',
                                'message' => CustomHandlers::getreSlimMessage('RS204',$this->lang)
                            ];
                        }
                        $this->db->commit();
                    } catch (PDOException $e) {
                        $data = [
                            'status' => 'error',
                            'code' => $e->getCode(),
                            'message' => $e->getMessage()
                        ];
                        $this->db->rollBack();
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS404',
                        'message' => CustomHandlers::getreSlimMessage('RS404',$this->lang)
                    ];
                }
            } else {
                $data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
            }

			return JSON::encode($data,true);
			$this->db = null;
        }

        //Get modules information
        public function viewInfo(){
            return file_get_contents($this->basemod.'/package.json');
        }

        public function deleteData($username=''){
            $newusername = strtolower($this->username);
            try {
                $this->db->beginTransaction();

                if (!empty($username)) {
                    $sql = "DELETE FROM imagehoster_data WHERE ID= BINARY :id AND Created_by=:username;";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':id', $this->id, PDO::PARAM_STR);
                    $stmt->bindParam(':username', $newusername, PDO::PARAM_STR);
                } else {
                    $sql = "DELETE FROM imagehoster_data WHERE ID= BINARY :id;";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bindParam(':id', $this->id, PDO::PARAM_STR);
                }

                if ($stmt->execute()) {
                    $data = [
                        'status' => 'success',
                        'code' => 'RS104',
                        'message' => CustomHandlers::getreSlimMessage('RS104',$this->lang)
                    ];	
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS204',
                        'message' => CustomHandlers::getreSlimMessage('RS204',$this->lang)
                    ];
                }
                $this->db->commit();
            } catch (PDOException $e) {
                $data = [
                    'status' => 'error',
                    'code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
                $this->db->rollBack();
            }
            return $data;
        }

        public function delete() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $roles = Auth::getRoleID($this->db,$this->token);
                if ($roles <= 2){
                    $data = $this->deleteData();
                } else if ($roles == 5){
                    $data = [
                        'status' => 'error',
                        'code' => 'RS404',
                        'message' => CustomHandlers::getreSlimMessage('RS404',$this->lang)
                    ];
                } else {
                    if(!empty($this->username)){
                        $data = $this->deleteData($this->username);
                    } else {
                        $data = [
                            'status' => 'error',
                            'code' => 'RS802',
                            'message' => CustomHandlers::getreSlimMessage('RS802',$this->lang)
                        ];
                    }
                }
            } else {
                $data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
            }
			return JSON::encode($data,true);
			$this->db = null;
        }

    }