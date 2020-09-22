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



	function send_image()
	{
	
	
	}
   
	function parse_for_image($api, $message_id)
	{
		global $cnf;
		
		
		$image_folder = add_trailing_slash_local($cnf['fileRoot']) . "images/im/";
	
		$sql = "SELECT int_ssshout_id, var_shouted FROM tbl_ssshout WHERE int_ssshout_id = " . $message_id;
		//echo $sql . "\n";
		$result_msgs = $api->db_select($sql);
		while($row_msg = $api->db_fetch_array($result_msgs))
		{
			echo "Message: " . $row_msg['var_shouted'] . "    ID:" . $row_msg['int_ssshout_id'] . "\n";
			
			global $cnf;


			$preg_search = "/.*?" . $url_matching ."(.*?)\.jpg/i";
			preg_match_all($preg_search, $row_msg['var_shouted'], $matches);
			
				
					
			if(count($matches) > 1) {
				//Yes we have at least one image
				for($cnt = 0; $cnt < count($matches[1]); $cnt++) {
					echo "Matched image raw: " . $matches[1][$cnt] . "\n";
					$between_slashes = explode( "/", $matches[1][$cnt]);
					$len = count($between_slashes) - 1;
					$image_name = $between_slashes[$len] . ".jpg";
					$image_hi_name = $between_slashes[$len] . "_HI.jpg";
					echo "Image name: " . $image_name . "\n";
	
	
					//Send this image
					$this->send_image($image_name, $image_folder);
					$this->send_image($image_hi_name, $image_folder);
				}
			}
		}

	}
	
	//parse_for_image($message_id)
	parse_for_image($api, $_REQUEST['msg_id']);
	echo "Got to the end: " . $_REQUEST['msg_id'];


?>