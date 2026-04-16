<?php
 defined('_JEXEC') or die('Restricted access');
 
$language  = $params->get("languages","");
$track     = $params->get("tracking","");
$indicator = $params->get("status_indicator","");
$agent = $params->get("agent_id","");
 
$tracking_script ='<script language="JavaScript" type="text/JavaScript" src="http://www.results.com.br/components/com_activehelper_livehelp/server/import/javascript.php">
 
</script> <script type="text/javascript"> 
_vlDomain =4;
_vlAgent ='. $agent.';
_vlService = 1; 
_vlLanguage ="'.$language.'";
_vlTracking ='.$track.';
 _vlStatus_indicator ='. $indicator.';
 startLivehelp();
</script>';
 
echo ($tracking_script);