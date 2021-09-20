<?php

	//You can add more sample strings in here and check the outputs.

	$test_msg = array(" This should not match .jpg",
					"Hello http",
					"https://test.com/image.jpg", 
					"https://test.com:5567/image.jpg",
					"Hello: http://image.com/image.jpg hi there",
					"some random text src=\"http://image.com/image.jpg\" hi there",
					"Peter: <img src=\"http://127.0.0.1:5100/vendor/atomjump/loop-server/images/im/upl2-97678490.jpg\" class=\"img-responsive\" width=\"80%\" border=\"0\">");

	$preg_search = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]*([^\" \n]*)?/";
	
	
	
	for($test = 0; $test < count($test_msg); $test++) {
		echo "Checking: " . $test_msg[$test] . "\n";
		preg_match_all($preg_search, $test_msg[$test], $matches);
			
		print_r($matches);		
		
		$image = false;	
					
		if(count($matches[0]) > 0) {
			//Yes we have at least one image
			$raw_image_url = "";
			
			for($cnt = 0; $cnt < count($matches[0]); $cnt++) {
				$info = pathinfo(trim($matches[0][$cnt], "\""));
				print_r($info);
				$ext = $info['extension'];
				if(($ext == 'jpg')||($ext == 'jpeg')||($ext == 'png')||($ext == 'gif')) {
					echo "Matched image raw: " . $matches[0][$cnt] . "\n\n\n";
					$image = true;
				} 
			}
		}
		
		if($image == false) {
			echo "No match\n\n\n";
		}
	}

?>