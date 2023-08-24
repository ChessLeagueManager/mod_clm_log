<?php 
/**
 * @ Chess League Manager (CLM) Login Modul 
 * @Copyright (C) 2008-2023 CLM Team.  All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.chessleaguemanager.de
*/
// no direct access
defined('_JEXEC') or die('Restricted access'); 

	function getParam($name) {
		$db = JFactory::getDbo();
		$db->setQuery('SELECT manifest_cache FROM #__extensions WHERE element = "com_clm"');
		$manifest = json_decode($db->loadResult(), true);
		return $manifest[$name];
	}

// Mindest-Version der Hauptkomponente für Kontaktdatenpflege
$vversion = "4.1.0b";
function user_check($zps = - 1, $pkz = - 1, $mgl_nr = -1) {
	@set_time_limit(0); // hope
	$counter = 0;
	//CLM parameter auslesen
	$config = clm_core::$db->config();
	$countryversion = $config->countryversion;
	if ($countryversion !="de") {
		return array(false, "e_wrongCountry", $counter);
	}
	// deutsche Anwendung mit DSB-Daten
	$source = "https://dwz.svw.info/services/files/dewis.wsdl";
	$sid = clm_core::$access->getSeason();
	$zps = clm_core::$load->make_valid($zps, 8, "");
	$pkz = clm_core::$load->make_valid($pkz, 8, "");
	$mgl_nr = clm_core::$load->make_valid($mgl_nr, 8, "");
	if (strlen($zps) != 5) {
		return array(false, "e_wrongZPSFormat", $counter);
	}	
	// Check SOAP Webservice is down
	try {
		$client = new SoapClient($source,array('exceptions' => true));
		unset($client);
	}
	catch(Exception $e) {
		return array(false, "e_servicedownError");
	}

	// Using SOAP Webservice 
	try {
		$client = clm_core::$load->soap_wrapper($source);
		// VKZ des Vereins --> Vereinsliste
		$unionRatingList = $client->unionRatingList($zps);
		// Detaildaten zu Mitgliedern lesen
		foreach ($unionRatingList->members as $m) {
			if(intval($mgl_nr) == intval($m->membership) OR $pkz == $m->pid) {
				$counter++;
			}
		}
		unset($unionRatingList);
		unset($client);
	}
	catch(SOAPFault $f) {
		if($f->getMessage() == "that is not a valid union id" || $f->getMessage() == "that union does not exists") {
			return array(false, "e_wrongZPS",0);
		}
		return array(false, "e_connectionError");
	}
	if ($counter == 0) {
		return array(false, "e_dewisUserNone", $counter);
	}
	if ($counter > 1) {
		return array(false, "e_dewisUserMultiple", $counter);
	}
	return array(true, "m_dewisUserSuccess", $counter);
}

	// Konfigurationsparameter auslesen
	$config = clm_core::$db->config();
	$conf_meldeliste	= $config->conf_meldeliste;
	$conf_vereinsdaten	= $config->conf_vereinsdaten;
	$conf_ergebnisse	= $config->conf_ergebnisse;
	$meldung_heim		= $config->meldung_heim;
	$meldung_verein		= $config->meldung_verein;
	$conf_user_member	= $config->user_member;

	if ($conf_user_member == 1 ) {
//echo "<br>data:"; var_dump($data);
		$ucheck = user_check($data[0]->zps, $data[0]->PKZ, $data[0]->mglnr);
//echo "<br>ucheck:"; var_dump($ucheck);
	}
?>

<!-- ///////// User Published  ???  -->
<?php if (!$data OR $data[0]->published < 1) { ?>
Ihr Account ist noch nicht aktiviert oder wurde von einem Administrator gesperrt! <?php } 

// Check Mitglied DSB/ECF
elseif ($conf_user_member == 1 AND $ucheck[0] === false AND $data[0]->org_exc == '0') {
// Ihre Mitgliedschaft in der Schachorganisation kann nicht überprüft werden! 
	echo JText::_('E_CLM_BERECHTIGUNG').'<br><br>';
	echo JText::_($ucheck[1]);
	$name = 'keine CLM-Berechtigung';
	$content = str_replace ("<br>", " - ", JText::_($ucheck[1]));
	clm_core::addInfo($name,$content);
} 
	// Published OK !
else {
	if ($altItemid	!= '') { $itemid = $altItemid; }
	else { $itemid = '1'; }

if (isset($_GET['view'])) $cmd = $_GET['view']; else $cmd = '';
if (isset($_GET['layout'])) $cmd = $_GET['layout']; else $cmd = '';
$off="-1";
$cnt=0;

if ($conf_meldeliste == 1 AND $rangliste) {$cnt++;}
if ($conf_meldeliste == 1 AND $meldeliste) {$cnt++;}

if($cmd=="meldeliste" AND $layout=="") { $off =1;}
if($cmd=="meldeliste" AND $layout=="rangliste") { $off =1;}
if($cmd=="vereinsdaten") { $off =$cnt+1;}
if($cmd=="meldung") { $off =0;}

$usertype = $data[0]->usertype;

// Datum der Meldung
$now = date('Y-m-d H:i:s'); 
$today = date("Y-m-d"); 

?>
<style>

/* Style the tab */
.tab {
  overflow: hidden;
  border: 1px solid #ccc;
  /* background-color: #f1f1f1; */
}

/* Style the buttons inside the tab */
.tab button {
  background-color: inherit;
  border: 1px solid #ccc;
  outline: none;
  cursor: pointer;
  transition: 0.3s;
  font-size: 17px;
}

/* Change background color of buttons on hover */
.tab button:hover {
  background-color: #ddd;
}

/* Create an active/current tablink class */
.tab button.active {
  background-color: #ccc;
}

/* Style the tab content */
.tabcontent {
  display: none;
}
</style>

<script>
function openFunction(evt, clmFunction) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(clmFunction).style.display = "block";
  evt.currentTarget.className += " active";
}
</script>


<div class="tab">
<?php if ($vversion > getParam('version')) { ?>
  <button class="tablinks" onclick="openFunction(event, 'overview')"><?php echo JText::_('MOD_CLM_LOG_OVERVIEW') ?></button><br />
<?php } else { ?>
  <button class="tablinks" onclick="openFunction(event, 'contact')"><?php echo JText::_('MOD_CLM_LOG_CONTACT') ?></button><br />
<?php } ?>

<?php 	   	  
		if ($usertype != 'spl' AND ($liga)) { ?>
  <button class="tablinks" onclick="openFunction(event, 'input_result')"><?php echo JText::_('MOD_CLM_LOG_INPUT_RESULT') ?></button><br />
		<?php } ?>
  
<?php 	if ($usertype != 'spl' AND $conf_vereinsdaten == 1 AND $par_vereinsdaten == 1) { ?>  
   
  <button class="tablinks" onclick="openFunction(event, 'change_clubdata')"><?php echo JText::_('MOD_CLM_LOG_CHANGE_CLUBDATA') ?></button><br />
<?php } ?>
<?php 	
	if ($conf_meldeliste == 1 AND $meldeliste) {
		// Testen, ob Aufstellungen eingegeben werden können
		$t_meldeliste = 0;
		foreach ($meldeliste as $meldelistt){ 
			if ($meldelistt->liste < 1) { $t_meldeliste = 1; } 			
			else {
				//Liga-Parameter aufbereiten
				$paramsStringArray = explode("\n", $meldelistt->params);
				$meldelistt_params = array();
				foreach ($paramsStringArray as $value) {
					$ipos = strpos ($value, '=');
					if ($ipos !==false) {
					$key = substr($value,0,$ipos);
					if (substr($key,0,2) == "\'") $key = substr($key,2,strlen($key)-4);
					if (substr($key,0,1) == "'") $key = substr($key,1,strlen($key)-2);
					$meldelistt_params[$key] = substr($value,$ipos+1);
					}
				}	
			if (!isset($meldelistt_params['deadline_roster']) OR $meldelistt_params['deadline_roster'] == '')  {   //Standardbelegung
				$meldelistt_params['deadline_roster'] = '0000-00-00'; }
			if ($meldelistt_params['deadline_roster'] >= $today) { $t_meldeliste = 1; } 
			}
		}
		if ($usertype != 'spl' AND $t_meldeliste == 1) { ?>
  <button class="tablinks" onclick="openFunction(event, 'input_teamlineup')"><?php echo JText::_('MOD_CLM_LOG_INPUT_TEAMLINEUP') ?></button><br />
		<?php } 
	} ?>

<?php 	
	if ($rangliste) {
		// Testen, ob Ranglisten eingegeben werden können
		$t_rangliste = 0;
		foreach ($rangliste as $ranglistt){ 
			if ($ranglistt->id == '') { $t_rangliste = 1; } 			
			else {
				//Liga-Parameter aufbereiten
				$paramsStringArray = explode("\n", $ranglistt->params);
				$ranglistt_params = array();
				foreach ($paramsStringArray as $value) {
					$ipos = strpos ($value, '=');
					if ($ipos !==false) {
					$key = substr($value,0,$ipos);
					if (substr($key,0,2) == "\'") $key = substr($key,2,strlen($key)-4);
					if (substr($key,0,1) == "'") $key = substr($key,1,strlen($key)-2);
					$ranglistt_params[$key] = substr($value,$ipos+1);
					}
				}	
			if (!isset($ranglistt_params['deadline_roster']))  {   //Standardbelegung
				$ranglistt_params['deadline_roster'] = '1970-01-01'; }
			if ($ranglistt_params['deadline_roster'] >= $today) { $t_rangliste = 1; } 
			}
		}
		if ($usertype != 'spl' AND $t_rangliste == 1) { ?>
  <button class="tablinks" onclick="openFunction(event, 'input_clublineup')"><?php echo JText::_('MOD_CLM_LOG_INPUT_CLUBLINEUP') ?></button><br />
		<?php } 
	} ?>
</div>

<div id="overview" class="tabcontent">
	<b><?php echo "<br>".JText::_('MOD_CLM_LOG_HELLO')." ".$data[0]->name.' !' ?></b>
</div>

<div id="contact" class="tabcontent">
	<b><?php echo "<br>".JText::_('MOD_CLM_LOG_CONTACT')." ".$data[0]->name ?></b><br/>
        <?php if($data[0]->tel_fest == "") { $nr=JText::_("MOD_CLM_LOG_CONTACT_UNPROVIDED"); } else { $nr=$data[0]->tel_fest; } echo JText::_("MOD_CLM_LOG_CONTACT_WIRED") . " ".$nr; ?><br/>
        <?php if($data[0]->tel_mobil == "") { $nr=JText::_("MOD_CLM_LOG_CONTACT_UNPROVIDED"); } else { $nr=$data[0]->tel_mobil; } echo JText::_("MOD_CLM_LOG_CONTACT_MOBILE") . " ".$nr; ?><br/>
        <?php echo JText::_("MOD_CLM_LOG_CONTACT_EMAIL") . " ".$data[0]->email; ?><br/>
        <a class="link" href="index.php?option=com_clm&amp;view=contact"><?php echo JText::_("MOD_CLM_LOG_CONTACT_UPDATE"); ?></a>
</div>

<div id="input_result" class="tabcontent">
	<?php
	$c_rang = 0; $c_lid = 0; $c_tln_nr = 0;
	$oll = "";
	$oln = "";
//echo "<br>liga:"; var_dump($liga);
		foreach ($liga as $liga) {
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			$mdt = $liga->deadlineday.' ';
			$mdt .= $liga->deadlinetime;
			if (($liga->gemeldet < 1 OR $mdt >= $now) AND ($liga->liste > 0 OR ($liga->rang == 1 AND isset($liga->gid)))) {
				if (!($liga->meldung == 0 AND $params->get('runden') == 0)) {
					if ($c_rang != $liga->rang OR $c_lid != $liga->lid OR $c_tln_nr != $liga->tln_nr) {
						if (($liga->name != $oln) || ($liga->lname != $oll)) {
							echo "<h5><br>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</h5>'; 
							$oln = $liga->name;
							$oll = $liga->lname;
						}
						$c_rang = $liga->rang; $c_lid = $liga->lid; $c_tln_nr = $liga->tln_nr;
					}
			?>
			<a class="link" href="index.php?option=com_clm&amp;view=meldung&amp;saison=<?php echo $liga->sid;?>&amp;liga=<?php echo $liga->liga; ?>&amp;runde=<?php echo $liga->runde; ?>&amp;tln=<?php echo $liga->tln_nr; ?>&amp;paar=<?php echo $liga->paar; ?>&dg=<?php echo $liga->dg; ?>&amp;Itemid=<?php echo $itemid; ?>">
				<?php echo $liga->rname; ?>
			</a>
			<br>
<?php				}
			} else {
				if (($liga->name != $oln) || ($liga->lname != $oll)) {
					echo "<b><br>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</b><br/>Zur Zeit noch keine Meldung möglich.<br/>'; 
					$oln = $liga->name;
					$oll = $liga->lname;
				}
			}
		} ?>
</div>

<div id="change_clubdata" class="tabcontent">
	<b><?php echo "<br>".JText::_('MOD_CLM_LOG_CHANGE_CLUBDATA'); ?></b><br/>
		<div>
		<a href="index.php?option=com_clm&view=verein&saison=<?php echo $data[0]->sid; ?>&zps=<?php echo $data[0]->zps; ?>&layout=vereinsdaten&amp;Itemid=<?php echo $itemid; ?>"><?php echo $data[0]->vname; ?></a>
		</div>

</div>

<div id="input_teamlineup" class="tabcontent">
	<br>
	<?php foreach ($meldeliste as $meldeliste){ 
			$s_meldeliste = 0;
			if ($meldeliste->liste < 1) $s_meldeliste = 1;
			else {
				//Liga-Parameter aufbereiten
				$paramsStringArray = explode("\n", $meldeliste->params);
				$meldeliste->params = array();
				foreach ($paramsStringArray as $value) {
					$ipos = strpos ($value, '=');
					if ($ipos !==false) {
					$key = substr($value,0,$ipos);
					if (substr($key,0,2) == "\'") $key = substr($key,2,strlen($key)-4);
					if (substr($key,0,1) == "'") $key = substr($key,1,strlen($key)-2);
					$meldeliste->params[$key] = substr($value,$ipos+1);
					}
				}	
				if (!isset($meldeliste->params['deadline_roster']))  {   //Standardbelegung
					$meldeliste->params['deadline_roster'] = '0000-00-00'; }
				if ($meldeliste->params['deadline_roster'] < $today) $s_meldeliste = 0;
				else $s_meldeliste = 1;
			}
			if ($usertype != 'spl' AND $s_meldeliste == 1) { ?>
		<div>
			<a href="index.php?option=com_clm&view=meldeliste&saison=<?php echo $meldeliste->sid; ?>&zps=<?php echo $meldeliste->zps; ?>&lid=<?php echo $meldeliste->lid; ?>&man=<?php echo $meldeliste->man_nr; ?>&amp;Itemid=<?php echo $itemid; ?>"><?php echo $meldeliste->name; ?></a> - <?php echo $meldeliste->liganame; ?> 
		</div>
		<?php } } ?>

</div>

<div id="input_clublineup" class="tabcontent">
	<br>
	<?php foreach ($rangliste as $rangliste){
			$s_rangliste = 0;
			if ($rangliste->id == '') $s_rangliste = 1;
			else {
			//Liga-Parameter aufbereiten
			$paramsStringArray = explode("\n", $rangliste->params);
			$rangliste->params = array();
			foreach ($paramsStringArray as $value) {
				$ipos = strpos ($value, '=');
				if ($ipos !==false) {
				$key = substr($value,0,$ipos);
				if (substr($key,0,2) == "\'") $key = substr($key,2,strlen($key)-4);
				if (substr($key,0,1) == "'") $key = substr($key,1,strlen($key)-2);
				$rangliste->params[$key] = substr($value,$ipos+1);
				}
			}	
			if (!isset($rangliste->params['deadline_roster']))  {   //Standardbelegung
			$rangliste->params['deadline_roster'] = '1970-01-01'; }
			if ($rangliste->params['deadline_roster'] < $today) $s_rangliste = 0;
			else $s_rangliste = 1;
			}
			if ($usertype != 'spl' AND $s_rangliste == 1) { ?>
		<div>
			<a href="index.php?option=com_clm&view=meldeliste&layout=rangliste&saison=<?php echo $rangliste->sid; ?>&zps=<?php echo $rangliste->zps; ?>&gid=<?php echo $rangliste->gid; ?>&amp;Itemid=<?php echo $itemid; ?>"><?php echo $rangliste->gruppe; ?></a> 
		</div>
		<?php } } ?>

</div>

<?php
} ?>
