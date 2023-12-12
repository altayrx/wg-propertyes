<?php

//$running = exec('/bin/ps ax |/usr/bin/grep  http |/usr/bin/grep -v grep|/usr/bin/wc -l');
//$running = exec('pgrep -S -f importwhitegoods|wc -l');
//$running = exec('ps -A | grep -i \'importwhitegoods\' | grep -v grep|wc -l');

$running = exec('/bin/ps -A -w -o command |/usr/bin/grep ' . basename(__FILE__) . '|/usr/bin/grep -v grep|/usr/bin/wc -l');

$running2 = exec('ps -A | grep -i \'importwhitegoods\' | grep -v grep');
$running3 = exec('ps -A > /usr/local/www/whitegoods.ru/data/local/modules/wg/cron/ps.txt');


//echo $running . "\n";

if($running > 1) {
//   echo $running . "\n";
//   exit;
}


chdir(dirname(__FILE__));

file_put_contents('running2.log', $running . PHP_EOL, FILE_APPEND);

file_put_contents('running3.log', $running2 . PHP_EOL, FILE_APPEND);



