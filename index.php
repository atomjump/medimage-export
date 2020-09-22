<?php
    include_once("classes/cls.pluginapi.php");
    

    
    
    class plugin_medimage_export
    {
       
        private function get_medimage_config() {
				 if(!isset($medimage_config)) {
					  //Get global plugin config - but only once
					  $data = file_get_contents (dirname(__FILE__) . "/config/config.json");
					  if($data) {
						   $medimage_config = json_decode($data, true);
						   if(!isset($medimage_config)) {
						       $msg = "Error: MedImage config/config.json is not valid JSON.";
						       error_log($msg);
						       echo $msg;
						       exit(0);
						   }
					  } else {
					  		$msg = "Error: MedImage config/config.json in medimage_export plugin.";
					  		error_log($msg);
						   echo $msg;
						   exit(0);
					  }
				 }
       		
        		return $medimage_config;
        }
       
 		  public function on_message($message_forum_id, $message, $message_id, $sender_id, $recipient_id, $sender_name, $sender_email, $sender_phone)
        {
            global $cnf;
            $api = new cls_plugin_api();
                      
  
  
            
            //TODO: notify if there is no id when a photo is detected.
            //Check for existence of photo in message and initiate a sending process for that photo
            //Check if we don't have a paired MedImage Server stored, and warn user with a message
            //Check for a pairing with the MedImage Server i.e 'pair aBc1' or 'pr aBc1'
            //TODO: generalise languages here
 
 				$url_matching = "atomjump";		//Works with based jpgs on atomjump which include e.g. 'atomjump' in their strings.
				if($cnf['uploads']['replaceHiResURLMatch']) $url_matching = $cnf['uploads']['replaceHiResURLMatch'];			
 				$preg_search = "/.*?" . $url_matching ."(.*?)\.jpg/i";
				error_log($preg_search);
				error_log($message);
				preg_match_all($preg_search, $message, $matches);
				error_log(json_encode($matches));
				if(count($matches[0]) > 0) {
						//Yes we have at least one image
						
						//Check if we have a pairing
						if(isset($_COOKIE['medimage-server'])) {
						
							//Check if we already have an ID, and if not send a message to say we have sent the image as 'image', but you 
							//should set the id with 'id [patientId] [optional description tags]'
							
							
							for($cnt = 0; $cnt < count($matches[1]); $cnt++) {
								//echo "Matched image raw: " . $matches[1][$cnt] . "\n";
								$between_slashes = explode( "/", $matches[1][$cnt]);
								$len = count($between_slashes) - 1;
								$image_name = $between_slashes[$len] . ".jpg";
								$image_hi_name = $between_slashes[$len] . "_HI.jpg";
								//echo "Image name: " . $image_name . "\n";
					
					
								//TODO: Send this image to the MedImage Server
								//send_image($image_name, $image_folder, $preview);
								//send_image($image_hi_name, $image_folder, $preview);
								
								$new_message = "Sending photo to the MedImage Server: 'image' [TESTING:" . $image_hi_name . "]";		//TODO: get the latest ID entered here
				      		$recipient_ip_colon_id =  "123.123.123.123:" . $sender_id;		//Send privately to the original sender
				      		$sender_name_str = "MedImage";
				      		$sender_email = "info@medimage.co.nz";
				      		$sender_ip = "111.111.111.111";
				      		$options = array('notification' => false, 'allow_plugins' => false);
				   			$api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
				   			
				   			
				   			 //Now start a parallel process, that waits until the photo has been sent, before sending a confirmation message.       
				   			$medimage_config = $this->get_medimage_config();
				   			
				   			//Get the layer name, if available. Used to ensure we have selected the correct database in our process child.
            				$layer_name = "";
								if(isset($_REQUEST['passcode'])) {
									$layer_name = $_REQUEST['passcode'];			
								}
		
								if(isset($_REQUEST['uniqueFeedbackId'])) {
									$layer_name = $_REQUEST['uniqueFeedbackId'];
								}
				   			
                        $command = $medimage_config['phpPath'] . " " . dirname(__FILE__) . "/upload.php " . $image_folder . " " .$image_hi_name . " " . $message_id . " " . $message_forum_id . " " . $layer_name;
                        global $staging;
                        if($staging == true) {
                            $command = $command . " staging";   //Ensure this works on a staging server  
                        }
                        error_log($command);
                        $api->parallel_system_call($command, "linux");
				   			
				   			
								
							}
						} else {
							//Sorry, no medimage server detected. Give the option via a return private message, and syntax for setting the MedImage Server, with 'pair aBc1' or 'pr aBc1'
							 $new_message = "You have uploaded a photo to the group, but you haven't paired with your MedImage desktop yet. Click one of the large pairing buttons on the desktop MedImage, and then type 'pr [your 4 digit code]' into this app, with the 4 digit code that MedImage gives you. http://medimage.co.nz";
				     		 $recipient_ip_colon_id = "123.123.123.123:" . $sender_id;		//Private to the sender of the original message
				      	 $sender_name_str = "MedImage";
				      	 $sender_email = "info@medimage.co.nz";
				      	 $sender_ip = "111.111.111.111";
				      	 $options = array('notification' => false, 'allow_plugins' => false);
				   	 	 $api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
						}
				}
 
 
            $actual_message = explode(": ", $message);			//Remove name of sender         
            if($actual_message[1]) {
            	$uc_message = strtoupper($actual_message[1]);
            	error_log($uc_message);
		         if(strpos($uc_message, "ID ") === 0) {
				      //Check for messages starting with 'id [patientid] [keywords]', which switch the id to send this to on the
				      //backend MedImage Server
				      $id = substr($actual_message[1], 3);
				      $id = str_replace("\\r","", $id);
				      $id = str_replace("\\n","", $id);
				      $id = preg_replace('/\s+/', ' ', trim($id));
				      
				      //TODO: Set a cookie with this current ID? (Or we can check via the database in future images sent).
				      
				      $new_message = "Switched MedImage patient to ID: '" . $id . "'";
				      $recipient_ip_colon_id = "";		//No recipient, so the whole group. 123.123.123.123:" . $recipient_id;
				      $sender_name_str = "MedImage";
				      $sender_email = "info@medimage.co.nz";
				      $sender_ip = "111.111.111.111";
				      $options = array('notification' => false, 'allow_plugins' => false);
				   	$api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
				   }
				   
				   
				   if((strpos($uc_message, "PAIR ") === 0)||
				   	(strpos($uc_message, "PR ") === 0)) {
				   	//A pairing request.
					   $new_message = "You have successfully paired with your MedImage Server! [TODO complete] To unpair, enter 'unpair'.";
				      $recipient_ip_colon_id = "123.123.123.123:" . $sender_id;		//Private to the sender of the original message
				      $sender_name_str = "MedImage";
				      $sender_email = "info@medimage.co.nz";
				      $sender_ip = "111.111.111.111";
				      $options = array('notification' => false, 'allow_plugins' => false);
				   	$api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);			
				   	setcookie("medimage-server", "https://medimage-nz1.atomjump.com/morehere");   		
				   		
				   }
				   
				    if(strpos($uc_message, "UNPAIR") === 0) {
				   	//An unpairing request.
					   $new_message = "You have successfully unpaired with your MedImage Server! [TODO complete]";
				      $recipient_ip_colon_id = "123.123.123.123:" . $sender_id;		//Private to the sender of the original message
				      $sender_name_str = "MedImage";
				      $sender_email = "info@medimage.co.nz";
				      $sender_ip = "111.111.111.111";
				      $options = array('notification' => false, 'allow_plugins' => false);
				   	$api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);					
				   	setcookie("medimage-server", "", time()-3600);	
				   }
				   
				}
            
            return true;
            
        }
		

        
        public function on_msg_buttons($message_id)
        {
        	global $root_server_url;
        	
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
        
           
            /*<script>
            	function medimageExport(msgId) {  
            		$.ajax({
							url: \"" . trim_trailing_slash_local($root_server_url) . "/plugins/medimage_export/export.php\", 
							data: data,
							type: 'POST',
							cache: false
							}).done(function(response) {
								alert(\"Response : \" + response);
        						
        					}
        			);
        		}
           	</script> */
            
           $ret_text = "<script>function medimageExport(msgId) { alert('Clicked'); data = { msg_id: " . $message_id . " }; jQuery.ajax({ url: \"" . $root_server_url . "/plugins/medimage_export/export.php\", data: data, type: 'POST', cache: false 		}).done(function(response) { alert(\"Response : \" + response);	}); }</script><a class=\"comment-msg-button\" href=\"javascript:\" onclick=\"alert('About to run MedImage Photo Export in here. Msg ID: " . $message_id . "'); medimageExport(" . $message_id . ");\"><img width=\"48\" src='" . $root_server_url . "/plugins/medimage_export/medimage_logo.png'></a>";
            return $ret_text;
            
        }
        
        public function on_upload_screen()
        {
        	global $root_server_url;
        	
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
           
            ?>
            	<br/>
            	<h4>Export Forum to MedImage (.pdf)</h4>
            
            <?php
           
            $ret_text = "<a class=\"comment-msg-button\" href=\"javascript:\" onclick=\"alert('TODO: MedImage Forum .pdf export in here. Forum ID: " . $_REQUEST['uniqueFeedbackId'] . "'); \"><img width=\"48\" src='" . $root_server_url . "/plugins/medimage_export/medimage_logo.png'></a>";
            
            
            echo $ret_text;
            
            return true;
            
        }
        
        
    }
?>
