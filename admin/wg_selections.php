<?php
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php";
IncludeModuleLangFile(__FILE__);
require_once $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php";

CJSCore::RegisterExt('wg', array(
	'js' => '/js/js.cookie.js',
	'css' => '/local/admin/wg.css',
));
CJSCore::Init(array("wg"));

?><form method="post" action="" enctype="multipart/form-data">
<?
$onPageAr = Array(5, 10, 20, 50, 100, 200, 500);
$onPage = 50;
if (!empty($_COOKIE['WG_SELECTIONS_ON_PAGE'])) {
	$onPage = $_COOKIE['WG_SELECTIONS_ON_PAGE'];
}
$aTabs = array(
	array("DIV" => "selections", "TAB" => GetMessage("TAB_SELECTIONS"), "ICON" => "wg", "TITLE" => GetMessage("TAB_SELECTIONS")),
	array("DIV" => "hide_selections", "TAB" => GetMessage("TAB_HIDE_SELECTIONS"), "ICON" => "wg", "TITLE" => GetMessage("TAB_HIDE_SELECTIONS"))
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$tabControl->Begin();

$tabControl->BeginNextTab();
?>
<tr class="adm-detail-required-field">
	<td width="40%"></td>
	<td width="60%"></td>
</tr>
<?
$page = 1;
if (!empty($_REQUEST['page'])) {
	$page = $_REQUEST['page'];
}
$dbh = new PDO('mysql:host=localhost;dbname=whitegoods24_c1;charset=utf8', 'whitegoods24_c1', 'GOKsdhkstb16');
$sql = "SELECT
  count(s.name) AS count
FROM selections AS s

LEFT JOIN b_iblock_section AS bs ON bs.ID=s.section_id
WHERE s.type = 'selection'
";
$sth = $dbh->prepare($sql);
$sth->execute();
$count = $sth->fetchAll()[0]['count'];

/*$sql = "SELECT
  s.name AS name,
  s.list AS list,
  bs.ID AS id
FROM selections AS s

LEFT JOIN b_iblock_section AS bs ON bs.ID=s.section_id
WHERE s.type = 'selection'
LIMIT ? OFFSET ?";*/

$sql = "SELECT 
  s.name AS name,
  s.list AS list,
  bs.ID AS id,
  CONCAT_WS('', ss.list) AS ssc
FROM selections AS s

LEFT JOIN b_iblock_section AS bs ON bs.ID=s.section_id
LEFT JOIN selections AS ss ON ss.id IN (REPLACE(s.list, '|', ','))
WHERE s.type = 'selection' AND ss.type='property'
GROUP BY s.name, s.list, bs.ID
ORDER BY ssc DESC, bs.ID
LIMIT ? OFFSET ?";

$sth = $dbh->prepare($sql);
$sth->bindValue(1, $onPage, PDO::PARAM_INT);
$sth->bindValue(2, ($page - 1) * $onPage, PDO::PARAM_INT);
$sth->execute();
$res = $sth->fetchAll();
?>
<tr><td style='vertical-align: top'>
<table class='adm-wg-table'>
<tr>
	<th><?=GetMessage("SELECTION_NAME")?></th>
	<th><?=GetMessage("SELECTION_COUNT")?></th>
	<th><?=GetMessage("SELECTION_LINK")?></th>
</tr>
<?foreach ($res as $ob) {?>
<tr>
<td style='text-align: left;'>
	<?=$ob['name']?>
</td>
<td>
<?
	$selection_count = 0;
	//$selection_count_res = $dbh->prepare('SELECT * FROM selections WHERE type="property" AND section_id=' . $ob['section_id'] . ' AND id IN (' . str_replace('|', ',', $ob['list']) . ')');
	$selection_count_res = $dbh->prepare('SELECT * FROM selections WHERE type="property" AND id IN (' . str_replace('|', ',', $ob['list']) . ')');
	$selection_count_res->execute();
	while ($selection_count_ob = $selection_count_res->fetch()) {
		if (!empty($selection_count_ob['list'])) {
			$selection_count += count(explode('|', $selection_count_ob['list']));
		}
	}
	echo $selection_count;
?>
</td>
<td>
	<a href='/bitrix/admin/iblock_section_edit.php?IBLOCK_ID=17&type=SHOP&ID=<?=$ob['id']?>&lang=ru&find_section_section=<?=$ob['id']?>&form_section_1_active_tab=WGSELECTIONS_wgselectionsedit' target='_blank'>
		<?=$ob['id']?>
	</a>
</td>
</tr>
<?}
$count = intval($count / $onPage);
?>
<tr><td colspan="3">
<?
$cur_page = 0;
while ($cur_page < $count) {?>
<a
	data-cp="<?=$page?>"
	style="
		text-decoration: none;
		display: inline-block;
		padding: 5px 9px;
		margin: 0 5px 5px 0;
		border: 1px solid #5aa1cb;
		border-radius: 8px;
		<?=((($cur_page + 1) == $page) ? ' background: #5aa1cb; color: #fff;' :'')?>"
	href="<?=($cur_page > 0 ? '?page=' . ($cur_page + 1) : '/bitrix/admin/wg_selections.php')?>
	">
	<?=($cur_page + 1)?>
</a>
<?
	$cur_page++;
}?>
<div style='display: inline-block; white-space: nowrap'>
<?=GetMessage("ON_PAGE")?>
<select class='change-wg-selections-onpage'>
<?foreach($onPageAr as $onPageItem) {?>
	<option value='<?=$onPageItem?>'<?if ($onPageItem == $onPage) {?> selected='selected'<?}?>><?=$onPageItem?></option>
<?}?>
</select>
</div>
</td></tr>
</table>
</td></tr>
<?
$tabControl->BeginNextTab();
?><tr><td>
<?
$count_sql = 'SELECT COUNT(ID) AS count FROM b_iblock_section WHERE IBLOCK_ID=17';
$count_res = $dbh->prepare($count_sql);
$count_res->execute();
$count_s = $count_res->fetch()['count'] / $onPage;
?>

<table class='adm-wg-table'>
<tr>
	<th><?=GetMessage("HIDE_NAME")?></th>
	<th><?=GetMessage("HIDE_COUNT")?></th>
	<th><?=GetMessage("HIDE_HIDE")?></th>
</tr>
<?
$spage = 1;
if (!empty($_REQUEST['spage'])) {
	$spage = $_REQUEST['spage'];
}
/*$s_sql = '
SELECT s.ID AS ID, s.NAME AS NAME, u.UF_HIDE_SELECTIONS AS HIDE FROM b_iblock_section AS s
LEFT JOIN b_uts_iblock_1_section AS u ON s.ID=u.VALUE_ID
WHERE s.IBLOCK_ID=17 LIMIT ? OFFSET ?';*/
$s_sql = '
SELECT
  s.ID AS ID,
  s.CODE AS CODE,
  s.DEPTH_LEVEL AS DEPTH,
  s.NAME AS NAME,
  u.UF_HIDE_SELECTIONS AS HIDE,
  COUNT(e.id) AS count
FROM b_iblock_section AS s
LEFT JOIN b_uts_iblock_1_section AS u ON s.ID=u.VALUE_ID
LEFT JOIN selections AS e ON s.ID=e.section_id AND e.type="selection"
WHERE
  s.IBLOCK_ID=17
GROUP BY
  s.ID,
  s.NAME,
  u.UF_HIDE_SELECTIONS
ORDER BY s.LEFT_MARGIN, count DESC
LIMIT ? OFFSET ?';
$s_res = $dbh->prepare($s_sql);
$s_res->bindValue(1, $onPage, PDO::PARAM_INT);
$s_res->bindValue(2, ($spage - 1) * $onPage, PDO::PARAM_INT);
$s_res->execute();
while ($s_ob = $s_res->fetch()) {
	$s_sql2 = 'SELECT COUNT(id) AS count FROM selections WHERE type="selection" AND section_id=' . $s_ob['ID'];
	$s_res2 = $dbh->query($s_sql2);
	$s_ob2 = $s_res2->fetch()['count'];
?><tr>
	<td style='text-align: left;'>
		<?if ($s_ob['DEPTH'] == 1) {?><b><?}?><?=str_repeat('&bull;&nbsp;', $s_ob['DEPTH'] - 1)?><?=$s_ob['NAME']?><?if ($s_ob['DEPTH'] == 1) {?></b><?}?>
		<a href='/bitrix/admin/iblock_section_edit.php?IBLOCK_ID=17&type=SHOP&ID=<?=$s_ob['ID']?>&lang=ru&find_section_section=<?=$s_ob['ID']?>&form_section_1_active_tab=WGSELECTIONS_wgselectionsedit' target='_blank'><?=GetMessage("LINK_ADMIN")?></a>
		<a href='/catalog/<?=$s_ob['CODE']?>.htm' target='_blank'><?=GetMessage("LINK_SITE")?></a>
	</td>
	<td>
		<?=$s_ob2?>
	</td>
	<td>
		<input class='cb_hide' type='checkbox'<?if ($s_ob['HIDE']) {?> checked='checked'<?}?> data-sid='<?=$s_ob['ID']?>'>
	</td>
</tr><?
}
?>
<tr><td colspan="3">
<?
$cur_spage = 0;
while ($cur_spage < $count_s) {?>
<a
	data-cp="<?=$spage?>"
	style="
		text-decoration: none;
		display: inline-block;
		padding: 5px 9px;
		margin: 0 5px 5px 0;
		border: 1px solid #5aa1cb;
		border-radius: 8px;
		<?=((($cur_spage + 1) == $spage) ? ' background: #5aa1cb; color: #fff;' :'')?>"
	href="<?=($cur_spage > 0 ? '?tabControl_active_tab=hide_selections&spage=' . ($cur_spage + 1) : '/bitrix/admin/wg_selections.php?tabControl_active_tab=hide_selections')?>
	">
	<?=($cur_spage + 1)?>
</a>
<?
	$cur_spage++;
}?>
<div style='display: inline-block; white-space: nowrap'>
<?=GetMessage("ON_PAGE")?>
<select class='change-wg-selections-onpage'>
<?foreach($onPageAr as $onPageItem) {?>
	<option value='<?=$onPageItem?>'<?if ($onPageItem == $onPage) {?> selected='selected'<?}?>><?=$onPageItem?></option>
<?}?>
</select>
</div>
</td></tr>
</table></td></tr><?
$tabControl->End();
/*$message = new CAdminMessage([
	'MESSAGE' => '* Внимание!',
	'TYPE' => 'OK',
	'DETAILS' => GetMessage('ALERT_COMMON'),
	'HTML' => true
]);
echo $message->Show();*/
?></form>
<?php
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");
