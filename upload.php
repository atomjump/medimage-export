<?php

	$verbose = false;

  	function trim_trailing_slash_local($str) {
        return rtrim($str, "/");
    }
    
    function add_trailing_slash_local($str) {
        //Remove and then add
        return rtrim($str, "/") . '/';
    }
    
    
    function post_data($url, $local_file_path, $filename) {
		$success = false;
		
		// Puts files to be uploaded in this array
		$files = array(
		  $local_file_path
		);

		$postfields = array();

		foreach ($files as $index => $file) {
		  if (function_exists('curl_file_create')) { // For PHP 5.5+
			$file = curl_file_create($file);
		  } else {
			$file = '@' . realpath($file);
		  }
		  $postfields["file$index"] = $file;		//$postfields["file_$index"] = $file;
		}

		// Add other post data as well.
		$postfields['name'] = 'file1';		//Name?
		// Need to set this head for uploading file.
		$headers = array("Content-Type" => "multipart/form-data");

		print_r($postfields);		//TESTING

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		$response = curl_exec($ch);
		echo $response;
		
		if(!curl_errno($ch))
		{
			$info = curl_getinfo($ch);
			if ($info['http_code'] == 200) {
			  // Files uploaded successfully.
			  $success = true;
			}
		}
		else
		{
		  // Error happened
		  $error_message = curl_error($ch);
		  error_log($error_message);
		  print_r($error_message);
		}
		curl_close($ch);
    
    
 		return $success;
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
	$notify = false;
	if(isset($argv[4])) { 		//This is the layer name
		//Set the global layer val, so that this is the correct database to add this message on
		$_REQUEST['passcode'] = $argv[4];
	}
	
	if(isset($argv[6])) {      //allow for a staging flag
	    $staging = true;
	}
	include_once($start_path . 'config/db_connect.php');	
	
    $define_classes_path = $start_path;     //This flag ensures we have access to the typical classes, before the cls.pluginapi.php is included
	require($start_path . "classes/cls.pluginapi.php");

    $api = new cls_plugin_api();

 
    
    if(isset($argv[5])) {
    		$filename = "image.jpg";		//TODO: get incoming name
    		$upload_to = $argv[5];
    		//Split up the medimage-server value e.g. https://medimage-nz1.atomjump.com/write/uPSE4UWHmJ8XqFUqvf
    		error_log("MedImage Server on upload:" . $upload_to);
    		echo "MedImage Server on upload:" . $upload_to . "\n";
    		$url = explode("/", $upload_to);
    		$domain = $url[0] . "/" . $url[1] . "/" . $url[2];
    		$folder = $url[4];
    		echo "Domain: " . $domain . "  Folder: " . $folder . "\n";
    		$output_post_url = $domain . "/api/photo";
    		$output_file_name = "#" . $folder . "-" . $filename;
    		echo "POST URL: " . $output_post_url . "  Filename: " . $output_file_name . "\n";
    		$local_file_path = $start_path . "images/im/" . $argv[1];		//ARgv1 is the actual local filename 
    		echo "Local file path:" . $local_file_path . "\n";
    		$resp = post_data($output_post_url, $local_file_path,  $output_file_name);
    		
    		
    		if($verbose == true) error_log("About to post to the group with success transfer.");
    
    		if($success == true) {
			 $new_message = "Successfully sent the photo to the MedImage Server: 'image' [TESTING:" . $argv[1] . "]";		//TODO: get the latest ID entered here
			} else {
			 $new_message = "Sorry there was a problem sending the photo to the MedImage Server: 'image' [TESTING:" . $argv[1] . "]";		//TODO: get the latest ID entered here
				
			
			}
			 $recipient_ip_colon_id = "";		//No recipient, so the whole group. 
			 $sender_name_str = "MedImage";
			 $sender_email = "info@medimage.co.nz";
			 $sender_ip = "111.111.111.111";
			 $options = array('allow_plugins' => false);
			 $message_forum_id = $argv[3];
			 if($verbose == true) error_log("About to post to the group:" . $message_forum_id);
			 $api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
	
    }
    
    //sleep(2);		//TODO: actually upload the image to the MedImage Server, this delay is currently simulated
 
    

       

?>
