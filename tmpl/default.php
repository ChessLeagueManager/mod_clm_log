<?php // no direct access
defined('_JEXEC') or die('Restricted access'); ?>

<!-- ///////// Published  ???  -->
<?php if (!$data OR $data[0]->published < 1) { ?>
Ihr Account ist noch nicht aktiviert oder wurde von einem Administrator gesperrt ! <?php } 

// Published OK !
else {
	// Konfigurationsparameter auslesen
	$config 		= JComponentHelper::getParams( 'com_clm' );
	$conf_meldeliste	= $config->get('conf_meldeliste',1);
	$conf_vereinsdaten	= $config->get('conf_vereinsdaten',1);
	$conf_ergebnisse	= $config->get('conf_ergebnisse',1);
	$meldung_heim		= $config->get('meldung_heim',1);
	$meldung_verein		= $config->get('meldung_verein',1);
	
	if ($altItemid	!= '') { $itemid = $altItemid; }
	else { $itemid = '1'; }

$document = JFactory::getDocument();
$cssDir = JURI::base().'modules'.DS.'mod_clm_log';
$document->addStyleSheet( $cssDir.DS.'sliders.css', 'text/css', null, array() );

$cmd	= JRequest::getCmd('view');
$layout	= JRequest::getCmd('layout');
$off="-1";
$cnt=0;

if ($conf_meldeliste == 1 AND $rangliste) {$cnt++;}
if ($conf_meldeliste == 1 AND $meldeliste) {$cnt++;}

if($cmd=="meldeliste" AND $layout=="") { $off =1;}
if($cmd=="meldeliste" AND $layout=="rangliste") { $off =1;}
if($cmd=="vereinsdaten") { $off =$cnt+1;}
if($cmd=="meldung") { $off =0;}

// Datum der Meldung
$now = date('Y-m-d H:i:s'); 
?>

<div><h4>Hallo SF <?php echo $data[0]->name.' !'; ?></h4></div>
<?php jimport('joomla.html.pane');
//$pane =& JPane::getInstance('sliders', array('startOffset'=>$off));
$pane =& JPane::getInstance('sliders', array('startOffset'=>$off, 'startTransition'=> 0 ));
echo $pane->startPane( 'pane');
	echo $pane->startPanel( 'Ergebnisse melden', 'panel1' );
	
		//$vorher = 999;
		$c_rang = 0; $c_lid = 0; $c_tln_nr = 0;
		foreach ($liga as $liga ) {
			// Wenn NICHT gemeldet oder noch Zeit zu korrigieren dann Runde anzeigen
			$mdt = $liga->deadlineday.' ';
			$mdt .= $liga->deadlinetime;
			if (($liga->gemeldet < 1 OR $mdt >= $now) AND ($liga->liste > 0 OR ($liga->rang == 1 AND isset($liga->gid)))) {
			  if (!($liga->meldung == 0 AND $params->get('runden') == 0)) {
				if ($c_rang != $liga->rang OR $c_lid != $liga->lid OR $c_tln_nr != $liga->tln_nr) {
					echo "<h4>".$liga->name; if ($params->get('klasse') == 1) { echo ' - '.$liga->lname; } echo '</h4>'; 
					$c_rang = $liga->rang; $c_lid = $liga->lid; $c_tln_nr = $liga->tln_nr;
				}
			?>
			<a class="link" href="index.php?option=com_clm&amp;view=meldung&amp;saison=<?php echo $liga->sid;?>&amp;liga=<?php echo $liga->liga; ?>&amp;runde=<?php echo $liga->runde; ?>&amp;tln=<?php echo $liga->tln_nr; ?>&amp;paar=<?php echo $liga->paar; ?>&dg=<?php echo $liga->dg; ?>&amp;Itemid=<?php echo $itemid; ?>">
				<?php echo $liga->rname; 
				?>
			</a>
			<br>
		<?php }}}

	echo $pane->endPanel();
	if ($conf_meldeliste == 1) {
	if ($meldeliste) {
		$today = date("Y-m-d"); 
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
			if (!isset($meldelistt_params['deadline_roster']))  {   //Standardbelegung
				$meldelistt_params['deadline_roster'] = '0000-00-00'; }
			if ($meldelistt_params['deadline_roster'] >= $today) { $t_meldeliste = 1; } 
			}
		}
		if ($t_meldeliste == 1) {
 
	echo $pane->startPanel( 'Meldeliste abgeben', 'panel2' );

		foreach ($meldeliste as $meldeliste){ 
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
			if ($s_meldeliste == 1) { ?>
		<div>
			<a href="index.php?option=com_clm&view=meldeliste&saison=<?php echo $meldeliste->sid; ?>&zps=<?php echo $meldeliste->zps; ?>&lid=<?php echo $meldeliste->lid; ?>&man=<?php echo $meldeliste->man_nr; ?>&amp;Itemid=<?php echo $itemid; ?>"><?php echo $meldeliste->name; ?></a> - <?php echo $meldeliste->liganame; ?> 
		</div>
		<?php } }

	echo $pane->endPanel();
	}}
	if ($rangliste) {
	echo $pane->startPanel( 'Rangliste abgeben', 'panel3' );

		foreach ($rangliste as $rangliste){
		if ($rangliste->id == "") { ?>
		<div>
			<a href="index.php?option=com_clm&view=meldeliste&layout=rangliste&saison=<?php echo $rangliste->sid; ?>&zps=<?php echo $rangliste->zps; ?>&gid=<?php echo $rangliste->gid; ?>&amp;Itemid=<?php echo $itemid; ?>"><?php echo $rangliste->gruppe; ?></a> 
		</div>
		<?php }}
	
	echo $pane->endPanel();
	}}
	
	if ($conf_vereinsdaten == 1 AND $par_vereinsdaten == 1) {
	echo $pane->startPanel( 'Vereinsdaten ändern', 'panel4' );
	?>
		<div>
		<a href="index.php?option=com_clm&view=verein&saison=<?php echo $data[0]->sid; ?>&zps=<?php echo $data[0]->zps; ?>&layout=vereinsdaten&amp;Itemid=<?php echo $itemid; ?>"><?php echo $data[0]->vname; ?></a>
		</div>
		<?php 

	echo $pane->endPanel();
	}

echo $pane->endPane();

} ?>
