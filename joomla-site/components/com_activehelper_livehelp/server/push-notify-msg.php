<?php

/*  
Name : Push Notification for Messages
Ver  : 4.2
*/

ob_start( 'ob_gzhandler' );

include_once('import/constants.php');
include('/import/class.mysql.php');

$language_file = './i18n/' . LANGUAGE_TYPE . '/lang_guest_' . LANGUAGE_TYPE . '.php';
if (file_exists($language_file)) {
        include($language_file);
}
else {
        include('./i18n/en/lang_guest_en.php');
}

// Open MySQL Connection
$SQL = new MySQL();
$SQL->connect();
         
		 
	/*	$query ="  SELECT  lu.device_id , lu.device, lm.id , lm.message ".
                   " FROM " . $table_prefix . "sessions ls , " . $table_prefix . "users lu ,  " . $table_prefix . "messages lm ".
				     " WHERE ls.active in(1,2) and ls.id_user =  lu.id  and ". 
                     " lu.device_id <> '' and ls.id = lm.session and lm.delivered = 0 and ((UNIX_TIMESTAMP(NOW()))- (UNIX_TIMESTAMP(lm.datetime)) )< 60";
		*/			 
			
		// New condition to always notify by push
		
          $query ="  SELECT  lu.device_id , lu.device, lm.id , lm.message ".
                   " FROM " . $table_prefix . "sessions ls , " . $table_prefix . "users lu ,  " . $table_prefix . "messages lm ".
				     " WHERE ls.active  = lu.id  and ls.id_user =  lu.id  and ". 
                     " lu.device_id is not NULL and ls.id = lm.session and lm.delivered = 0 and ((UNIX_TIMESTAMP(NOW()))- (UNIX_TIMESTAMP(lm.datetime)) )< 30";					 
		   		 		   
    //error_log("SQL : " . $query ."\n" , 3 ,"push_message.log");	  		   

   $rows = $SQL->selectall($query);
   $msg_sound = 2;
   
   foreach ($rows as $key => $row)
    {

       $push_user_device  = $row['device_id'];
       $message_id        = $row['id'];
       $message           = $row['message'];
	   $push_device       = $row['device'];		
       
	 // error_log("push_user_device : " . $push_user_device ."\n" , 3 ,"push_message.log");	
	  
	   if($push_user_device <> '' && $message_id <> '') 
              {
                if ($push_device=="IOS") {				
				     $call_URL = $push_api_path . "notify_ios.php?token=".$push_user_device. "&message=" . "$new_msg_arrives" . "$message" . "&sound=" . "$msg_sound"  ;
				     } 
				   else	 
				if ($push_device=="Android") {				
					 $call_URL = $push_api_path . "notify_android.php?token=".$push_user_device. "&message=" . "$new_msg_arrives" . "$message" . "&sound=" . "$msg_sound"  ;
                   } 
				   
				// error_log("URL: " . $call_URL."\n" , 3 ,"push_message.log");	  		   
				
                 file_get_contents($call_URL);
                           				 
				 $query = " UPDATE ". $table_prefix . "messages SET delivered = 1 WHERE id =" .  "'$message_id'";
				 $SQL->miscquery($query);
				 
				// error_log("SQL1 : " . $query ."\n" , 3 ,"push_message.log");	
				  
				 
   
                } 
    }
?>
