<?php

	echo "Inside export. Layer:" . $_REQUEST['layer_name'];
	file_get_contents("https://staging.atomjump.com/api/download.php?format=json&uniqueFeedbackId=test_feedback");
	//Sample URL: https://staging.atomjump.com/api/download.php?format=json&uniqueFeedbackId=test_feedback

?>