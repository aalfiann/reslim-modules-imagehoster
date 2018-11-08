<?php
namespace modules\imagehoster;
    /**
     * An Imgur API class
     *
     * @package    Imgur API Class
     * @author     M ABD AZIZ ALFIAN <github.com/aalfiann>
     * @copyright  Copyright (c) 2018 M ABD AZIZ ALFIAN
     * @license    https://github.com/aalfiann/reslim-modules-imagehoster/blob/master/LICENSE.md  MIT License
     */
    class Imgur {

        // model variable that use in class
        var $id,$clientid,$image,$album,$type,$name,$title,$description,$response,$resultArray=null,$proxy='',$proxyauth='';

        /**
         * Set image
         * @param image = A binary file, base64 data, or a URL for an image. (up to 10MB)
         * @return this for chaining
         */
        public function setImage($image=''){
            $this->image = $image;
            return $this;
        }

        /**
         * Set album
         * @param album = The id of the album you want to add the image to. For anonymous albums, album should be the deletehash that is returned at creation.
         * @return this for chaining
         */
        public function setAlbum($album=''){
            if(!empty($album)) $this->album = $album;
            return $this;
        }

        /**
         * Set type
         * @param type = The type of the file that's being sent; file, base64 or URL.
         * @return this for chaining
         */
        public function setType($type=''){
            if(!empty($type)) $this->type = $type;
            return $this;
        }

        /**
         * Set name
         * @param name = The name of the file, this is automatically detected if uploading a file with a POST and multipart / form-data.
         * @return this for chaining
         */
        public function setName($name=''){
            if(!empty($name)) $this->name = $name;
            return $this;
        }

        /**
         * Set title
         * @param title = The title of the image.
         * @return this for chaining
         */
        public function setTitle($title=''){
            if(!empty($title)) $this->title = $title;
            return $this;
        }

        /**
         * Set description
         * @param description = The description of the image.
         * @return this for chaining
         */
        public function setDescription($description=''){
            if(!empty($description)) $this->description = $description;
            return $this;
        }

        /**
         * Set Client-ID
         * @param clientid = is the id of imgur application, if you don't have it, just create at here >> https://api.imgur.com/oauth2/addclient
         * @return this for chaining
         */
        public function setClientID($clientid=''){
            if(!empty($clientid)) $this->clientid = $clientid;
            return $this;
        }

        /**
         * Get Client-ID (if clientid is on array, then this will rotate the clientid to save quota of imgur limit rate)
         * @return string
         */
        public function getClientID(){
            if (is_array($this->clientid)){
                return $this->clientid[mt_rand(0, count($this->clientid) - 1)];
            }
            $cid = explode(',',$this->clientid);
            return trim($cid[mt_rand(0, count($cid) - 1)]);
        }

        /**
         * Process upload image
         * @return this for chaining
         */
        public function upload(){
            $this->response = $this->requestUpload();
            if (!empty($this->response)) $this->resultArray = json_decode($this->response, true);
            return $this;
        }

        /**
         * Make array
         * @return array
         */
        public function makeArray() {
            if (!empty($this->resultArray)) return $this->resultArray;
            return null;
        }

        /**
         * Get All data result from Imgur
         * @return string
         */
        public function getAll() {
            if (!empty($this->response)) return $this->response;
            return null;
        }

        /**
         * Get image date
         * @return string
         */
        public function getDate() {
            if (!empty($this->resultArray["data"]['datetime'])) return $this->resultArray["data"]["datetime"];
            return null;
        }

        /**
         * Get image id
         * @return string
         */
        public function getID() {
            if (!empty($this->resultArray["data"]["id"])) return $this->resultArray["data"]["id"];
            return null;
        }

        /**
         * Get image link
         * @return string
         */
        public function getLink() {
            if (!empty($this->resultArray["data"]['link'])) return $this->resultArray["data"]["link"];
            return null;
        }

        /**
         * Get image type
         * @return string
         */
        public function getType() {
            if (!empty($this->resultArray["data"]['type'])) return $this->resultArray["data"]["type"];
            return null;
        }

        /**
         * Get image title
         * @return string
         */
        public function getTitle() {
            if (!empty($this->resultArray["data"]['title'])) return $this->resultArray["data"]["title"];
            return null;
        }

        /**
         * Get image description
         * @return string
         */
        public function getDescription() {
            if (!empty($this->resultArray["data"]['description'])) return $this->resultArray["data"]["description"];
            return null;
        }

        /**
         * Get status
         * @return string
         */
        public function getStatus() {
            if (!empty($this->resultArray["status"]) && !empty($this->resultArray["success"])) {
                return json_encode([
                    'success' => $this->resultArray["success"],
                    'status' => $this->resultArray["status"],
                ]);
            }
            return null;
        }

        /**
         * Get another properties by name
         * @return string
         */
        public function getProperty($name) {
            if (!empty($this->resultArray["data"][$name])) return $this->resultArray["data"][$name];
            return null;
        }

        /**
         * Make a request translation service to the Imgur
         * @return string json data 
         */
        public function requestUpload() {
            if (!empty($this->clientid) && !empty($this->image)){
                $url = "https://api.imgur.com/3/image";
                $fields = array();
                $fields['image'] = urlencode($this->image);
                if(!empty($this->album)) $fields['album'] = urlencode($this->album);
                if(!empty($this->type)) $fields['type'] = urlencode($this->type);
                if(!empty($this->name)) $fields['name'] = urlencode($this->name);
                if(!empty($this->title)) $fields['title'] = urlencode($this->title);
                if(!empty($this->description)) $fields['description'] = urlencode($this->description);

                // Set header
                $headers = array();
                $headers[] = 'Authorization: Client-ID '.$this->getClientID();
        
                // Build data parameter for the POST
                $fields_string = "";
                foreach ($fields as $key => $value) {
                    $fields_string .= $key . '=' . $value . '&';
                }
                rtrim($fields_string, '&');
                // Open connection
                $ch = curl_init();

                // Set the url, number of POST vars, POST data
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_POST, count($fields));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                if (!empty($this->proxy)) curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
                if (!empty($this->proxyauth)) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $this->proxyauth);
                curl_setopt($ch, CURLOPT_HTTPHEADER,$headers);
                curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
                // Execute post
                $result = curl_exec($ch);
                // Close connection
                curl_close($ch);
                return $result;
            }
            return '';    
        }
    }