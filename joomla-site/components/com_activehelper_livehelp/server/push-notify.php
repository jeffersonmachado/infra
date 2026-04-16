<?php

/*  
Name : Push Notification for Chat Request
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
         
 $query = "SELECT DISTINCT id_domain, department , username, id_agent ".
           " FROM " . $table_prefix . "sessions WHERE active = 0 and (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`refresh`)) < '$connection_timeout'"; 
		   
  // error_log("SQL1 : " . $query ."\n" , 3 ,"push.log");	  		   

 $rows = $SQL->selectall($query);
   foreach ($rows as $key => $row)
    {

       $push_id_domain  = $row['id_domain'];
       $push_department = $row['department'];
       $push_username   = $row['username'];
       $push_id_agent   = $row['id_agent'];
    

         if($push_id_agent == '0' && $push_department <> '') 
              {
                 $queryu = "SELECT  id, department, device_id , device ".
                           " FROM ". $table_prefix . "users , ". $table_prefix ."domain_user " . 
                           " WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`refresh`)) < '$connection_timeout_mobile' and device_id is not NULL and ".
                           " id = id_user and id_domain =" ."$push_id_domain" . " and department = " .  "'$push_department'";
                } 
                else
             if($push_id_agent =='0' && $push_department =='') 
              {
                   $queryu = "SELECT  id, department, device_id , device ".
                           " FROM ". $table_prefix . "users , ". $table_prefix ."domain_user " . 
                           " WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`refresh`)) < '$connection_timeout_mobile' and device_id is not NULL and ".
                           " id = id_user and id_domain =" ."$push_id_domain" ;
                } 
                else                    
             if($push_id_agent <>'0') 
              {
                $queryu = "SELECT  id, department, device_id , device ".
                           " FROM ". $table_prefix . "users ".
                           " WHERE (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(`refresh`)) < '$connection_timeout_mobile' and device_id is not NULL and ".
                           " id =" ."$push_id_agent" ;
                }                                                         
                               
              $rowsu = $SQL->selectall($queryu);
			  
	    // error_log("SQL2 : " . $queryu ."\n" , 3 ,"push.log");	
			  
                if (is_array($rowsu))
                   {
                    foreach ($rowsu as $key => $rowu)
                      {
  
                       if (is_array($rowu))
                         {

                           $push_user_id     = $rowu['id'];
                           $push_user_dept   = $rowu['department'];
                           $push_user_device = $rowu['device_id'];  
                           $push_device      = $rowu['device'];						   
                           $msg_sound = 1;
						   
                           if ($push_device=="IOS") {
						     $call_URL = $push_api_path . "notify_ios.php?token=".$push_user_device. "&message="  . "$chat_request" .  "$push_username" . "&sound=" . "$msg_sound" ;
						   } else
							   
						  if ($push_device=="Android") {
						     $call_URL = $push_api_path . "notify_android.php?token=".$push_user_device. "&message="  . "$chat_request" .  "$push_username" . "&sound=" . "$msg_sound" ;
						    } 
							 
                           file_get_contents($call_URL);
                        //   error_log("URL: " . $call_URL."\n" , 3 ,"push.log");
                                                                                            
                 } 
              }                                                                         
          }
    }
?>
