<?php
Class wg extends CModule
{
	var $MODULE_ID = "wg";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_CSS;

	function wg()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");
		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		$this->MODULE_NAME = "Модуль WG";
		$this->MODULE_DESCRIPTION = "Модуль WG";
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		// Install events
		RegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "wg", "CWG", "onBeforeElementUpdateHandler");
		RegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile("Установка модуля wg", $DOCUMENT_ROOT."/local/modules/wg/install/step.php");
		return true;
	}

	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		UnRegisterModuleDependences("iblock", "OnAfterIBlockElementUpdate", "wg", "CWG", "onBeforeElementUpdateHandler");
		UnRegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile("Деинсталляция модуля wg", $DOCUMENT_ROOT."/local/modules/wg/install/unstep.php");
		return true;
	}
}
