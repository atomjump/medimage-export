<?php
    include_once("classes/cls.pluginapi.php");
    
    class plugin_medimage_export
    {
       
        
        public function on_msg_buttons($message_id)
        {
        	global $root_server_url;
        	
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
           
            $ret_text = "<a href=\"javascript:\" onclick=\"alert('TODO: MedImage Photo Export in here. Msg ID: " . $message_id . "'); \"><img src='" . $root_server_url . "/plugins/medimage_export/medimage_logo.png'></a>";
            return $ret_text;
            
        }
        
        public function on_upload_screen($message_id)
        {
        	global $root_server_url;
        	
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
           
            $ret_text = "<a href=\"javascript:\" onclick=\"alert('TODO: MedImage Photo Export in here. Msg ID: " . $message_id . "'); \"><img src='" . $root_server_url . "/plugins/medimage_export/medimage_logo.png'></a>";
            return $ret_text;
            
        }
        
        
    }
?>