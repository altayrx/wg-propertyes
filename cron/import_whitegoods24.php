<?php
$running = exec('/bin/ps -A -w -o command |/usr/bin/grep ' . basename(__FILE__) . '|/usr/bin/grep -v grep|/usr/bin/wc -l');

//echo $running . "\n";

if($running > 1) {
	//echo $running . "\n";
	exit;
} else {
	//echo "basename" . basename(__FILE__) . "\n";
	//echo "nr\n";
}
chdir(dirname(__FILE__));
file_put_contents('running.log', $running . PHP_EOL, FILE_APPEND);
$_SERVER["DOCUMENT_ROOT"] = preg_replace("/(.*data24).*/", "$1", dirname(__FILE__));
require_once(dirname(__FILE__) . '/../exchange/import.php');
