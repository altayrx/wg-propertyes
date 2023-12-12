<?php
CModule::IncludeModule("wg");
global $DBType;

$arClasses = array(
	'Accusative' => 'classes/general/accusative.php',
	'CWG' => 'classes/general/wg.php',
	'CWGEP' => 'classes/general/wgextraprops.php',
	'CWGExchange' => 'classes/general/wgexchange.php',
	'Dellin' => 'classes/general/dellin.php',
	'CWGLK' => 'classes/general/lk.php',
	'CWGUser' => 'classes/general/user.php',
	'CWGFav' => 'classes/general/fav.php',
	'CWGGift' => 'classes/general/wggift.php',
);

CModule::AddAutoloadClasses("wg", $arClasses);
