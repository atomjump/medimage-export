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
    	
    	$url_matching = "atomjump";		//Works with based jpgs on atomjump which include e.g. 'atomjump' in their strings.
		//if($cnf['uploads']['replaceHiResURLMatch']) $url_matching = $cnf['uploads']['replaceHiResURLMatch'];			
		//$preg_search = "/.*?" . $url_matching ."(.*?)\.jpg/i";
		$preg_search = "/^|\s(.*?)\.jpg/i";
		preg_match_all($preg_search, $line_text, $matches);
			
				
					
		if(count($matches[0]) > 0) {
			//Yes we have at least one image
			$raw_image_url = "";
			
			for($cnt = 0; $cnt < count($matches[1]); $cnt++) {
				if($verbose == true) echo "Matched image raw: " . $matches[1][$cnt] . "\n";
				$raw_image_url = $matches[1][$cnt];
				$between_slashes = explode( "/", $matches[1][$cnt]);
				$len = count($between_slashes) - 1;
				$image_name = $between_slashes[$len] . ".jpg";
				$image_hi_name = $between_slashes[$len] . "_HI.jpg";
				if($verbose == true) echo "Image name: " . $image_name . "\n";
				
				$abs_image_path = str_replace($web_api_url, $api_file_path, $raw_image_url);
				$abs_image_dir = dirname($abs_image_path);
				
				if(!file_exists($abs_image_dir . $image_name)) $image_name = false;		//Don't use this version if
				if(!file_exists($abs_image_dir . $image_hi_name)) $image_hi_name = false;										//it doesn't exist locally
																						
			}
			return array($raw_image_url, $image_name, $image_hi_name, $abs_image_dir);
		} else {
			return array(false, false, false, false);
		
		}
    
    
    }


   function parse_json_into_easytable($json) {
  	   $lines = json_decode($json);
 	  
 	  
 	  list($web_api_url, $api_file_path) = get_image_url_remote_local();	
 	  
 	   
       require('easyTable.php');
 	   $pdf = require('fpdf181/fpdf.php');
 	   require('exfpdf.php');
 	
 	
 	   $pdf=new exFPDF();
 	   $pdf->AddPage(); 
 	   $pdf->SetFont('helvetica','',10);
 	   
 	   $hi_res_image_countdown = 10;		//About 400KB*10 = 4MB
 	   $low_res_image_countdown = 20;		//About 100KB*20 = 2MB
 	   

 	
	   $table=new easyTable($pdf, '%{70, 30}', 'align:L;');
 
 	   $colours = array('#ddd', '#eee');
 	   
 	   for($cnt = 0; $cnt < count($lines->res); $cnt++) {
 	  
 	  	   $background_colour = $colours[$cnt%2];
 	  	   $line_text = $lines->res[$cnt]->text;
 	  
 		   list($image_url, $image_filename, $image_hi_filename, $abs_image_dir) = parse_for_image($lines->res[$cnt]->text, $web_api_url, $api_file_path);
 		   
 		   
 		   $urls = $image_url . "," . $image_filename . "," . $image_hi_filename . "," . $abs_image_dir;		//TEMP DEBUGGING
 		   
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
							$line_text = $line_text . " [Maximum images in this .pdf exceeded. Please manually export this photo]";
					
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
 		   
 		   
 
		   $table->easyCell($line_text, 'width:70%; align:L; bgcolor:' . $background_colour . '; valign:T;' . $image_str);
		   $table->easyCell($lines->res[$cnt]->timestamp . " " . $urls, 'width:30%; align:L; bgcolor:' . $background_colour . '; valign:T;');
		   $table->printRow();
 
 	   }

   	    $table->endTable();
 
 		return $pdf->Output('S');
   
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
	  
	  $from = 7047;		//TESTING
  
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
  
  	  ob_start();
	  $se->process(NULL, NULL, 200,  true, $from, $db_timezone, $format, $duration);		//50 should be 2000 or so. TESTING
 	  $json = ob_get_clean();
 
 	  //echo $json;
 	  //print_r($lines->res[0]);
 	
 	  
 	  $pdfString = parse_json_into_easytable($json);
 	  $pdfBase64 = base64_encode($pdfString);
	  echo 'data:application/pdf;base64,' . $pdfBase64;
 
	} else {
	 //wrong username
	  echo "{ 'Error' : 'Wrong credentials.' }";
	}




?>