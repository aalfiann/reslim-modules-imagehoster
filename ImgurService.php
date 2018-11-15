<?php
namespace modules\imagehoster;                                  //Make sure namespace is same structure with parent directory
use \modules\imagehoster\Imgur as Imgur;
use \modules\proxylist\PubProxy as PubProxy;
use \modules\flexibleconfig\FlexibleConfig as FlexibleConfig;   
use \classes\Auth as Auth;                                      //For authentication internal user
use \classes\JSON as JSON;                                      //For handling JSON in better way
use \classes\CustomHandlers as CustomHandlers;                  //To get default response message
use \classes\Validation as Validation;
use PDO;                                                        //To connect with database

	/**
     * ImgurService class
     *
     * @package    modules/imagehoster
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/reSlim-modules-imagehoster/blob/master/LICENSE.md  MIT License
     */
    class ImgurService {

        //data var
        var $id,$clientid,$validid,$image,$album,$type,$name,$title,$description;

        //multilanguage
        var $lang;

        //search var
        var $search,$firstdate,$lastdate;

        //pagination var
		var $page,$itemsPerPage;

        var $username,$token;

        //config var
        var $keyconfig = 'imgurclientid';
        var $login = 'a94c8d5890af13e';

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
            if (is_dir('../modules/flexibleconfig')){
                $this->login = $this->setLogin();
            }
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

        private function getDataLogin(){
            $fc = new FlexibleConfig($this->db);
            return $fc->readConfig($this->keyconfig);
        }

        private function setLogin(){
            if(empty($this->getDataLogin())){
                $fc = new FlexibleConfig($this->db);
                $fc->insertConfig($this->keyconfig,$this->login,'imagehoster_module','Default Client-ID to access the Imgur API.');
                return $this->getDataLogin();
            }
            return $this->getDataLogin();
        }

        /**
         * Modify json data string in some field array to be nice json data structure
		 * 
		 * Note:
		 * - When you put json into database, then you load it with using PDO:fetch() or PDO::fetchAll() it will be served as string inside some field array.
		 * - So this function is make you easier to modify the json string to become nice json data structure automatically.
		 * - This function is well tested at here >> https://3v4l.org/G8Jaa
         * 
         * @var data is the data array
         * @var jsonfield is the field which is contains json string
         * @var setnewfield is to put the result of modified json string in new field
         * @return mixed array or string (if the $data is string then will return string)
         */
		private function modifyJsonStringInArray($data,$jsonfield,$setnewfield=""){
			if (is_array($data)){
				if (count($data) == count($data, COUNT_RECURSIVE)) {
					foreach($data as $value){
						if(!empty($setnewfield)){
							if (is_array($jsonfield)){
								for ($i=0;$i<count($jsonfield);$i++){
									if (isset($data[$jsonfield[$i]])){
										$data[$setnewfield[$i]] = json_decode($data[$jsonfield[$i]]);
									}
								}
							} else {
								if (isset($data[$jsonfield])){
									$data[$setnewfield] = json_decode($data[$jsonfield]);
								}
							}
						} else {
							if (is_array($jsonfield)){
								for ($i=0;$i<count($jsonfield);$i++){
									if (isset($data[$jsonfield[$i]])){
										if (is_string($data[$jsonfield[$i]])) {
                                            $decode = json_decode($data[$jsonfield[$i]]);
                                            if (!empty($decode)) $data[$jsonfield[$i]] = $decode;
                                        }
									}
								}
							} else {
								if (isset($data[$jsonfield])){
                                    $decode = json_decode($data[$jsonfield]);
                                    if (!empty($decode)) $data[$jsonfield] = $decode;
								}
							}
						}
					}
				} else {
					foreach($data as $key => $value){
						$data[$key] = $this->modifyJsonStringInArray($data[$key],$jsonfield,$setnewfield);
					}
				}
			}
			return $data;
        }

        public function createTable(){
            return "CREATE TABLE IF NOT EXISTS `imagehoster_data` (
                `ID` varchar(20) NOT NULL,
                `ClientID` varchar(20) NOT NULL,
                `Title` varchar(255) DEFAULT NULL,
                `Link` varchar(255) DEFAULT NULL,
                `Type` varchar(50) DEFAULT NULL,
                `Size` double DEFAULT NULL,
                `Width` double DEFAULT NULL,
                `Height` double DEFAULT NULL,
                `Data` text NOT NULL,
                `Created_at` datetime NOT NULL,
                `Created_by` varchar(50) NOT NULL,
                `Updated_at` datetime DEFAULT NULL,
                `Updated_by` varchar(50) DEFAULT NULL,
                `Updated_sys` datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`ID`,`ClientID`),
                KEY `Title` (`Title`) USING BTREE,
                KEY `Created_by` (`Created_by`),
                KEY `Created_at` (`Created_at`)
              ) ENGINE=InnoDB DEFAULT CHARSET=latin1;
              SET FOREIGN_KEY_CHECKS=1;";
        }

        public function isLimitReached(){
            $sql = $this->createTable();
			$stmt = $this->db->prepare($sql);
            $stmt->execute();
            $stmt->closeCursor();
            if ($this->countLimit() >= 1000 ){
                return true;
            }
            return false;
        }

        public function countLimit(){
            $img = new Imgur();
            $validclient = (!empty($this->clientid)?$this->clientid:$this->login);
            $img->clientid = $validclient;
            $this->validid = $img->getClientID();
            $sqlcount = "SELECT count(a.ID) as TotalRow from imagehoster_data a where date(a.Created_at) = '".date('Y-m-d')."' and a.ClientID=:clientid;";
            $stmt2 = $this->db->prepare($sqlcount);
            $stmt2->bindParam(':clientid', $this->validid, PDO::PARAM_STR);
            if ($stmt2->execute()) {	
                $single = $stmt2->fetch();
                return $single['TotalRow'];
            }
        }
        
        public function insertDataImage($id,$datajson,$title='',$link='',$type='',$size=0,$width=0,$height=0){
            $newusername = strtolower($this->username);
            $newclientid = strtolower($this->clientid);
            try{
                $sql = $this->createTable()."INSERT INTO imagehoster_data (ID,ClientID,Title,Link,Type,Size,Width,Height,Data,Created_at,Created_by) 
        					VALUES (:id,:clientid,:title,:link,:type,:size,:width,:height,:datajson,current_timestamp,:username);";
                $stmt = $this->db->prepare($sql);
                $stmt->bindParam(':id', $id, PDO::PARAM_STR);
                $stmt->bindParam(':clientid', $newclientid, PDO::PARAM_STR);
                $stmt->bindParam(':datajson', $datajson, PDO::PARAM_STR);
                $stmt->bindParam(':username', $newusername, PDO::PARAM_STR);
                $stmt->bindParam(':title', $title, PDO::PARAM_STR);
                $stmt->bindParam(':link', $link, PDO::PARAM_STR);
                $stmt->bindParam(':type', $type, PDO::PARAM_STR);
                $stmt->bindParam(':size', $size, PDO::PARAM_STR);
                $stmt->bindParam(':width', $width, PDO::PARAM_STR);
                $stmt->bindParam(':height', $height, PDO::PARAM_STR);
                $stmt->execute();
            } catch (Exception $e){
                return false;
            }
            return true;
        }

        //Process upload
        public function processUpload(){
            $img = new Imgur();
            $img->clientid = $this->validid;
            $img->image = $this->image;
            if(!empty($this->title)) $img->title = $this->title;
            if(!empty($this->description)) $img->description = $this->description;
            if(!empty($this->album)) $img->album = $this->album;
            if(!empty($this->type)) $img->type = $this->type;
            if(!empty($this->name)) $img->name = $this->name;
            if(!empty($img->clientid)){
                $imagejson = $img->upload()->getAll();
                if(!empty($imagejson)){
                    $image = json_decode($imagejson,true);
                    if($image['success']){
                        $data = [
                            'result' => $image['data'],
                            'status' => 'success',
                            'code' => 'RS101',
                            'message' => CustomHandlers::getreSlimMessage('RS101',$this->lang)
                        ];
                        $this->insertDataImage(
                            $image['data']['id'],
                            json_encode($image['data']),
                            $image['data']['title'],
                            $image['data']['link'],
                            $image['data']['type'],
                            $image['data']['size'],
                            $image['data']['width'],
                            $image['data']['height']
                        );
                    } else {
                        $data = [
                            'status' => 'error',
                            'code' => 'RS201',
                            'message' => CustomHandlers::getreSlimMessage('RS201',$this->lang)
                        ];
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS910',
                        'message' => CustomHandlers::getreSlimMessage('RS910',$this->lang)
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'code' => 'RS802',
                    'message' => CustomHandlers::getreSlimMessage('RS802',$this->lang)
                ];
            }
            return $data;
        }

        //Process upload with auto rotate proxy
        public function processUploadRotate(){
            $proxy = new PubProxy;
            $proxy->last_check = 1;
            $proxy->type = 'http';
            $proxy->https = true;
            $proxy->post = true;
            $proxy->google = true;
            $proxy->referer = true;
            $proxy->cookies = true;
            $proxy->level = 'elite';
            $dataproxy = $proxy->make()->getProxy();
            $img = new Imgur();
            $img->proxy = $dataproxy;
            if(!empty($this->validid)){
                $img->clientid = $this->validid;
            } else {
                $img->clientid = (!empty($this->clientid)?$this->clientid:$this->login);
            }
            $img->image = $this->image;
            if(!empty($this->title)) $img->title = $this->title;
            if(!empty($this->description)) $img->description = $this->description;
            if(!empty($this->album)) $img->album = $this->album;
            if(!empty($this->type)) $img->type = $this->type;
            if(!empty($this->name)) $img->name = $this->name;
            if(!empty($img->clientid)){
                $imagejson = $img->upload()->getAll();
                if(!empty($imagejson)){
                    $image = json_decode($imagejson,true);
                    if($image['success']){
                        $data = [
                            'result' => $image['data'],
                            'status' => 'success',
                            'code' => 'RS101',
                            'message' => CustomHandlers::getreSlimMessage('RS101',$this->lang),
                            'network' => $dataproxy
                        ];
                        $this->insertDataImage(
                            $image['data']['id'],
                            json_encode($image['data']),
                            $image['data']['title'],
                            $image['data']['link'],
                            $image['data']['type'],
                            $image['data']['size'],
                            $image['data']['width'],
                            $image['data']['height']
                        );
                    } else {
                        $data = [
                            'status' => 'error',
                            'code' => 'RS201',
                            'message' => CustomHandlers::getreSlimMessage('RS201',$this->lang),
                            'network' => $dataproxy
                        ];
                    }
                } else {
                    $data = [
                        'status' => 'error',
                        'code' => 'RS910',
                        'message' => CustomHandlers::getreSlimMessage('RS910',$this->lang),
                        'network' => $dataproxy
                    ];
                }
            } else {
                $data = [
                    'status' => 'error',
                    'code' => 'RS802',
                    'message' => CustomHandlers::getreSlimMessage('RS802',$this->lang)
                ];
            }
            return $data;
        }

        public function uploadImage(){
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $roles = Auth::getRoleID($this->db,$this->token);
                if ($roles != 5){
                    if($this->isLimitReached()){
                        $data = $this->processUploadRotate();
                    } else {
                        $data = $this->processUpload();
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

        public function uploadImageRotate(){
            if (Auth::validToken($this->db,$this->token,$this->username)){
                $roles = Auth::getRoleID($this->db,$this->token);
                if ($roles != 5){
                    $data = $this->processUploadRotate();
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

        public function indexData() {
            $search = "%$this->search%";
			//count total row
			$sqlcountrow = "SELECT count(a.ID) AS TotalRow 
				FROM imagehoster_data a
				WHERE 
                    ".(!empty($this->firstdate) && !empty($this->lastdate)?'date(a.Created_at) BETWEEN :firstdate and :lastdate and ':'')."
                    (a.ID LIKE :search OR a.ClientID LIKE :search OR a.Title LIKE :search OR a.Created_by LIKE :search)
				ORDER BY a.Created_at DESC;";
			$stmt = $this->db->prepare($sqlcountrow);		
            $stmt->bindParam(':search', $search, PDO::PARAM_STR);
            if (!empty($this->firstdate) && !empty($this->lastdate)){
                $stmt->bindParam(':firstdate', $this->firstdate, PDO::PARAM_STR);
                $stmt->bindParam(':lastdate', $this->lastdate, PDO::PARAM_STR);
            }
				
			if ($stmt->execute()) {	
    			if ($stmt->rowCount() > 0){
					$single = $stmt->fetch();
						
					// Paginate won't work if page and items per page is negative.
					// So make sure that page and items per page is always return minimum zero number.
					$newpage = Validation::integerOnly($this->page);
					$newitemsperpage = Validation::integerOnly($this->itemsPerPage);
					$limits = (((($newpage-1)*$newitemsperpage) <= 0)?0:(($newpage-1)*$newitemsperpage));
					$offsets = (($newitemsperpage <= 0)?0:$newitemsperpage);
					// Query Data
					$sql = "SELECT 
                            a.ID,a.ClientID,a.Title,a.Link,a.Type,a.Size,a.Width,a.Height,a.Created_at,a.Created_by,a.Updated_at,a.Updated_by,a.Updated_sys 
                        FROM imagehoster_data a
                        WHERE 
                            ".(!empty($this->firstdate) && !empty($this->lastdate)?'date(a.Created_at) BETWEEN :firstdate and :lastdate and ':'')."
                            (a.ID LIKE :search OR a.ClientID LIKE :search OR a.Title LIKE :search OR a.Created_by LIKE :search)
						ORDER BY a.Created_at DESC LIMIT :limpage , :offpage;";
					$stmt2 = $this->db->prepare($sql);
					$stmt2->bindParam(':search', $search, PDO::PARAM_STR);
					$stmt2->bindValue(':limpage', (INT) $limits, PDO::PARAM_INT);
                    $stmt2->bindValue(':offpage', (INT) $offsets, PDO::PARAM_INT);
                    if (!empty($this->firstdate) && !empty($this->lastdate)){
                        $stmt2->bindParam(':firstdate', $this->firstdate, PDO::PARAM_STR);
                        $stmt2->bindParam(':lastdate', $this->lastdate, PDO::PARAM_STR);
                    }
					
					if ($stmt2->execute()){
						$pagination = new \classes\Pagination();
						$pagination->totalRow = $single['TotalRow'];
						$pagination->page = $this->page;
						$pagination->itemsPerPage = $this->itemsPerPage;
						$pagination->fetchAllAssoc = $stmt2->fetchAll(PDO::FETCH_ASSOC);
						$data = $pagination->toDataArray();
					} else {
						$data = [
        		    		'status' => 'error',
		    		    	'code' => 'RS202',
				    	    'message' => CustomHandlers::getreSlimMessage('RS202',$this->lang)
						];	
					}			
				} else {
    	    		$data = [
            			'status' => 'error',
	    	    		'code' => 'RS601',
    			    	'message' => CustomHandlers::getreSlimMessage('RS601',$this->lang)
					];
		    	}          	   	
			} else {
				$data = [
        			'status' => 'error',
					'code' => 'RS202',
	        		'message' => CustomHandlers::getreSlimMessage('RS202',$this->lang)
				];
			}	
        
			return $data;
	        $this->db= null;
        }

        public function readData(){
            $sql = "SELECT 
                    a.ID,a.ClientID,a.Title,a.Link,a.Type,a.Size,a.Width,a.Height,a.Created_at,a.Created_by,a.Updated_at,a.Updated_by,a.Updated_sys,a.Data 
				FROM imagehoster_data a
                WHERE a.ID = BINARY :id LIMIT 1;";
				
			$stmt = $this->db->prepare($sql);		
			$stmt->bindParam(':id', $this->id, PDO::PARAM_STR);
			if ($stmt->execute()) {	
	    	    if ($stmt->rowCount() > 0){
                    $results = $this->modifyJsonStringInArray($stmt->fetchAll(PDO::FETCH_ASSOC),['Data']);
					$data = [
			            'result' => $results, 
    			        'status' => 'success', 
		           	    'code' => 'RS501',
    		        	'message' => CustomHandlers::getreSlimMessage('RS501',$this->lang)
					];
			    } else {
        		    $data = [
        		    	'status' => 'error',
	        		    'code' => 'RS601',
    		    	    'message' => CustomHandlers::getreSlimMessage('RS601',$this->lang)
					];
	    	    }          	   	
			} else {
				$data = [
        			'status' => 'error',
					'code' => 'RS202',
	        		'message' => CustomHandlers::getreSlimMessage('RS202',$this->lang)
				];
            }
            return $data;
        }

        public function read() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
				$data = $this->readData();
			} else {
                $data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
			}
			return JSON::safeEncode($data,true);
	        $this->db= null;
        }
        
        public function index() {
            if (Auth::validToken($this->db,$this->token,$this->username)){
				$data = $this->indexData();
			} else {
				$data = [
	    			'status' => 'error',
					'code' => 'RS401',
        	    	'message' => CustomHandlers::getreSlimMessage('RS401',$this->lang)
				];
			}
			return JSON::safeEncode($data,true);
	        $this->db= null;
        }

    }