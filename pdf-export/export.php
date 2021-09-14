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


    function parse_for_image($line_text) {
    	global $cnf;
    	
    	$url_matching = "atomjump";		//Works with based jpgs on atomjump which include e.g. 'atomjump' in their strings.
		if($cnf['uploads']['replaceHiResURLMatch']) $url_matching = $cnf['uploads']['replaceHiResURLMatch'];			
		$preg_search = "/.*?" . $url_matching ."(.*?)\.jpg/i";
		preg_match_all($preg_search, $line_text, $matches);
			
				
					
		if(count($matches[0]) > 0) {
			//Yes we have at least one image
			for($cnt = 0; $cnt < count($matches[1]); $cnt++) {
				if($verbose == true) echo "Matched image raw: " . $matches[1][$cnt] . "\n";
				$between_slashes = explode( "/", $matches[1][$cnt]);
				$len = count($between_slashes) - 1;
				$image_name = $between_slashes[$len] . ".jpg";
				$image_hi_name = $between_slashes[$len] . "_HI.jpg";
				if($verbose == true) echo "Image name: " . $image_name . "\n";
			}
			return $image_hi_name;
		} else {
			return false;
		
		}
    
    
    }


   function parse_json_into_easytable($json) {
  	   $lines = json_decode($json);
 	  
 	  //echo "Text: " . $lines->res[0]->text;
 	  //echo "Time: " . $lines->res[0]->timestamp;   
   
       require('easyTable.php');
 	   $pdf = require('fpdf181/fpdf.php');
 	   require('exfpdf.php');
 	
 	
 	   $pdf=new exFPDF();
 	   $pdf->AddPage(); 
 	   $pdf->SetFont('helvetica','',10);

 	
	   $table=new easyTable($pdf, '%{70, 30}', 'align:L;');
 
 	   $colours = array('#ddd', '#eee');
 	   
 	   for($cnt = 0; $cnt < count($lines->res); $cnt++) {
 	  
 	  	   $background_colour = $colours[$cnt%2];
 	  
 		   $image =	parse_for_image($lines->res[$cnt]->text);
 		   if($image != false) {
 		   	  $image_str = " img:../../../images/im/upl682-39859669.jpg;";  //https://staging.atomjump.com/api/images/im/upl682-39859669.jpg
 		   } else {
 		   	  $image_str = "";
 		   }
 
		   $table->easyCell($lines->res[$cnt]->text, 'width:70%; align:L; bgcolor:' . $background_colour . '; valign:T;' . $image_str); //,w700,h1280 
		   $table->easyCell($lines->res[$cnt]->timestamp . " " . $image_str, 'width:30%; align:L; bgcolor:' . $background_colour . '; valign:T;');
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
	  $se->process(NULL, NULL, 50,  true, $from, $db_timezone, $format, $duration);		//50 should be 2000 or so. TESTING
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