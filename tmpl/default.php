<?php 
/**
 * @ Chess League Manager (CLM) Login Modul 
 * @Copyright (C) 2008-2025 CLM Team.  All rights reserved
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
	$fe_sl_ergebnisse 	= $config->fe_sl_ergebnisse;
	$fe_ar_ergebnisse 	= $config->fe_ar_ergebnisse;
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

// CLM-Version der inszallierten Hauptvariante
$clm_version = clm_core::$load->version();
$clm_version = $clm_version[0];
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
	if ($usertype != 'spl' AND ($liga)) { 
		$t_input_result = 0;
		foreach ($liga as $ligat) {
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			$mdt = $ligat->deadlineday.' ';
			$mdt .= $ligat->deadlinetime;
			if (($ligat->gemeldet < 1 OR $mdt >= $now) AND ($ligat->liste > 0 OR ($ligat->rang > 0 AND isset($ligat->gid)))) {
				$t_input_result = 1;
			}
		}
 		if ($t_input_result == 1) { ?>
  <button class="tablinks" onclick="openFunction(event, 'input_result')"><?php echo JText::_('MOD_CLM_LOG_INPUT_RESULT') ?></button><br />
		<?php } 
	} ?>
		
<?php
	// Mindestversion der CLM-Hauptkomponente für die Ergebniseingabe im FE durch SL
	$min_version = '4.2.3a';
	if ($usertype != 'spl' AND $usertype != 'mf' AND ($liga_sl) AND $fe_sl_ergebnisse == 1 ) { 
	if ($clm_version >= $min_version) {
		// Testen, ob Benutzer in meldebereiten Paarungen als Staffelleiter eingetragen ist - schon im helper erledigt
		$t_input_result_sl = 0;
		$a_paar = array();
		foreach ($liga_sl as $liga_slt) {
			$params_sl = new clm_class_params($liga_slt->params);
			$params_fe_sl_ergebnisse = $params_sl->get('fe_sl_ergebnisse',0);
			if ($params_fe_sl_ergebnisse == 1) {
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			$mdt = $liga_slt->deadlineday.' ';
			$mdt .= $liga_slt->deadlinetime;
			if (($liga_slt->gemeldet < 1 OR $mdt >= $now) AND ($liga_slt->liste > 0 OR ($liga_slt->rang > 0 AND isset($liga_slt->gid)))) {
				$t_input_result_sl = 1;
				if (!isset($a_paar[$liga_slt->lid][$liga_slt->dg][$liga_slt->runde]))  $a_paar[$liga_slt->lid][$liga_slt->dg][$liga_slt->runde] = 'p'.(string) $liga_slt->paar;
				else  $a_paar[$liga_slt->lid][$liga_slt->dg][$liga_slt->runde] .= 'p'.(string) $liga_slt->paar;
			}}
		}
 		if ($t_input_result_sl == 1) { ?>
 <button class="tablinks" onclick="openFunction(event, 'input_result_sl')"><?php echo JText::_('MOD_CLM_LOG_INPUT_RESULT_SL') ?></button><br />
		<?php }  
	}
	} ?>
	
<?php
	// Mindestversion der CLM-Hauptkomponente für die Ergebniseingabe im FE durch Arbiter / Scchiedsrichter
	$min_version = '4.2.3c';
	if ($usertype != 'spl' AND $usertype != 'mf' AND ($liga_ar) AND $fe_ar_ergebnisse == 1 ) { 
	if ($clm_version >= $min_version) {
		// Testen, ob Benutzer in meldebereiten Paarungen als Staffelleiter eingetragen ist - schon im helper erledigt
		$t_input_result_ar = 0;
		$a_paar = array();
		foreach ($liga_ar as $liga_art) {
			$params_ar = new clm_class_params($liga_art->params);
			$params_fe_ar_ergebnisse = $params_ar->get('fe_ar_ergebnisse',0);
			if ($params_fe_ar_ergebnisse == 1) {
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			$mdt = $liga_art->deadlineday.' ';
			$mdt .= $liga_art->deadlinetime;
			if (($liga_art->gemeldet < 1 OR $mdt >= $now) AND ($liga_art->liste > 0 OR ($liga_art->rang > 0 AND isset($liga_art->gid)))) {
				$t_input_result_ar = 1;
				if (!isset($a_paar[$liga_art->lid][$liga_art->dg][$liga_art->runde]))  $a_paar[$liga_art->lid][$liga_art->dg][$liga_art->runde] = 'p'.(string) $liga_art->paar;
				else  $a_paar[$liga_art->lid][$liga_art->dg][$liga_art->runde] .= 'p'.(string) $liga_art->paar;
			}}
		}
 		if ($t_input_result_ar == 1) { ?>
 <button class="tablinks" onclick="openFunction(event, 'input_result_ar')"><?php echo JText::_('MOD_CLM_LOG_INPUT_RESULT_AR') ?></button><br />
		<?php }  
	}
	} ?>
	
<?php
 	if ($usertype != 'spl' AND $conf_vereinsdaten == 1 AND $par_vereinsdaten == 1) {   
			if (!is_null($data[0]->zps) AND $data[0]->zps > '0') { ?>
  <button class="tablinks" onclick="openFunction(event, 'change_clubdata')"><?php echo JText::_('MOD_CLM_LOG_CHANGE_CLUBDATA') ?></button><br />
	<?php } } ?>
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
//echo "<br>klasse:"; var_dump(($params->get('klasse')));
		foreach ($liga as $ligat) {
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			$mdt = $ligat->deadlineday.' ';
			$mdt .= $ligat->deadlinetime;
			if (($ligat->gemeldet < 1 OR $mdt >= $now) AND ($ligat->liste > 0 OR ($ligat->rang > 0 AND isset($ligat->gid)))) {
				// if (!($liga->meldung == 0 AND $params->get('runden') == 0)) {
					if ($c_rang != $ligat->rang OR $c_lid != $ligat->lid OR $c_tln_nr != $ligat->tln_nr) {
						if (($ligat->name != $oln) || ($ligat->lname != $oll)) {
//							echo "<h5><br>".$ligat->name; if ($params->get('klasse') == 1) { echo ' - '.$ligat->lname; } echo '</h5>'; 
							echo "<b><br>".$ligat->name; if ($params->get('klasse') == 1) { echo ' - '.$ligat->lname; } echo '</b><br/>'; 
							$oln = $ligat->name;
							$oll = $ligat->lname;
						}
						$c_rang = $ligat->rang; $c_lid = $ligat->lid; $c_tln_nr = $ligat->tln_nr;
					}
			?>
			<a class="link" href="index.php?option=com_clm&amp;view=meldung&amp;saison=<?php echo $ligat->sid;?>&amp;liga=<?php echo $ligat->liga; ?>&amp;runde=<?php echo $ligat->runde; ?>&amp;tln=<?php echo $ligat->tln_nr; ?>&amp;paar=<?php echo $ligat->paar; ?>&dg=<?php echo $ligat->dg; ?>&amp;Itemid=<?php echo $itemid; ?>">
				<?php echo $ligat->rname; ?>
			</a>
			<br>
<?php			//	}
			} else {
				if (($ligat->name != $oln) || ($ligat->lname != $oll)) {
//					echo "<b><br>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</b><br/>Zur Zeit noch keine Meldung möglich.<br/>'; 
					echo "<b><br>".$ligat->name; if ($params->get('klasse') == 1) { echo ' - '.$ligat->lname; } echo '</b><br/>'; 
					$oln = $ligat->name;
					$oll = $ligat->lname;
				}
			}
		} ?>
</div>

<div id="input_result_sl" class="tabcontent">
	<?php
	$c_lid = 0; $c_dg = 0; $c_runde = 0;
	$oll = "";
	$oln = "";
	$a_unfit = array();
	$e_unfit = array(); $ie = 0;
	foreach ($liga_sl as $liga_slt) {
		$params_sl = new clm_class_params($liga_slt->params);
		$params_fe_sl_ergebnisse = $params_sl->get('fe_sl_ergebnisse',0);
		if ($params_fe_sl_ergebnisse == 1) { 
			$mdt = $liga_slt->deadlineday.' ';
			$mdt .= $liga_slt->deadlinetime;
			// Wenn NICHT gemeldet und entweder keine Deadline angegeben oder diese bereits überschritten ist
			if (($liga_slt->gemeldet < 1) AND ($mdt <= '1970-01-0124:00:00' OR $mdt < $now)) {
				if (isset($a_unfit[$liga_slt->lid][$liga_slt->dg][$liga_slt->runde])) continue;
				$ie++;
				if ($liga_slt->durchgang == 1) $dtext = ''; else $dtext = ' Dg: '.$liga_slt->dg;
				$htext = $liga_slt->lname.$dtext.' Runde: '.$liga_slt->runde; 
				$a_unfit[$liga_slt->lid][$liga_slt->dg][$liga_slt->runde] = $htext;
				$b_unfit[$ie] = new stdClass();
				$b_unfit[$ie]->htext = $htext;
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			} elseif (($liga_slt->gemeldet < 1 OR $mdt >= $now)) {
				if ($c_lid != $liga_slt->lid OR $c_dg != $liga_slt->dg OR $c_runde != $liga_slt->runde) {
//					if (($liga->name != $oln) || ($liga->lname != $oll)) {
					if ($liga_slt->durchgang == 1) $dtext = ''; else $dtext = ' Dg: '.$liga_slt->dg;
						echo "<h5><br>".$liga_slt->lname.$dtext.' Runde: '.$liga_slt->runde.'</h5>'; 
						$oln = $liga_slt->name;
						$oll = $liga_slt->lname;
//					}
					$c_lid = $liga_slt->lid; $c_dg = $liga_slt->dg; $c_runde = $liga_slt->runde;
				}
				if (isset($a_paar[$liga_slt->lid][$liga_slt->dg][$liga_slt->runde])) $apaar = $a_paar[$liga_slt->lid][$liga_slt->dg][$liga_slt->runde]; else $apaar = '0';
			?>
			<a class="link" href="index.php?option=com_clm&amp;view=meldung_sl&amp;saison=<?php echo $liga_slt->sid;?>&amp;liga=<?php echo $liga_slt->liga; ?>&amp;dg=<?php echo $liga_slt->dg; ?>&amp;runde=<?php echo $liga_slt->runde; ?>&amp;paar=<?php echo $liga_slt->paar; ?>&amp;tln=<?php echo $liga_slt->tln_nr; ?>&amp;apaar=<?php echo $apaar; ?>&amp;Itemid=<?php echo $itemid; ?>">
				<?php echo $liga_slt->name.' - '.$liga_slt->gname; ?>
			</a>
			<br>
<?php			//	}
			} else {
				if (($liga_slt->name != $oln) || ($liga_slt->lname != $oll)) {
//					echo "<b><br>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</b><br/>Zur Zeit noch keine Meldung möglich.<br/>'; 
//					echo "<b><br>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</b><br/>'; 
					$oln = $liga_slt->name;
					$oll = $liga_slt->lname;
				}
			}
		}
	}
	foreach ($b_unfit as $unfit) {
		echo "<br>".$unfit->htext." - Deadline ungeeignet für schrittweise Erg.eingabe";
	}	
?>
</div>

<div id="input_result_ar" class="tabcontent">
	<?php
	$c_lid = 0; $c_dg = 0; $c_runde = 0;
	$oll = "";
	$oln = "";
	$a_unfit = array();
	$e_unfit = array(); $ie = 0;
	foreach ($liga_ar as $liga_art) {
		$params_ar = new clm_class_params($liga_art->params);
		$params_fe_ar_ergebnisse = $params_ar->get('fe_ar_ergebnisse',0);
		if ($params_fe_ar_ergebnisse == 1) { 
			$mdt = $liga_art->deadlineday.' ';
			$mdt .= $liga_art->deadlinetime;
			// Wenn NICHT gemeldet und entweder keine Deadline angegeben oder diese bereits überschritten ist
			if (($liga_art->gemeldet < 1) AND ($mdt <= '1970-01-0124:00:00' OR $mdt < $now)) {
				if (isset($a_unfit[$liga_art->lid][$liga_art->dg][$liga_art->runde])) continue;
				$ie++;
				if ($liga_art->durchgang == 1) $dtext = ''; else $dtext = ' Dg: '.$liga_art->dg;
				$htext = $liga_art->lname.$dtext.' Runde: '.$liga_art->runde; 
				$a_unfit[$liga_art->lid][$liga_art->dg][$liga_art->runde] = $htext;
				$b_unfit[$ie] = new stdClass();
				$b_unfit[$ie]->htext = $htext;
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			} elseif ($liga_art->gemeldet < 1 OR $mdt >= $now) {
				if ($c_lid != $liga_art->lid OR $c_dg != $liga_art->dg OR $c_runde != $liga_art->runde) {
//					if (($liga->name != $oln) || ($liga->lname != $oll)) {
					if ($liga_art->durchgang == 1) $dtext = ''; else $dtext = ' Dg: '.$liga_art->dg;
						echo "<h5><br>".$liga_art->lname.$dtext.' Runde: '.$liga_art->runde.'</h5>'; 
						$oln = $liga_art->name;
						$oll = $liga_art->lname;
//					}
					$c_lid = $liga_art->lid; $c_dg = $liga_art->dg; $c_runde = $liga_art->runde;
				}
				if (isset($a_paar[$liga_art->lid][$liga_art->dg][$liga_art->runde])) $apaar = $a_paar[$liga_art->lid][$liga_art->dg][$liga_art->runde]; else $apaar = '0';
			?>
			<a class="link" href="index.php?option=com_clm&amp;view=meldung_sl&amp;saison=<?php echo $liga_art->sid;?>&amp;liga=<?php echo $liga_art->liga; ?>&amp;dg=<?php echo $liga_art->dg; ?>&amp;runde=<?php echo $liga_art->runde; ?>&amp;paar=<?php echo $liga_art->paar; ?>&amp;tln=<?php echo $liga_art->tln_nr; ?>&amp;apaar=<?php echo $apaar; ?>&amp;Itemid=<?php echo $itemid; ?>">
				<?php echo $liga_art->name.' - '.$liga_art->gname; ?>
			</a>
			<br>
<?php			//	}
			} else {
				if (($liga_art->name != $oln) || ($liga_art->lname != $oll)) {
//					echo "<b><br>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</b><br/>Zur Zeit noch keine Meldung möglich.<br/>'; 
//					echo "<b><br>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</b><br/>'; 
					$oln = $liga_art->name;
					$oll = $liga_art->lname;
				}
			}
		}	
	}
	foreach ($b_unfit as $unfit) {
		echo "<br>".$unfit->htext." - Deadline ungeeignet für schrittweise Erg.eingabe";
	}	
?>
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
