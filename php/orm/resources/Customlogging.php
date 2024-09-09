<?php
 // this to log info to a file, as typical php error logging not working
     const CUSTOM_ERROR_LOG_FILE='/opt/app-root/etc/customphp.log'; 

     function custom_error_log($msg) {
       if (getenv('DEVELOPMENT_INSTANCE')) {
         error_log($msg);
       } else {
		$myfile = fopen(CUSTOM_ERROR_LOG_FILE, "a") ;
        fwrite($myfile, $msg . '
');  //\n not working.
		fclose($myfile); 
	 } 
     }
	?>
