<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php";
IncludeModuleLangFile(__FILE__);
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php";

CJSCore::RegisterExt('wg', array(
	'css' => '/local/admin/wg.css',
));
CJSCore::Init(array("wg"));

?><form method="post" action="" enctype="multipart/form-data">
<?
$aTabs = array(
	array("DIV" => "emptyextra", "TAB" => GetMessage("TAB_EMPTY_EXTRA"), "ICON" => "wg", "TITLE" => GetMessage("TAB_EMPTY_EXTRA"))
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();

$tabControl->BeginNextTab();
?>
<tr class="adm-detail-required-field">
	<td width="40%"><?=GetMessage("EXTRA_NAME")?>:</td>
	<td width="60%"><input type='text' name='extra_name' value='<?=$_REQUEST['extra_name']?>' /></td>
</tr>
<tr>
	<td></td>
	<td><button id="button_download"><?=GetMessage("BUTTON_SEARCH")?></button></td>
</tr>
<?
$dbh = new PDO('mysql:host=localhost;dbname=whitegoods24_c1;charset=utf8', 'whitegoods24_c1', 'GOKsdhkstb16');
if (!empty($_REQUEST['extra_name'])) {
$sql = "SELECT
  p.ID AS ID,
  p.CODE AS CODE,
  e.extra_fields_name AS PROP
FROM b_iblock_element AS p

LEFT JOIN products_to_extra_fields AS pe
ON pe.products_id=p.CODE

LEFT JOIN extra_fields AS e
ON e.extra_fields_id=pe.products_extra_fields_id

LEFT JOIN b_iblock_section AS s
ON p.IBLOCK_SECTION_ID=s.ID

WHERE
  p.IBLOCK_ID=17
  AND s.CODE=e.extra_fields_categories_id
  AND e.extra_fields_name LIKE ?
  AND (
    pe.products_extra_fields_value IS NULL
    OR pe.products_extra_fields_value='')
ORDER BY p.CODE";
$sth = $dbh->prepare($sql);
$sth->execute(Array('%' . $_REQUEST['extra_name'] . '%'));
$res = $sth->fetchAll();
}
?>
<tr><td style='vertical-align: top'>
<?if (!empty($_REQUEST['extra_name'])) {?>
<table class='adm-wg-table'>
<tr>
	<th><?=GetMessage("TABLE_HEAD_CODE")?></th>
	<th><?=GetMessage("TABLE_HEAD_EXTRA_NAME")?></th>
</tr>
<?foreach ($res as $ob) {?>
<tr>
<td>
	<a href='/bitrix/admin/iblock_element_edit.php?IBLOCK_ID=17&type=SHOP&ID=<?=$ob['ID']?>&lang=ru&find_section_section=-1&WF=Y&form_element_1_active_tab=WGELEMENTPROPS_wgelementedit' target='_blank'>
		<?=$ob['CODE']?>
	</a>
</td>
<td>
	<?=$ob['PROP']?>
</td>
</tr>
<?}?>
</table>
<?}?>
</td><td style='vertical-align: top'>
<div style='overflow: auto; max-height: 50vh'>
<table class='adm-wg-table'>
<?
$list = $dbh->query('
SELECT DISTINCT e.extra_fields_name AS PROP
FROM extra_fields AS e
ORDER BY e.extra_fields_name')->fetchAll();
foreach ($list as $listItem) {
?><tr><td><?=$listItem['PROP']?></td></tr><?
}
?>
</table>
</div>
</td></tr>
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
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
