<?php

	//This script should not have any output, or expect any output in the final version.
	//It is run via index.php > run-process.php
	/* Command line inputs:   
		E.g. [
			"\/var\/www\/html\/atomjump_staging\/api\/plugins\/medimage_export\/upload.php",
			"\/var\/www\/html\/atomjump_staging\/api\/images\/im\/",
			"upl415-37673138_HI.jpg",
			"5299",
			"178",
			"test_feedback",
			"https:\/\/medimage-nz1.atomjump.com\/write\/QY5WZRemkuadCDjY83",
			"nhi1234-arm",
			"staging"
		]
		
		/usr/bin/php /var/www/html/atomjump_staging/api/plugins/medimage_export/upload.php /var/www/html/atomjump_staging/api/images/im/ upl415-37673138_HI.jpg 5299 178 test_feedback https://medimage-nz1.atomjump.com/write/QY5WZRemkuadCDjY83 nhi1234-arm staging
	*/
	
	
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
		$filename = str_replace("..","", $filename);		//Prevent any cross path scripting
		$temp_filename = add_trailing_slash_local(__DIR__) . "temp/" . $filename;
		//WARNING: the temporary folder must have 777 permissions.
		
  
		
		
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
		} else {
			$error_message = "Temporary file not copied successfully";
			
		}    
    
 		return array($success, $error_message);
 	}
 	
        
	 if(!isset($medimage_config)) {
		  //Get global plugin config - but only once
		  $data = file_get_contents (dirname(__FILE__) . "/config/config.json");
		  if($data) {
			   $medimage_config = json_decode($data, true);
			   if(!isset($medimage_config)) {
				   $msg = "Error: MedImage config/config.json is not valid JSON.";
				   if($verbose == true) error_log($msg);
				   exit(0);
			   }
		  } else {
				$msg = "Error: MedImage config/config.json in medimage_export plugin.";
				if($verbose == true) error_log($msg);
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
	$folder_off = 1;
	$filename_off = 2;
	$message_id_off = $filename_off + 1;
	$forum_id_off = $message_id_off + 1;
	$layer_name_off = $forum_id_off + 1;
	$upload_to_off = $layer_name_off + 1;
	$tags_off = $upload_to_off + 1;
	$sender_id_off = $tags_off + 1;
	$staging_flag_off = $sender_id_off + 1;
	
	
	

	

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
    		if($argv[$message_id_off] == 0) {
    			//This is a .pdf upload
    			
    			$basic_filename = $argv[$filename_off];		//E.g. Unknown-Forum-Title-Wed-Sep-15-2021-10-56-18.pdf
    			$upload_to = $argv[$upload_to_off];
    			
    			if($upload_to == "null") {
					//There is no MedImage Server paired
					$new_message =  "You have not yet paired with MedImage on your desktop. Click one of the large pairing buttons on the MedImage desktop, and then type 'pair [your 4 digit code]' into this app, with the 4 digit code that MedImage gives you. http://medimage.co.nz/how-to/#pair";
				} else {
    			
					$tags = $argv[$tags_off];
					$tags_visible = $tags . " " . $basic_filename;
					$tags_visible = str_replace("-", " ", $tags);
				
					$filename = "#" . $tags . "-" . $basic_filename;
					//Split up the medimage-server value e.g. https://medimage-nz1.atomjump.com/write/uPSE4UWHmJ8XqFUqvf
					if($verbose == true) error_log("MedImage Server on upload:" . $upload_to);
					//echo "MedImage Server on upload:" . $upload_to . "\n";
					$url = explode("/", $upload_to);
					$domain = $url[0] . "/" . $url[1] . "/" . $url[2];
					$folder = $url[4];
					$output_post_url = $domain . "/api/photo";		//MedImage constant
					$output_file_name = "#" . $folder . "-" . $filename;
						//E.g.  /var/www/html/atomjump_staging/api/plugins/medimage_export/pdf-export/../temp/
					$local_file_path = $argv[$folder_off] . $argv[$filename_off];		//the actual local filename 
				
				
					list($resp, $err) = post_data($output_post_url, $local_file_path,  $output_file_name, $verbose);
			
				
			
					if($resp == true) {
					 $new_message = "Successfully sent the whole forum PDF file to the MedImage Server: '" . $tags_visible . "'";		
			 
					 //TODO: See check if a file exists section of http://medimage.co.nz/building-an-alternative-client-to-medimage/
					 //We should keep pinging the server until the photo disappears here, ideally, in order to show a full run through.
			 
					} else {
					 $new_message = "Sorry there was a problem sending the PDF to the MedImage Server: '" . $tags_visible . "'.  Error msg: " . $err;		
					
					}
				}
				
				//Send a private message
				$sender_id = $argv[$sender_id_off];
				
				//Delete the PDF file
				$basic_filename = str_replace("..","", $basic_filename);		//Prevent any cross path scripting
				unlink(add_trailing_slash_local(dirname(__FILE__)) . "temp/" . $basic_filename);
			
				if($verbose == true) error_log("About to post to the group with :" . $new_message);
				 $recipient_ip_colon_id = "123.123.123.123:" . $sender_id; //A private message just to ourself 
				 $sender_name_str = "MedImage";
				 $sender_email = "info@medimage.co.nz";
				 $sender_ip = "111.111.111.111";
				 $options = array('allow_plugins' => false);
				 $message_forum_id = $argv[$forum_id_off];
				 if($verbose == true) error_log("About to post to the group:" . $message_forum_id);
				 $api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
						
				
    		} else {
    			//This is an image upload
				$tags = $argv[$tags_off];
				$tags_visible = str_replace("-", " ", $tags);
				$filename = "#" . $tags . "-" . date("d-m-Y-h-i-s") . ".jpg";
				$upload_to = $argv[$upload_to_off];
				if($upload_to == "null") {
					//There is no MedImage Server paired
					$new_message =  "You have not yet paired with MedImage on your desktop. Click one of the large pairing buttons on the MedImage desktop, and then type 'pair [your 4 digit code]' into this app, with the 4 digit code that MedImage gives you. http://medimage.co.nz/how-to/#pair";
				} else {
					//Split up the medimage-server value e.g. https://medimage-nz1.atomjump.com/write/uPSE4UWHmJ8XqFUqvf
					if($verbose == true) error_log("MedImage Server on upload:" . $upload_to);
					//echo "MedImage Server on upload:" . $upload_to . "\n";
					$url = explode("/", $upload_to);
					$domain = $url[0] . "/" . $url[1] . "/" . $url[2];
					$folder = $url[4];
					$output_post_url = $domain . "/api/photo";
					$output_file_name = "#" . $folder . "-" . $filename;
					$local_file_path = $start_path . "images/im/" . $argv[$filename_off];		//the actual local filename 
		
			
					list($resp, $err) = post_data($output_post_url, $local_file_path,  $output_file_name, $verbose);
			
					if($resp == true) {
					 $new_message = "Successfully sent the photo to the MedImage Server: '" . $tags_visible . "'";		
			 
					 //TODO: See check if a file exists section of http://medimage.co.nz/building-an-alternative-client-to-medimage/
					 //We should keep pinging the server until the photo disappears here, ideally, in order to show a full run through.
			 
					} else {
					 $new_message = "Sorry there was a problem sending the photo to the MedImage Server: '" . $tags_visible . "'.  Error msg: " . $err;		
					
					}
				
				}
			
				if($verbose == true) error_log("About to post to the group with :" . $new_message);
	
				 $recipient_ip_colon_id = "123.123.123.123:" . $sender_id; //A private message just to ourself 
				 $sender_name_str = "MedImage";
				 $sender_email = "info@medimage.co.nz";
				 $sender_ip = "111.111.111.111";
				 $options = array('allow_plugins' => false);
				 $message_forum_id = $argv[$forum_id_off];
				 if($verbose == true) error_log("About to post to the group:" . $message_forum_id);
				 $api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
			}
	
    }
    
      

?>
