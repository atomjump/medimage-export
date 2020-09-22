<?php
    include_once("classes/cls.pluginapi.php");
    

    
    
    class plugin_medimage_export
    {
       
       
 		  public function on_message($message_forum_id, $message, $message_id, $sender_id, $recipient_id, $sender_name, $sender_email, $sender_phone)
        {
            
            $api = new cls_plugin_api();
            
            //TODO: notify if there is no id when a photo is detected.
            //Check for existence of photo in message and initiate a sending process for that photo
            //Check if we don't have a paired MedImage Server stored, and warn user with a message
            //Check for a pairing with the MedImage Server i.e 'pair aBc1' or 'pr aBc1'
                      
            //TEMPOUT if(strpos($message, "id ") === 0) {
		         //Check for messages starting with 'id [patientid] [keywords]', which switch the id to send this to on the
		         //backend MedImage Server
		         $new_message = "Switched MedImage patient to ID: '" . $message . "'";
		         $recipient_ip_colon_id = "123.123.123.123:" . $recipient_id;
		         $sender_email = "medimage@atomjump.com";
		         $sender_ip = "111.111.111.111";
		         $options = array('notification' => false, 'allow_plugins' => false);
		      	$api->new_message($sender_name_str, $new_message, $recipient_ip_colon_id, $sender_email, $sender_ip, $message_forum_id, $options);
		      //}
            
            return true;
            
        }
		

        
        public function on_msg_buttons($message_id)
        {
        	global $root_server_url;
        	
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
        
           
            /*<script>
            	function medimageExport(msgId) {  
            		$.ajax({
							url: \"" . trim_trailing_slash_local($root_server_url) . "/plugins/medimage_export/export.php\", 
							data: data,
							type: 'POST',
							cache: false
							}).done(function(response) {
								alert(\"Response : \" + response);
        						
        					}
        			);
        		}
           	</script> */
            
           $ret_text = "<script>function medimageExport(msgId) { alert('Clicked'); data = { msg_id: " . $message_id . " }; jQuery.ajax({ url: \"" . $root_server_url . "/plugins/medimage_export/export.php\", data: data, type: 'POST', cache: false 		}).done(function(response) { alert(\"Response : \" + response);	}); }</script><a class=\"comment-msg-button\" href=\"javascript:\" onclick=\"alert('About to run MedImage Photo Export in here. Msg ID: " . $message_id . "'); medimageExport(" . $message_id . ");\"><img width=\"48\" src='" . $root_server_url . "/plugins/medimage_export/medimage_logo.png'></a>";
            return $ret_text;
            
        }
        
        public function on_upload_screen()
        {
        	global $root_server_url;
        	
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
           
            ?>
            	<br/>
            	<h4>Export Forum to MedImage (.pdf)</h4>
            
            <?php
           
            $ret_text = "<a class=\"comment-msg-button\" href=\"javascript:\" onclick=\"alert('TODO: MedImage Forum .pdf export in here. Forum ID: " . $_REQUEST['uniqueFeedbackId'] . "'); \"><img width=\"48\" src='" . $root_server_url . "/plugins/medimage_export/medimage_logo.png'></a>";
            
            
            echo $ret_text;
            
            return true;
            
        }
        
        
    }
?>
