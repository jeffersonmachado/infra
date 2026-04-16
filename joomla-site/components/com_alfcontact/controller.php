<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');
 
/**
 * ALFContact Component Controller
 */
class AlfcontactController extends JControllerLegacy
{
	function sendemail()
	{

		function in_ip_range($ip_one, $ip_two=false)
		{
			if($ip_two===false){
				if($ip_one==$_SERVER['REMOTE_ADDR']){
					$ip=true;
				}else{
					$ip=false;
				}
			}else{
				if(ip2long($ip_one)<=ip2long($_SERVER['REMOTE_ADDR']) && ip2long($ip_two)>=ip2long($_SERVER['REMOTE_ADDR'])){
					$ip=true;
				}else{
					$ip=false;
				}
			}
			return $ip;
		}
				
		$app = JFactory::getApplication();
		$jinput = $app->input;
        $menu = $app->getMenu()->getActive();
		$model = $this->getModel();

		// check if website uses CloudFlare and set IP
		if (in_ip_range('204.93.240.0','204.93.240.255') ||
		    in_ip_range('204.93.177.0','204.93.177.255') ||
		    in_ip_range('199.27.128.0','199.27.135.255') ||
		    in_ip_range('173.245.48.0','173.245.63.255') ||
			in_ip_range('103.22.200.0','103.22.203.255') ||
		    in_ip_range('141.101.64.0','141.101.127.255') ||
		    in_ip_range('108.162.192.0','108.162.255.255') ||
		    in_ip_range('190.93.240.0','190.93.255.255'))
		{
			$site_ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
		} else{
			$site_ip = $_SERVER['REMOTE_ADDR'];
		}
		
		// get the parameters
		$params = JComponentHelper::getParams('com_alfcontact');
		$redirect_option = $params->get('redirect_option', 1);
		$redirect_url = $params->get('url', '');
		$verbose = $params->get('verbose', 1);
		$html = $params->get('mailformat', 1);
		$site = $params->get('fromsite', 0);
        $gpdrinfo = $menu->params->get('gpdr_info', '');
        $gpdrtext = $menu->params->get('gpdr_label', '');

		$gpdrcheck  = $params->get('gpdrcheck', 0);
		$sitename = $app->get('fromname');
		$siteaddress = $app->get('mailfrom');

		
		if ($html)
		{
			$sep  = "<BR>";
			$line = "<HR>";
		} 
		else 
		{
			$sep  = PHP_EOL;
			$line = PHP_EOL . '-------------------------------------------------------------------------------' . PHP_EOL;
		}
						
				
		//Variable ophalen die verstuurd zijn via URL
        $name       = $jinput->getString('name','', 'post');
		$email      = $jinput->getString('email','', 'post');
		$emailto_id = $jinput->getInt('emailto_id', 99);
		$subject    = $jinput->getString('subject','','post');
        $message    = $jinput->getString('message','','post');
		$copy       = $jinput->getBool('copy', 0);
		$gpdr       = $jinput->getBool('gpdr_consent', 0);
		$extravalues= $jinput->getString('extravalues','','post');
		    
        //Store form data in the session
     	$app->setUserState('com_alfcontact.name', $name);
		$app->setUserState('com_alfcontact.email', $email);
		$app->setUserState('com_alfcontact.emailto_id', $emailto_id);
		$app->setUserState('com_alfcontact.subject', $subject);
		$app->setUserState('com_alfcontact.message', $message);
		$app->setUserState('com_alfcontact.copy', $copy);
		$app->setUserState('com_alfcontact.gpdr_consent', $gpdr);
				
		//check the security measures
		if (!$model->CheckCaptcha()) 
		{
			JFactory::getApplication()->enqueueMessage(JText::_('COM_ALFCONTACT_WRONG_CAPTCHA'), 'error');
			$this->setRedirect(JRoute::_('index.php?option=com_alfcontact&view=alfcontact', false));
			return false;
		}
		
		// field validation - we trim the input to prevent whitespace-only values
		if (!trim($name)) {
			JFactory::getApplication()->enqueueMessage(JText::_('COM_ALFCONTACT_INVALID_NAME'), 'warning');
			$this->setRedirect(JRoute::_('index.php?option=com_alfcontact&view=alfcontact', false));
			return false;
		}

		if (!preg_match('/^[a-zA-Z0-9._-]+(\\+[a-zA-Z0-9._-]+)*@([a-zA-Z0-9.-]+\\.)+[a-zA-Z0-9.-]{2,10}$/', $email)) {
			JFactory::getApplication()->enqueueMessage(JText::_('COM_ALFCONTACT_INVALID_EMAIL'), 'warning');
			$this->setRedirect(JRoute::_('index.php?option=com_alfcontact&view=alfcontact', false));
			return false;
		}

		if (!trim($subject)) {
			JFactory::getApplication()->enqueueMessage(JText::_('COM_ALFCONTACT_INVALID_SUBJECT'), 'warning');
			$this->setRedirect(JRoute::_('index.php?option=com_alfcontact&view=alfcontact', false));
			return false;
		}

		if (!trim($message)) {
			JFactory::getApplication()->enqueueMessage(JText::_('COM_ALFCONTACT_INVALID_MESSAGE'), 'warning');
			$this->setRedirect(JRoute::_('index.php?option=com_alfcontact&view=alfcontact', false));
			return false;
		}
                
		//get email address coresponding to ID number
		if ($emailto_id == '99')
		{
			$emailto = $siteaddress; 
		}
		else
		{		
			
			$db = JFactory::getDBO();
			$query = "SELECT * FROM #__alfcontact WHERE id =". (int) $emailto_id;
		
			$db->setQuery( $query );
        	$rows      = $db->loadObjectList();
			$emailto   = $rows[0]->email;
            $bcc       = $rows[0]->bcc;
			$prefix    = $rows[0]->prefix;
			$optfields = $rows[0]->extra;
					
            //Adding prefix to subject
            $origSubject = $subject;
			$subject = $prefix.' '.$subject;
		}
		
		//Split multiple email addresses into an array
		$recipients = explode("\n", $emailto);

		// Add information from the extra fields if applicable
		if (!empty($optfields)) {
			$fields_array = explode("\n", $optfields);
			$values_array = explode('#', $extravalues);

			unset($values_array[0]);
			$extra_array = array_combine($fields_array, $values_array);

			$extramsg = '';
			foreach ($extra_array as $key => $value) {
				$extramsg = $extramsg . $key . ' ' . $value . $sep;
			}
			$message = $extramsg . $sep . JText::_('COM_ALFCONTACT_FORM_MESSAGE') . $sep .$message;
		}

		// send copy if requested
		if ($copy)
		{
			$copySubject = JText::_('COM_ALFCONTACT_COPYOFMESSAGE').' '.$sitename ;
			$copyHeader = JText::_('COM_ALFCONTACT_FORM_SUBJECT').' '. $origSubject . $sep;

			if ($gpdr)
			{
				$copyHeader = $copyHeader . $sep . JText::_('COM_ALFCONTACT_OPTIONS_GPDR_CONSENT_COPY').' '. $sep;
				$copyHeader = $copyHeader . " " . $gpdrinfo . $sep;
				$copyHeader = $copyHeader . " " . $gpdrtext . $sep . $sep;
			}
			$copyHeader = $copyHeader . $line;
			$copyMessage = $copyHeader . $message;
			$mail = JFactory::getMailer();
			$mail->addRecipient($email);
			$mail->setSender(array($siteaddress, $sitename));
			$mail->setSubject($copySubject);
			$mail->setBody($copyMessage);
			if ($html)
			{
				$mail->IsHTML(True);
				//$mail->setBody(nl2br($message));
		    }

		    //If required check GPDR value before sending mail
            if ($gpdrcheck) {
                if ($gpdr) {
                    $sent = $mail->Send();
                }
            } else {
                $sent = $mail->Send();
            }
		}
		
		//Add an infomation banner to the top of the contacts message.
		switch ($verbose) {
			case 1:
				$header = JText::_('COM_ALFCONTACT_DETAILS_HEADER') . $sep;
				$header = $header . $line;
				$header = $header . JText::_('COM_ALFCONTACT_DETAILS_NAME') . " " . $name . $sep;
				$header = $header . JText::_('COM_ALFCONTACT_DETAILS_EMAIL') . " " . $email . $sep;
				$header = $header . JText::_('COM_ALFCONTACT_DETAILS_IP') . " " . $site_ip . $sep;
				$header = $header . JText::_('COM_ALFCONTACT_DETAILS_BROWSER') . " " .$_SERVER['HTTP_USER_AGENT'] . $sep;

				if ($gpdr)
				{
					$header = $header . $sep . JText::_('COM_ALFCONTACT_OPTIONS_GPDR_CONSENT') . ' ' . $sep;
					$header = $header . " " . $gpdrinfo . $sep;
					$header = $header . " " . $gpdrtext . $sep . $sep;
				}
				$header = $header . $line;
				$message = $header . $message;
				break;
			case 2:
				$header = JText::_('COM_ALFCONTACT_DETAILS_HEADER') . $sep;
				$header = $header . $line;
				$header = $header . JText::_('COM_ALFCONTACT_DETAILS_NAME') . " " . $name . $sep;
				$header = $header . JText::_('COM_ALFCONTACT_DETAILS_EMAIL') . " " . $email . $sep;
				$header = $header . $line;
				$message = $header . $message;
				break;
			case 3:
				break;
		}
		
		//send mail
		$mail = JFactory::getMailer();
		
		foreach($recipients as $value)
		{
			$mail->addRecipient($value);
		}

		if(!empty($bcc)){
			//Split multiple bcc addresses into an array
			$bccs = explode("\n", $bcc);
			foreach($bccs as $value)
			{
				$mail->addBCC($value);
			}
		}

		if ($site)
		{
			$mail->setSender(array($siteaddress, $name));
		}
		else
		{
			$mail->setSender(array($email, $name));
		}
				
		$mail->setSubject($subject);
		$mail->setBody($message);
		$mail->addReplyTo($email, $name);
		
		if ($html) 
		{
			$mail->IsHTML(True);
			//$mail->setBody($message);
			//$mail->setBody(nl2br($message));
		}

        //If required check GPDR value before sending mail
        if ($gpdrcheck) {
            if ($gpdr) {
                $sent = $mail->Send();
            }
        } else {
            $sent = $mail->Send();
        }
		
		//Clear session variables
		$app->setUserState('com_alfcontact.name', null);
		$app->setUserState('com_alfcontact.email', null);
		$app->setUserState('com_alfcontact.emailto_id', null);
		$app->setUserState('com_alfcontact.subject', null);
		$app->setUserState('com_alfcontact.message', null);
		$app->setUserState('com_alfcontact.copy', null);
		
		//redirect
		switch ($redirect_option) {
			case 2: $this->setRedirect(JURI::current());
					break;
			case 3: $this->setRedirect(JRoute::_('index.php?option=com_alfcontact&view=response'));
					break;
			case 4: $this->setRedirect($redirect_url);
					break;
			default:$this->setRedirect(JRoute::_(JURI::root()));
					break;
		}
	}    
}
