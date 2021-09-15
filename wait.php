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
			"/usr/bin/php",
			"\/var\/www\/html\/atomjump_staging\/api\/plugins\/medimage_export\/wait.php",
			"\/var\/www\/html\/atomjump_staging\/api\/plugins\/medimage_export\/plugins\/temp\/mypdf.pdf",
			
		]
	*/
	$file_offset = 1;
	
	
	

	

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

 	$core_filename = $argv[$file_offset];
 	
 	//TODO: wait 10 minutes, and then delete the input file   
	echo "Will delete " . $basic_filename . " in 10 minutes.";

    sleep(15);		//TODO: change to 10 minute = 60secx10min  = 600

    //Delete the PDF file
    $core_filename = str_replace("..","", $core_filename);		//Prevent any cross path scripting
    
	unlink(add_trailing_slash_local(dirname(__FILE__)) . "temp/" . $core_filename);
   

?>
