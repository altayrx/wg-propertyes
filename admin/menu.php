<?php
IncludeModuleLangFile(__FILE__);

if($APPLICATION->GetGroupRight("form")>"D") {
	$aMenu = array(
		"parent_menu" => "global_menu_services",
		"sort"        => 1,
		"url"         => "wg.php",
		"text"        => GetMessage("MENU_MAIN"),
		"title"       => GetMessage("MENU_MAIN_TITLE"),
		"icon"        => "wg-admin-icon",
		"page_icon"   => "wg-admin-page-icon",
		"items_id"    => "wg",
		"items"       => array()
	);

	$aMenu["items"][] = array(
		"text" => GetMessage("MENU_EXPORT_IMPORT"),
		"url"  => "wg_exchange.php",
		"icon" => "wg-exchange-icon",
		"page_icon" => "wg-exchange-page-icon"
	);

	$aMenu["items"][] = array(
		"text" => GetMessage("MENU_EMPTY_EXTRA"),
		"url"  => "wg_empty_extra.php",
		"icon" => "search_menu_icon",
		"page_icon" => "wg-exchange-page-icon"
	);

	$aMenu["items"][] = array(
		"text" => GetMessage("MENU_SELECTIONS"),
		"url"  => "wg_selections.php",
		"icon" => "search_menu_icon",
		"page_icon" => "wg-exchange-page-icon"
	);

	/*$aMenu["items"][] =  array(
		"text" => GetMessage("MENU_EXPORT"),
		"url"  => "wg_export.php",
		"icon" => "wg-export-icon",
		"page_icon" => "wg-export-page-icon"
	);

	$aMenu["items"][] =  array(
		"text" => GetMessage("MENU_IMPORT"),
		"url"  => "wg_import.php",
		"icon" => "wg-import-icon",
		"page_icon" => "wg-import-page-icon"
	);*/

	return $aMenu;
}

return false;
