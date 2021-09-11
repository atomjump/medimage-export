<?php


    function trim_trailing_slash_local($str) {
        return rtrim($str, "/");
    }
    
    function add_trailing_slash_local($str) {
        //Remove and then add
        return rtrim($str, "/") . '/';
    }

	if(!isset($medimage_config)) {
        //Get global plugin config - but only once
        $data = file_get_contents (dirname(__FILE__) . "/config/config.json");
        if($data) {
            $medimage_config = json_decode($data, true);
            if(!isset($medimage_config)) {
                echo "Error: MedImage config/config.json is not valid JSON.";
                exit(0);
            }
        } else {
            echo "Error: MedImage config/config.json in medimage_export plugin.";
            exit(0);
        }
    }

	$start_path = add_trailing_slash_local($medimage_config['serverPath']);
	$notify = true;		//this switches on notifications from this message
	$staging = $medimage_config['staging'];
	if($staging == 1) {
		$staging = true;
	}
	include_once($start_path . 'config/db_connect.php');	
	
    $define_classes_path = $start_path;     //This flag ensures we have access to the typical classes, before the cls.pluginapi.php is included
	require($start_path . "classes/cls.pluginapi.php");

    $api = new cls_plugin_api();

	global $root_server_url;
	global $local_server_path;



	function get_current_id($api, $message_forum_id)
	{
		//This query will find the latest on this forum. May need some refinements e.g. around user id
		//With var_shouted example value:
		//MedImage: Switched MedImage patient to ID: 'nhi123 arm'
		
		$sql = "SELECT * from tbl_ssshout where int_layer_id = " . $message_forum_id . " AND var_shouted like 'MedImage: Switched%' order by int_ssshout_id desc limit 1";
		$result = $api->db_select($sql);
		
					
		$row = $api->db_fetch_array($result);
		if($row) {
			$message = $row['var_shouted'];
			$between = explode("'", $message);
			if($between[1]) {
				return $between[1];
			
			}
		}
		
		return false;
	
	}


	function send_image($api, $message_id, $image_hi_name, $image_folder, $message_forum_id, $layer_name, $medimage_config)
	{
		$verbose = true;   //usually false, unless you want to debug
		
		$image_folder = add_trailing_slash_local($medimage_config['serverPath']) . "images/im/";
		
		
		//Send a message to the forum
		$id_text = get_current_id($api, $message_forum_id);
		if(!$id_text) {
			$id_text = "image";
			$append_message = " Note: you can name your photo by entering e.g. 'id nhi1234 arm'";
		} else {
			$append_message = "";
		}
		
		$tags = str_replace(" ", "-", $id_text);
							
		
		$new_message = "Sending photo to the MedImage Server: '" . $id_text . "'" . $append_message;		
		$recipient_ip_colon_id =  "123.123.123.123:" . $sender_id;		//Send privately to the original sender
		$sender_name_str = "MedImage";
		$sender_email = "info@medimage.co.nz";
		$sender_ip = "111.111.111.111";
		$options = array('notification' => false, 'allow_plugins' => false);
		$api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
		
		
		//Now start a parallel process, that waits until the photo has been sent, before sending a confirmation message.       
		
	
						
		//Get the layer name, if available. Used to ensure we have selected the correct database in our process child.
		$command = $medimage_config['phpPath'] . " " . dirname(__FILE__) . "/upload.php " . $image_folder . " " . $image_hi_name . " " . $message_id . " " . $message_forum_id . " " . $layer_name . " " . $_COOKIE['medimage-server'] . " " . $tags;
		global $staging;
		if($staging == true) {
			$command = $command . " staging";   //Ensure this works on a staging server  
		}
		if($verbose == true) error_log("Running: " . $command);
		
		$api->parallel_system_call($command, "linux");
		echo "Running: " . $command . "\n";		//TESTING					
												
		///usr/bin/php /var/www/html/atomjump_staging/api/plugins/medimage_export/upload.php /var/www/html/atomjump_staging/api/images/im/ upl682-37825023_HI.jpg 7061 178
	
	}
   
	function parse_for_image($api, $message_id, $layer_name, $medimage_config)
	{
		global $cnf;
		
		
		$image_folder = add_trailing_slash_local($cnf['fileRoot']) . "images/im/";
	
		$sql = "SELECT int_ssshout_id, var_shouted, int_layer_id FROM tbl_ssshout WHERE int_ssshout_id = " . $message_id;
		//echo $sql . "\n";
		$result_msgs = $api->db_select($sql);
		while($row_msg = $api->db_fetch_array($result_msgs))
		{
			echo "Message: " . $row_msg['var_shouted'] . "    ID:" . $row_msg['int_ssshout_id'] . "\n";
			
			global $cnf;

			$url_matching = "atomjump";		//Works with based jpgs on atomjump which include e.g. 'atomjump' in their strings.
			if($cnf['uploads']['replaceHiResURLMatch']) $url_matching = $cnf['uploads']['replaceHiResURLMatch'];			
 			$preg_search = "/.*?" . $url_matching ."(.*?)\.jpg/i";
			preg_match_all($preg_search, $row_msg['var_shouted'], $matches);
			
				
					
			if(count($matches[0]) > 0) {
				//Yes we have at least one image
				for($cnt = 0; $cnt < count($matches[1]); $cnt++) {
					echo "Matched image raw: " . $matches[1][$cnt] . "\n";
					$between_slashes = explode( "/", $matches[1][$cnt]);
					$len = count($between_slashes) - 1;
					$image_name = $between_slashes[$len] . ".jpg";
					$image_hi_name = $between_slashes[$len] . "_HI.jpg";
					echo "Image name: " . $image_name . "\n";
	
					$message_forum_id = $row_msg['int_layer_id'];
					$message_id = $row_msg['int_ssshout_id'];
	
					//Send this image - TODO check the hi version exists and send that, but otherwise, send the smaller version.
					
					//TEMPOUTsend_image($api, $message_id, $image_name, $image_folder, $message_forum_id, $layer_name, $medimage_config);
					send_image($api, $message_id, $image_hi_name, $image_folder, $message_forum_id, $layer_name, $medimage_config);
				}
			}
		}

	}
	
	//parse_for_image($message_id)
	parse_for_image($api, $_REQUEST['msg_id'], $_REQUEST['layer_name'], $medimage_config);
	echo "Got to the end: " . $_REQUEST['msg_id'] . "  Layer:" . $_REQUEST['layer_name'];


?>
