<?php
    include_once("classes/cls.pluginapi.php");
    
    class plugin_medimage_export
    {
       
        
        public function on_msg_buttons($message_id)
        {
            //Do your thing in here. Here is a sample.
            $api = new cls_plugin_api();
          
           
            
            return "<a href=\"javascript:\" onclick=\"alert('TODO: Export in here'); \">MedImage Export</a>";
            
        }
        
        
    }
?>