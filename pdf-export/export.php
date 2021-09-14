<?php

	//Note: this is very similar to the code in the main loop-server project in download.php

	//Note, we will need read only capability later on
	$db_read_only = false;				//Ensure we can palm this off to RDS replicas - we are only reading here, never writing
										//except where we write because of the sessions vars.  Have added a test to reconnect with the
										//master in that case.
	require('../../../config/db_connect.php');

	require("../../../classes/cls.basic_geosearch.php");
	require("../../../classes/cls.layer.php");
	require("../../../classes/cls.ssshout.php");


	$sh = new cls_ssshout();
	$lg = new cls_login();


	function get_image_url_remote_local()
	{
		global $cnf;
		
		$subdomain = check_subdomain();

		if((isset($cnf['readURLAllowReplacement'])) && ($cnf['readURLAllowReplacement'] == true)) {
			if((isset($cnf['readURLIncludeDot'])) && ($cnf['readURLIncludeDot'] == true)) {
				$web_api_url = add_trailing_slash(str_replace('[subdomain]', $subdomain . ".", $cnf['webRoot']));
			} else {
				$web_api_url = add_trailing_slash(str_replace('[subdomain]', $subdomain , $cnf['webRoot']));
			}
		} else {
			$web_api_url = add_trailing_slash(str_replace('[subdomain]', "", $cnf['webRoot']));		//Remove any mention of subdomains
		}
		
		$api_file_path = add_trailing_slash($cnf['fileRoot']);
		
		return array($web_api_url, $api_file_path);
	}	
	


    function parse_for_image($line_text, $web_api_url, $api_file_path) {
    	global $cnf;
    	
    	//General URL gathering
 		$preg_search = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
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
					$image_name = $info['filename'] . ".jpg";
					$image_hi_name = $info['filename'] . "_HI.jpg";
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


   function parse_json_into_easytable($lines, $user_date_time, $forum_title, $max_records) {
  	  
 	  
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
						$image_str = " img:" . $abs_image_dir . $image_hi_filename . ";";
						$line_text = str_replace($image_url, "",$line_text);		//Remove the textual version of image
						$hi_res_image_countdown --;
					} else {
						if(($low_res_image_countdown > 0) && ($image_filename)) {
							//Use the low-res version in the .pdf
							 $image_str = " img:" . $abs_image_dir . $image_filename . ";";
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
 		$filename = $forum_title . " " . $dt_coms[0] . " " . $dt_coms[1] . " " . $dt_coms[2] . " ". $dt_coms[3] . " ". $dt_coms[4];		//Leave off GMT etc.
 		$filename = str_replace(" ", "-", $filename);
 		$filename = str_replace("[", "", $filename);
 		$filename = str_replace("]", "", $filename);
 		$pdf->Output('F', "../temp/" . $filename);
   		return;
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
 	  
 	  
 	  $pdfString = parse_json_into_easytable($json, $_REQUEST['userDateTime'], $forum_title, $max_records);
 	  //$pdfBase64 = base64_encode($pdfString);
	  //echo 'data:application/pdf;base64,' . $pdfBase64;
 	  
 
	} else {
	 //wrong username
	  echo "{ 'Error' : 'Wrong credentials.' }";
	}




?>