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
		//See https://gist.github.com/maxivak/18fcac476a2f4ea02e5f80b303811d5f
		// data fields for POST request
		$fields = array("file1"=>$filename);		//"file1"

		// files to upload
		$filenames = array($local_file_path);

		$files = array();
		
		$files[$filename] = file_get_contents($filenames[0]);
		/*foreach ($filenames as $f){
		   $files[$f] = file_get_contents($f);
		   
		   echo "Filesize: " . filesize($f);
		}*/
		
		

		// curl

		$curl = curl_init();

		$url_data = http_build_query($data);

		$boundary = uniqid();
		$delimiter = '-------------' . $boundary;

		$post_data = build_data_files($boundary, $fields, $files);

		echo $post_data . "\n";
		
		//return "";		//TEMPIN TESTING
		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => $url,
		  CURLOPT_RETURNTRANSFER => 1,
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 30,
		  //CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => "POST",
		  CURLOPT_POST => 1,
		  CURLOPT_POSTFIELDS => $post_data,
		  CURLOPT_HTTPHEADER => array(
			//"Authorization: Bearer $TOKEN",
			"Content-Type: multipart/form-data; boundary=" . $delimiter,
			"Content-Length: " . strlen($post_data)

		  ),			//Peter comment: , ???

  
		));


		//
		$response = curl_exec($curl);

		$info = curl_getinfo($curl);
		//echo "code: ${info['http_code']}";

		//print_r($info['request_header']);

		var_dump($response);
		$err = curl_error($curl);
		
		
		echo "Any error:";
		var_dump($err);
		error_log($err);
		curl_close($curl);
		return $err;
	}




	function build_data_files($boundary, $fields, $files){
		$data = '';
		$eol = "\r\n";

		$delimiter = '-------------' . $boundary;

		foreach ($fields as $name => $content) {
			$data .= "--" . $delimiter . $eol
				. 'Content-Disposition: form-data; name="' . $name . "\"".$eol.$eol
				. $content . $eol;
		}


		foreach ($files as $name => $content) {
			$data .= "--" . $delimiter . $eol
				. 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
				//. 'Content-Type: image/png'.$eol
				. 'Content-Transfer-Encoding: binary'.$eol
				;

			$data .= $eol;
			$data .= $content . $eol;
		}
		$data .= "--" . $delimiter . "--".$eol;


		return $data;
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
    
			//TODO: After a successful receipt event
			 $new_message = "Successfully sent the photo to the MedImage Server: 'image' [TESTING:" . $argv[1] . "]";		//TODO: get the latest ID entered here
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
