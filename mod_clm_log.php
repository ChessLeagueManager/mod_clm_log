<?php

// no direct access
defined('_JEXEC') or die('Restricted access');

if(!defined("DS")){define('DS', DIRECTORY_SEPARATOR);} // fix for Joomla 3.2
$user = JFactory::getUser();

// angemeldet
if ($user->get('id')) {
require_once (dirname(__FILE__).DS.'helper.php');

$par_vereinsdaten  = $params->def('vereinsdaten', 1);
$altItemid	= $params->def('altItemid');

$data		= modCLM_LogHelper::getData($params);
$liga		= modCLM_LogHelper::getLiga($params);
$meldeliste	= modCLM_LogHelper::getMeldeliste($params);
$rangliste	= modCLM_LogHelper::getRangliste($params);

require(JModuleHelper::getLayoutPath('mod_clm_log'));
}
// NICHT angemeldet
else 
{ echo "Sie sind nicht angemeldet !";}
