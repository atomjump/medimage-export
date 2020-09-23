<?php

	$verbose = false;

  	function trim_trailing_slash_local($str) {
        return rtrim($str, "/");
    }
    
    function add_trailing_slash_local($str) {
        //Remove and then add
        return rtrim($str, "/") . '/';
    }
    
    
    function post_data($target, $local_file_path, $filename, $verbose) {
		$success = false;
		$error_message = "";
		
		//Copy a temporary cache into our current folder, and rename it.
		$temp_filename = add_trailing_slash_local(__DIR__) . "temp/" . $filename;
		if(copy($local_file_path, $temp_filename)) {
		
			# http://php.net/manual/en/curlfile.construct.php

			// Create a CURLFile object / procedural method
			$cfile = curl_file_create($temp_filename,'image/jpeg',$filename);		//Examples: 'resource/test.png','image/png','testpic'); // try adding

			// Create a CURLFile object / oop method
			#$cfile = new CURLFile('resource/test.png','image/png','testpic'); // uncomment and use if the upper procedural method is not working.

			// Assign POST data
			$imgdata = array('file1' => $cfile);	


			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $target);
			curl_setopt($curl, CURLOPT_USERAGENT,'Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15');
			curl_setopt($curl, CURLOPT_HTTPHEADER,array('User-Agent: Opera/9.80 (Windows NT 6.2; Win64; x64) Presto/2.12.388 Version/12.15','Referer: http://medimage.co.nz','Content-Type: multipart/form-data'));
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // stop verifying certificate
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl, CURLOPT_POST, true); // enable posting
			curl_setopt($curl, CURLOPT_POSTFIELDS, $imgdata); // post images
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // if any redirection after upload
			$response = curl_exec($curl);
			//echo $response;
		
			if(!curl_errno($curl))
			{
				$info = curl_getinfo($curl);
				if ($info['http_code'] == 200) {
				  // Files uploaded successfully.
				  $success = true;
				}
			}
			else
			{
			  // Error happened
			  $error_message = curl_error($curl);
			  if($verbose == true) error_log($error_message);
			  //print_r($error_message);
			}
			curl_close($curl);
			
			//Delete the temporary file
			unlink($temp_filename);
		}
    
    
 		return array($success, $error_message);
 	}
 	
 	#$debug_msg = "In here " . json_encode($argv);//add_trailing_slash_local(__DIR__) . "
 	#file_put_contents("/var/www/html/atomjump_staging/api/plugins/medimage_export/temp/debug.txt", $debug_msg);
        
	 if(!isset($medimage_config)) {
		  //Get global plugin config - but only once
		  $data = file_get_contents (dirname(__FILE__) . "/config/config.json");
		  if($data) {
			   $medimage_config = json_decode($data, true);
			   if(!isset($medimage_config)) {
				   $msg = "Error: MedImage config/config.json is not valid JSON.";
				   if($verbose == true) error_log($msg);
				   //echo $msg;
				   exit(0);
			   }
		  } else {
				$msg = "Error: MedImage config/config.json in medimage_export plugin.";
				if($verbose == true) error_log($msg);
			   //echo $msg;
			   exit(0);
		  }
	 }    


	/* Command line inputs:
		E.g. [
			"\/var\/www\/html\/atomjump_staging\/api\/plugins\/medimage_export\/upload.php",
			"\/var\/www\/html\/atomjump_staging\/api\/images\/im\/",
			"upl415-37673138_HI.jpg",
			"5299",
			"178",
			"test_feedback",
			"https:\/\/medimage-nz1.atomjump.com\/write\/QY5WZRemkuadCDjY83",
			"staging"
		]
	*/
	$run_process_offset = 0;			//Should be 1 during a live process, but 0 during testing on the command line.
	$layer_name_off = 5 + $run_process_offset;
	$staging_flag_off = 7 + $run_process_offset;
	$upload_to_off = 6 + $run_process_offset;
	$filename_off = 2 + $run_process_offset;
	$forum_id_off = 4 + $run_process_offset;

	

	$start_path = add_trailing_slash_local($medimage_config['serverPath']);
	$notify = false;
	if(isset($argv[$layer_name_off])) { 		//This is the layer name
		//Set the global layer val, so that this is the correct database to add this message on
		$_REQUEST['passcode'] = $argv[$layer_name_off];
	}
	
	if(isset($argv[$staging_flag_off])) {      //allow for a staging flag
	    $staging = true;
	}
	include_once($start_path . 'config/db_connect.php');	
	
    $define_classes_path = $start_path;     //This flag ensures we have access to the typical classes, before the cls.pluginapi.php is included
	require($start_path . "classes/cls.pluginapi.php");

    $api = new cls_plugin_api();

 	if($verbose == true) error_log(json_encode($argv, JSON_PRETTY_PRINT));
	if($verbose == true) error_log("Uploading to: " . $argv[$upload_to_off]);
    
    
    
    if(isset($argv[$upload_to_off])) {
    		$filename = "#image-" . date("d-m-Y-h-i-s") . ".jpg";		//TODO: get incoming name from db query
    		$upload_to = $argv[$upload_to_off];
    		//Split up the medimage-server value e.g. https://medimage-nz1.atomjump.com/write/uPSE4UWHmJ8XqFUqvf
    		if($verbose == true) error_log("MedImage Server on upload:" . $upload_to);
    		//echo "MedImage Server on upload:" . $upload_to . "\n";
    		$url = explode("/", $upload_to);
    		$domain = $url[0] . "/" . $url[1] . "/" . $url[2];
    		$folder = $url[4];
    		//echo "Domain: " . $domain . "  Folder: " . $folder . "\n";
    		$output_post_url = $domain . "/api/photo";
    		$output_file_name = "#" . $folder . "-" . $filename;
    		//echo "POST URL: " . $output_post_url . "  Filename: " . $output_file_name . "\n";
    		$local_file_path = $start_path . "images/im/" . $argv[$filename_off];		//the actual local filename 
    		//echo "Local file path:" . $local_file_path . "\n";
    		list($resp, $err) = post_data($output_post_url, $local_file_path,  $output_file_name, $verbose);
    		
    		
    		
    		if($resp == true) {
			 $new_message = "Successfully sent the photo to the MedImage Server: 'image' [TESTING:" . $argv[$filename_off] . "]";		//TODO: get the latest ID entered here
			} else {
			 $new_message = "Sorry there was a problem sending the photo to the MedImage Server: 'image' [TESTING:" . $argv[$filename_off] . " Error msg: " . $err . "]";		//TODO: get the latest ID entered here
					
			}
			
			if($verbose == true) error_log("About to post to the group with :" . $new_message);
    
			 $recipient_ip_colon_id = "";		//No recipient, so the whole group. 
			 $sender_name_str = "MedImage";
			 $sender_email = "info@medimage.co.nz";
			 $sender_ip = "111.111.111.111";
			 $options = array('allow_plugins' => false);
			 $message_forum_id = $argv[$forum_id_off];
			 if($verbose == true) error_log("About to post to the group:" . $message_forum_id);
			 $api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
	
    }
    
      

?>
