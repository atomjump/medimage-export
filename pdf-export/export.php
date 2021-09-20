<?php

	//Note: this is very similar to the code in the main loop-server project in download.php

	//Note, we will need read only capability later on
	$db_read_only = false;				//Ensure we can palm this off to RDS replicas - we are only reading here, never writing
										//except where we write because of the sessions vars.  Have added a test to reconnect with the
										//master in that case.

	
	function trim_trailing_slash_local($str) {
        return rtrim($str, "/");
    }
    
    function add_trailing_slash_local($str) {
        //Remove and then add
        return rtrim($str, "/") . '/';
    }

	if(!isset($medimage_config)) {
        //Get global plugin config - but only once
        $data = file_get_contents (dirname(__FILE__) . "/../config/config.json");
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

	$sh = new cls_ssshout();
	$lg = new cls_login();


	function get_image_url_remote_local()
	{
		global $cnf;
		
		$subdomain = check_subdomain();

		
		$web_api_url = add_trailing_slash(str_replace('[subdomain]', $subdomain . ".", $cnf['webRoot']));		//Remove any mention of subdomains
		
		$api_file_path = add_trailing_slash($cnf['fileRoot']);
		
		return array($web_api_url, $api_file_path);
	}
	
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
	
	


    function parse_for_image($line_text, $web_api_url, $api_file_path) {
    	global $cnf;
    	
    	//General URL gathering
 		$preg_search = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]*([^\" \n]*)?/";
		preg_match_all($preg_search, $line_text, $matches);
			
					
		if(count($matches[0]) > 0) {
			
			//Yes we have at least one url
			$raw_image_url = "";
			
			for($cnt = 0; $cnt < count($matches[0]); $cnt++) {
				$match = trim($matches[0][$cnt], "\"");
				$info = pathinfo($match);
				$ext = $info['extension'];
				if(($ext == 'jpg')||($ext == 'jpeg')||($ext == 'png')||($ext == 'gif')) {
					//Yes it's an image
				
					if($verbose == true) echo "Matched image raw: " . $match . "\n";
					$raw_image_url = $match;
					if(($ext == 'jpg')||($ext == 'jpeg')) {
						//A jpg should be a locally added one
						$image_name = $info['filename'] . ".jpg";
						$image_hi_name = $info['filename'] . "_HI.jpg";
					} else {
						//This could be a .png from e.g. an emoticon
						$image_name = $info['filename'] . "." . $ext;
						$image_hi_name = $info['filename'] . "." . $ext;
					}
					if($verbose == true) echo "Image name: " . $image_name . "\n";
				
					$abs_image_path = str_replace($web_api_url, $api_file_path, $raw_image_url);
					$abs_image_dir = add_trailing_slash(dirname($abs_image_path));
				
				
					if(!file_exists($abs_image_dir . $image_name)) $image_name = false;		//Don't use this version if
					if(!file_exists($abs_image_dir . $image_hi_name)) $image_hi_name = false;										//it doesn't exist locally
				}
																						
			}
			return array($raw_image_url, $image_name, $image_hi_name, $abs_image_dir);
		} else {
			return array(false, false, false, false);
		
		}
    
    
    }
    
    function jsonp_decode($jsonp, $assoc = false) { // PHP 5.3 adds depth as third parameter to json_decode
    	//With thanks to https://stackoverflow.com/questions/5081557/extract-jsonp-resultset-in-php
		if($jsonp[0] !== '[' && $jsonp[0] !== '{') { // we have JSONP
		   $jsonp = substr($jsonp, strpos($jsonp, '('));
		}
		return json_decode(trim($jsonp,'();'), $assoc);
	}
	
	function get_title($layer_info) {
		
		$title = "[Unknown Forum Title]";		
		if($layer_info['var_title']) {			
			$title = $layer_info['var_title'];		
		}
		
		return $title;
	}

	function security_code($security_gid) {
		if($security_gid == true) {
			//Need a unique security code on a publicly exported pdf
			$rand = rand(1,100000);
			return "-gid" . $rand;
		} else {
			return "";
		}
		
	}


   function parse_json_into_easytable($lines, $user_date_time, $forum_title, $max_records, $output_folder, $security_gid = true, $file_based = false) {
  	  
 	  
 	  list($web_api_url, $api_file_path) = get_image_url_remote_local();	
 	  
 	   
       require('easyTable.php');
 	   $pdf = require('fpdf181/fpdf.php');
 	   require('exfpdf.php');
 	
 	   if($max_records > count($lines->res)) {
 	   		$records = "  [All messages]";
 	   } else {
 	   		$records = "  [Most recent " . count($lines->res) ." messages]";
 	   }
 	
 	
 	   $pdf=new exFPDF();
 	   $pdf->AddPage(); 
 	   $pdf->SetFont('Arial','B',12);
	   $pdf->MultiCell(0,6,'AtomJump Forum Export "' . $forum_title . '"');
	   $pdf->SetFont('Arial','',8);
	   $pdf->MultiCell(0,8,$user_date_time . $records);
 	   $pdf->SetFont('Arial','',9);
 	   
 	   $hi_res_image_countdown = 10;		//About 400KB*10 = 4MB
 	   $low_res_image_countdown = 20;		//About 100KB*20 = 2MB
 	   

 	
	   $table=new easyTable($pdf, '%{70, 30}', 'align:L;');
 
 	   $colours = array('#ddd', '#eee');
 	   
 	   for($cnt = 0; $cnt < count($lines->res); $cnt++) {
 	  
 	  	   $background_colour = $colours[$cnt%2];
 	  	   if($lines->res[$cnt]->whisper == true) {
 	  	   		//A private message - show as light blue
 	  	   		$background_colour = '#eff8f8';
 	  	   }
 	  	   $line_text = strip_tags($lines->res[$cnt]->text);
 	  	   $line_text = str_replace("&nbsp;", " ", $line_text);
 	  	   $parsable_text = strip_tags($lines->res[$cnt]->text, "<img>");
 	  	   $parsable_text = str_replace("&nbsp;", " ", $parsable_text);
 	  
 		   list($image_url, $image_filename, $image_hi_filename, $abs_image_dir) = parse_for_image($parsable_text, $web_api_url, $api_file_path);
 		   
 		   
 		   if($image_url != false) {
 		   	  //So, it is at least an image from another website
 		   	  
 		   	  if($image_filename) {
 		   		  	//It is a local file
 		   		  
					if(($hi_res_image_countdown > 0) && ($image_hi_filename)) {
						//Use the hi-res version in the .pdf
						$image_str = " img:" . $abs_image_dir . $image_hi_filename . ",w50;";
						$line_text = str_replace($image_url, "",$line_text);		//Remove the textual version of image
						$hi_res_image_countdown --;
					} else {
						if(($low_res_image_countdown > 0) && ($image_filename)) {
							//Use the low-res version in the .pdf
							 $image_str = " img:" . $abs_image_dir . $image_filename . ",w50;";
							 $line_text = str_replace($image_url, "",$line_text);		//Remove the textual version of image
							 $low_res_image_countdown --;
						} else {
							//We've gone past the max number of images in this single .pdf file. Give the URL and add
							//a warning to manually export the photo.
							$image_str = "";  
							if($image_hi_filename) {
								$image_url = str_replace($image_filename, $image_hi_filename, $image_url);
							}
							$line_text = $line_text . " " . $image_url . " [Maximum images in this .pdf exceeded. Please manually export this photo]";
					
						}
 		   		
 		   		  	
					}
					
			 } else {
				//It is a remote image - just include the URL visually		
				$image_str = "";
			 }	
				
		   } else {
				//No images
      			$image_str = "";  
 		   	  
 		   }
 		   
  		   $ago =  $lines->res[$cnt]->ago;
 		   
 
		   $table->easyCell($line_text, 'width:70%; align:L; bgcolor:' . $background_colour . '; valign:T;' . $image_str);
		   $table->easyCell($ago, 'width:30%; align:L; bgcolor:' . $background_colour . '; valign:T;');
		   $table->printRow();
 
 	   }

   	    $table->endTable();
 
 
 		$dt_coms = explode(" ", $user_date_time);
 		$parsed_forum_title = preg_replace("/[^a-zA-Z]/", "", $forum_title);	//only allow alphnumeric chars in filename
 		$filename = $parsed_forum_title . " " . $dt_coms[0] . " " . $dt_coms[1] . " " . $dt_coms[2] . " ". $dt_coms[3] . " ". $dt_coms[4] . ".pdf";		//Leave off GMT etc.
 		$filename = str_replace(" ", "-", $filename);
 		$filename = str_replace(":", "-", $filename);
 		$filename = str_replace("[", "", $filename);
 		$filename = str_replace("]", "", $filename);
 		$filename = trim($filename);
 		if($file_based == true) {
 			$pdf->Output('F', $output_folder . $filename);
 		} else {
 			$pdf->Output('I', $filename);
 		}
   		return $filename;
   }


	function medimage_intro_message($api, $message_id, $web_path, $pdf_file_name, $message_forum_id, $layer_name, $sender_id, $medimage_config)
	{
		//Send an intro message
		$new_message = "You need to enable MedImage exports first, please enter 'start medimage'. Once paired, this will export this forum's contents as a PDF, or a single image, directly onto your desktop system. You can find more information at http://medimage.co.nz";
		
		$recipient_ip_colon_id =  "123.123.123.123:" . $sender_id;		//Send privately to the original sender
		$sender_name_str = "MedImage";
		$sender_email = "info@medimage.co.nz";
		$sender_ip = "111.111.111.111";
		$options = array('notification' => false, 'allow_plugins' => false);
		
		if($verbose == true) {
			echo "sender_name_str:" . $sender_name_str . "  new_message:" . $new_message . "  recipient_ip_colon_id:" . $recipient_ip_colon_id . "  sender_email:" .  $sender_email . "  sender_ip:" .  $sender_ip . "  message_forum_id:" .  $message_forum_id ."\n";
		}
		
		$new_message_id = $api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
		
	}


	function send_pdf_to_medimage($api, $message_id, $pdf_file_name, $image_folder, $message_forum_id, $layer_name, $sender_id, $medimage_config)
	{
		$verbose = false;   //usually false, unless you want to debug
		
		
		
		//Send a message to the forum
		$id_text = get_current_id($api, $message_forum_id);
		if(!$id_text) {
			$id_text = "pdf";
			$append_message = " Note: you can name your export folder by entering e.g. 'id nhi1234'";
		} else {
			$append_message = "";
		}
		
		$tags = str_replace(" ", "-", $id_text);
							
		
		$new_message = "Sending the whole forum PDF file to the MedImage Server: '" . $id_text . "'" . $append_message;		
		$recipient_ip_colon_id =  "123.123.123.123:" . $sender_id;		//Send privately to the original sender
		$sender_name_str = "MedImage";
		$sender_email = "info@medimage.co.nz";
		$sender_ip = "111.111.111.111";
		$options = array('notification' => false, 'allow_plugins' => false);
		
		if($verbose == true) {
			echo "sender_name_str:" . $sender_name_str . "  new_message:" . $new_message . "  recipient_ip_colon_id:" . $recipient_ip_colon_id . "  sender_email:" .  $sender_email . "  sender_ip:" .  $sender_ip . "  message_forum_id:" .  $message_forum_id ."\n";
		}
		
		$new_message_id = $api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
		
		if($verbose == true) echo "New message id:" . $new_message_id . "\n";
		//Now start a parallel process, that waits until the photo has been sent, before sending a confirmation message.       
		
	
						
		//Get the layer name, if available. Used to ensure we have selected the correct database in our process child.
		if(!$_COOKIE['medimage-server']) {
			$medimage_server = "null";
		} else {
			$medimage_server = $_COOKIE['medimage-server'];
		}
		
		$command = $medimage_config['phpPath'] . " " . dirname(__FILE__) . "/../upload.php " . $image_folder . " " . $pdf_file_name . " " . $message_id . " " . $message_forum_id . " " . $layer_name . " " . $medimage_server . " " . $tags . " " . $sender_id;
		global $staging;
		if($staging == true) {
			$command = $command . " staging";   //Ensure this works on a staging server  
		}
		if($verbose == true) error_log("Running: " . $command);
		
		$api->parallel_system_call($command, "linux");
		$api->complete_parallel_calls();										
	
	}


	
	function wait_and_remove_pdf($api, $pdf_file, $medimage_config) {
	
		$verbose = false;
			
		$command = $medimage_config['phpPath'] . " " . dirname(__FILE__) . "/../wait.php " . $pdf_file;
		global $staging;
		if($staging == true) {
			$command = $command . " staging";   //Ensure this works on a staging server  
		}
		if($verbose == true) error_log("Running: " . $command);
		
		$api->parallel_system_call($command, "linux");
		$api->complete_parallel_calls();	
	
	}





	$logged = false;
	if(($_SESSION['logged-user'] != '')&&(isset($_SESSION['logged-user']))) {
		//Already logged in, but check if we know the ip address
	 $logged = true;				
 
	 //Get the current layer - use to view 
		$layer_visible = $_REQUEST['uniqueFeedbackId'];
	
		$ly = new cls_layer();
		$layer_info = $ly->get_layer_id($layer_visible, null);
		if($layer_info) {
			$_SESSION['authenticated-layer'] = $layer_info['int_layer_id'];
		}
			
	} else {

	  if($sh->check_email_exists($_REQUEST['email'])) {
		if($lg->confirm($_REQUEST['email'], $_REQUEST['pass'], null, null, $_REQUEST['uniqueFeedbackId']) == 'LOGGED_IN')
		   {
			 $logged = true;
		   }
		 }
	}



	if($logged == true) {


	  //Get the URLs
 	  list($web_api_url, $api_file_path) = get_image_url_remote_local();
 	  $web_path = $web_api_url . "plugins/medimage_export/temp/";


	  if(isset($_REQUEST['send_medimage'])) {
	  	 //Trying to send via MedImage
		  if(isset($_COOKIE['medimage-switched-on'])) {
	  
				if($_COOKIE['medimage-switched-on'] == "true") {
					//All good. Carry on below.
				} else {
					//Not switched on
					//$api, $message_id, $web_path, $pdf_file_name, $message_forum_id, $layer_name, $sender_id, $medimage_config
					medimage_intro_message($api, 0, $web_path, $pdf_file_name, $layer_info['int_layer_id'], $layer_visible, $_REQUEST['sender_id'], $medimage_config);
					exit(0);
				}    	
		  } else {
				//Not even registered yet.
				medimage_intro_message($api, 0, $web_path, $pdf_file_name, $layer_info['int_layer_id'], $layer_visible, $_REQUEST['sender_id'], $medimage_config);
				exit(0);
			
		  }
	  }


	  $se = new cls_search();
 
	  if($_REQUEST['from_id']) {
		 $from = $_REQUEST['from_id'];
	  } else {
		$from = 0;
	  }
	  
	  if($_REQUEST['format']) {
		 $format = $_REQUEST['format'];
	  } else {
		$format = "json";
	  }
  
	  if($_REQUEST['duration']) {
		$duration = $_REQUEST['duration'];
	  } else {
		$duration = 900;
	  }
	  
	  $max_records = 2000;
  
  	  ob_start();
	  $se->process(NULL, NULL, $max_records,  false, $from, $db_timezone, $format, $duration);		
 	  $jsonp = ob_get_clean();
 
 	  $json = jsonp_decode($jsonp);
 	  
 	  $forum_title = get_title($layer_info);		
 	  
 	  
 	  $output_folder = add_trailing_slash(dirname(__FILE__)) . "../temp/";
 	  
 	  if(isset($_REQUEST['send_medimage'])) {
 	  	$security_gid = false;		//If sending directly to MedImage, this file will never be made
 	  								//publicly accessible, so no Globally unique id needed
 	  	$file_based = true;			//Yes create a local pdf file
 	  } else {
 	  	$security_gid = true;		//A publicly available (albiet time limited) pdf
 	  								//will be created - append a GID, so that it cannot
 	  								//be guessed.
 	  	$file_based = false;		//Pipe directly into the browser
 	  }
 	  
 	  $pdf_file_name = parse_json_into_easytable($json, $_REQUEST['userDateTime'], $forum_title, $max_records, $output_folder, $security_gid, $file_based);
 	  
 	  
 	  
 	  
 	  
 	  if(isset($_REQUEST['send_medimage'])) {
		  //($api, $message_id, $pdf_file_name, $image_folder, $message_forum_id, $layer_name, $sender_id, $medimage_config)
		  send_pdf_to_medimage($api, 0, $pdf_file_name, $output_folder, $layer_info['int_layer_id'], $layer_visible, $_REQUEST['sender_id'], $medimage_config);
 	   }
 	   
 	  
 	   
 	    
	} else {
	 //wrong username
	  echo "{ 'Error' : 'Wrong credentials.' }";
	}




?>
