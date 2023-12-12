<?
$testInput = json_decode(file_get_contents('php://input'), 1);
if (isset($testInput['queue'])) {
	define("NO_KEEP_STATISTIC", true);
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

	if (!\Bitrix\Main\Loader::includeModule('main')) {
		ShowError('Модуль main не установлен');
		return;
	}

	if (!\Bitrix\Main\Loader::includeModule('wg')) {
		ShowError('Модуль wg не установлен');
		return;
	}
	CWGExchange::cancelQueue($testInput['queue']);
	die(json_encode(['action' => 'reload']));
}

if (isset($testInput['test'])) {
	die(json_encode(['action' => 'reload']));
}

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php");


CJSCore::RegisterExt('wg', array(
	'css' => '/local/admin/wg.css',
));
CJSCore::Init(array("wg"));

function execScript($url, $params = array()) {
	$parts = parse_url($url);
	$returned_data = '';
	$data = http_build_query($params, '', '&');
	$fp = @fsockopen('ssl://'.$parts['host'], 443, $errnum, $errstr, 30);
	if ($fp) {
		fputs($fp, "POST ".$parts['path']." HTTP/1.1\r\n");
		fputs($fp, "Host: ".$parts['host']."\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data."\r\n\r\n");
		stream_set_timeout($fp, 1000);
		stream_set_blocking($fp, false);

		while (!feof($fp)) {
			$returned_data .= fgets($fp, 4096);
		}
		fclose($fp);
	}
	ob_end_clean();
	header("Content-type: application/vnd.ms-excel");
	if (isset($params['export_section'])) {
		$params['export_section'] .= 's';
	} else {
		$params['export_section'] = '';
	}
	if (isset($params['export_manufacturer'])) {
		$params['export_manufacturer'] .= 'm';
	} else {
		$params['export_manufacturer'] = '';
	}
	header('Content-disposition: attachment; filename=pricelist('.date('d-m-Y').')'.$params['export_section'].$params['export_manufacturer'].'.xls');
	list($out_header, $out_body, $out_tail) = explode("OUTSEPARATOR", $returned_data, 3);
	$out_body = preg_replace("/\x0D\x0A([0-9a-fA-F])*\x0D\x0A/", "", $out_body);
	echo $out_body;
	die();
}

if (isset($_POST['tabControl_active_tab'])) {
	if (isset($_FILES['file_import']['tmp_name'])) {
		$uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/upload/import/';
		$uploadfile = $uploaddir . basename($_FILES['file_import']['name']);
		if (move_uploaded_file($_FILES['file_import']['tmp_name'], $uploadfile)) {
			$_POST['file_import_uploaded'] = $uploadfile;
		}
	}
	if (isset($_FILES['file_price_import']['tmp_name'])) {
		$uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/upload/import/';
		$uploadfile = $uploaddir . basename($_FILES['file_price_import']['name']);
		if (move_uploaded_file($_FILES['file_price_import']['tmp_name'], $uploadfile)) {
			$_POST['file_price_import_uploaded'] = $uploadfile;
		}
	}
	if (isset($_FILES['file_provider_import']['tmp_name'])) {
		$uploaddir = $_SERVER['DOCUMENT_ROOT'] . '/upload/import/';
		$uploadfile = $uploaddir . basename($_FILES['file_provider_import']['name']);
		if (move_uploaded_file($_FILES['file_provider_import']['tmp_name'], $uploadfile)) {
			$_POST['file_provider_import_uploaded'] = $uploadfile;
		}
	}
	$action = "action".$_POST['tabControl_active_tab'];
	if (method_exists('CWGExchange', $action)) {
		CWGExchange::$action($_POST);
	}
}
?><form method="post" action="" enctype="multipart/form-data">
<?
$aTabs = array(
	array("DIV" => "export", "TAB" => GetMessage("TAB_EXPORT"), "ICON" => "wg", "TITLE" => GetMessage("TAB_EXPORT")),
	array("DIV" => "import", "TAB" => GetMessage("TAB_IMPORT"), "ICON" => "wg", "TITLE" => GetMessage("TAB_IMPORT")),
	array("DIV" => "prices", "TAB" => GetMessage("TAB_PRICES"), "ICON" => "wg", "TITLE" => GetMessage("TAB_PRICES")),
	array("DIV" => "providers", "TAB" => GetMessage("TAB_PROVIDERS"), "ICON" => "wg", "TITLE" => GetMessage("TAB_PROVIDERS"))
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();

$tabControl->BeginNextTab();
?>
<tr class="adm-detail-required-field">
	<td width="40%"><?=GetMessage("EXPORT_SECTIONS")?>:</td>
	<td width="60%"><?
$res = CIBlockSection::GetList(
	Array('LEFT_MARGIN' => 'ASC'),
	Array('IBLOCK_ID' => 17),
	false,
	Array('IBLOCK_ID', 'ID', 'NAME', 'CODE', 'DEPTH_LEVEL')
);
if ($res->SelectedRowsCount() > 0) {
?><select name="export_section"><option value="0"><?=GetMessage('FULL_LIST')?></option><?
	while ($ob = $res->GetNext()) {
		?><option value="<?=$ob['CODE']?>"><?echo str_repeat(". . ", $ob['DEPTH_LEVEL'] - 1);?><?=$ob['NAME']?></option><?
	}
?></select><?
}
	?></td>
</tr>
<tr>
	<td><?=GetMessage("EXPORT_MANUFACTURERS")?></td>
	<td><?
$res = CIBlockElement::GetList(
	Array('NAME' => 'ASC'),
	Array('IBLOCK_ID' => 18),
	false,
	false,
	Array('IBLOCK_ID', 'ID', 'NAME')
);
if ($res->SelectedRowsCount() > 0) {
?><select name="export_manufacturer"><option value="0"><?=GetMessage('ALL_MANUFACTURERS')?></option><?
	while ($ob = $res->GetNext()) {
		?><option value="<?=$ob['ID']?>"><?=$ob['NAME']?></option><?
	}
?></select><?
}
	?></td>
</tr>
<tr>
	<td></td>
	<td><button id="button_download"><?=GetMessage("BUTTON_DOWNLOAD")?></button></td>
</tr>
<?

$tabControl->BeginNextTab();

$message = new CAdminMessage([
	'MESSAGE' => GetMessage('ALERT_IMPORT'),
	'TYPE' => 'OK',
	'HTML' => true
]);
echo $message->Show();

?>
<tr class="adm-detail-required-field">
	<td width="40%"><?=GetMessage("IMPORT_PROPS")?>:</td>
	<td width="60%">
<?
$import_fields = Array(
	"1" => "Цена",
	"2" => "Показывать на сайте",
	"3" => "Показывать на маркете",
	"4" => "Статус наличия",
	"5" => "Остаток на складе",
	"6" => "Цена закупки",
	"7" => "Поставщик",
	"8" => "Самовывоз"
);
foreach ($import_fields as $i => $import_field) {
?>
		<p><label><input name="import_field[]" value="<?=$i?>" <?if (in_array($i, $_POST['import_field'])) {?> checked='checked'<?}?>type="checkbox" /> <?=$import_field?></label></p>
<?}?>
	</td>
</tr>
<tr>
	<td></td>
	<td><input type="file" name="file_import" /></td>
</tr>
<tr>
	<td></td>
	<td><button><?=GetMessage("BUTTON_UPLOAD")?></button></td>
</tr>
<tr>
	<td>Очередь</td>
	<td><?=CWGExchange::queueAdminTableImportImport()?></td>
</tr>
<?

$tabControl->BeginNextTab();
$message = new CAdminMessage([
	'MESSAGE' => GetMessage('ALERT_IMPORT'),
	'TYPE' => 'OK',
	'HTML' => true
]);
echo $message->Show();

?>
<tr class="adm-detail-required-field">
	<td width="40%"><?=GetMessage("IMPORT_PROPS")?>:</td>
	<td width="60%">
<?
$price_import_fields = Array(
	"1" => "Цена",
	"2" => "Показывать на сайте",
	"3" => "Показывать на маркете",
	"4" => "Статус наличия",
	"5" => "Остаток на складе",
	"6" => "Цена закупки",
	"7" => "Поставщик"
);
foreach ($price_import_fields as $i => $price_import_field) {
?>
		<p><label><input name="price_import_field[]" value="<?=$i?>" <?if (in_array($i, $_POST['price_import_field'])) {?> checked='checked'<?}?>type="checkbox" /> <?=$price_import_field?></label></p>
<?}?>
	</td>
</tr>
<tr>
	<td></td>
	<td><input type="file" name="file_price_import" /></td>
</tr>
<tr>
	<td></td>
	<td><button><?=GetMessage("BUTTON_UPLOAD")?></button></td>
</tr>
<tr>
	<td>Очередь</td>
	<td><?=CWGExchange::queueAdminTableImportPrice()?></td>
</tr>
<?

$tabControl->BeginNextTab();

?>
<tr>
	<td></td>
	<td><input type="file" name="file_provider_import" /></td>
</tr>
<tr>
	<td></td>
	<td><button><?=GetMessage("BUTTON_UPLOAD")?></button></td>
</tr>
<tr>
	<td>Очередь</td>
	<td><?=CWGExchange::queueAdminTableImportProvider()?></td>
</tr>
<?

$tabControl->End();
$message = new CAdminMessage([
	'MESSAGE' => '* Внимание!',
	'TYPE' => 'OK',
	'DETAILS' => GetMessage('ALERT_COMMON'),
	'HTML' => true
]);
echo $message->Show();
?></form>
<script>
console.log(['exchange', 'script']);
</script>
<?
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
