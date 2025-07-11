<?php
/**
 * @ Chess League Manager (CLM) Login Modul 
 * @Copyright (C) 2008-2025 CLM Team.  All rights reserved
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.chessleaguemanager.de
*/

// no direct access
defined('_JEXEC') or die('Restricted access');

// Konfiguration wird benötigt
require_once (JPATH_SITE . DIRECTORY_SEPARATOR . "components" . DIRECTORY_SEPARATOR . "com_clm" . DIRECTORY_SEPARATOR . "clm" . DIRECTORY_SEPARATOR . "index.php");

$user = JFactory::getUser();

// angemeldet
if ($user->get('id')) {
require_once (dirname(__FILE__).DS.'helper.php');

$par_vereinsdaten  = $params->def('vereinsdaten', 1);
$altItemid	= $params->def('altItemid');

$data		= modCLM_LogHelper::getData($params);
$liga		= modCLM_LogHelper::getLiga($params);
$liga_sl	= modCLM_LogHelper::getLiga_SL($params);
$liga_ar	= modCLM_LogHelper::getLiga_AR($params);
$meldeliste	= modCLM_LogHelper::getMeldeliste($params);
$rangliste	= modCLM_LogHelper::getRangliste($params);

require(JModuleHelper::getLayoutPath('mod_clm_log'));
}
// NICHT angemeldet
else 
{ echo "<p>".JText::_('MOD_CLM_LOG_INFO')."</p>";}
