<?php
    include_once("classes/cls.pluginapi.php");
    

    
    
    class plugin_medimage_export
    {
       
       

        
        public function on_msg_buttons($message_id)
        {
        	global $root_server_url;
        	
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
        
           /*
           //Now switch back to the main screen
        						//doSearch();
        						//$(\"#comment-popup-content\").show(); 
								//$(\"#comment-upload\").hide(); 
			*/
            
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
            
           $ret_text = "<script>function medimageExport(msgId) { alert('Clicked'); data = null; jQuery.ajax({ url: \"" . trim_trailing_slash_local($root_server_url) . "/plugins/medimage_export/export.php\", data: data, type: 'POST', cache: false 		}).done(function(response) { alert(\"Response : \" + response);	}); }</script><a class=\"comment-msg-button\" href=\"javascript:\" onclick=\"alert('About to run MedImage Photo Export in here. Msg ID: " . $message_id . "'); medimageExport(" . $message_id . ");\"><img width=\"48\" src='" . $root_server_url . "/plugins/medimage_export/medimage_logo.png'></a>";
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