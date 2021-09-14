<?php

	echo "Inside export. Layer:" . $_REQUEST['layer_name'];
	$resp = file_get_contents("https://staging.atomjump.com/api/download.php?format=json&uniqueFeedbackId=test_feedback");
	
	echo $resp;
	//Sample URL: https://staging.atomjump.com/api/download.php?format=json&uniqueFeedbackId=test_feedback

?>