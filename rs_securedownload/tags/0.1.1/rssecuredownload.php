<?php

session_start();
$file = $_SESSION['tx_rssecuredownload_pi1']['file'];
$title = $_SESSION['tx_rssecuredownload_pi1']['title'];

$size = filesize($file);

if ($file != '') {
	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename=" . $title);
	header("Content-Length: ".$size);
	header("Pragma: no-cache");
	header("Expires: 0");
	readfile($file);
}
/*
else 
{
	$server_name = 'http://'.$_SERVER['SERVER_NAME'];
	header("HTTP/1.1 403 Forbidden");
	print '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">'."\n";
	print '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">'."\n";
	print '<head>'."\n";
	print '  <meta http-equiv="refresh" CONTENT="100;URL='.$server_name.'">'."\n";
	print '<title>Seite nicht gefunden!</title>'."\n";
	print '</head>'."\n";
	print '<body style="font-family: Verdana, Geneva, Arial, Helvetica, sans-serif;text-align:center;";>'."\n";
	print '  Die Seite wurde nicht gefunden. <br />Wenn Sie nicht automatisch weitergeleitet werden, klicken Sie hier: <br /><br /><a href='.$server_name.'>'.$server_name.'</a>'."\n";
	print '</body>'."\n";
	print '</html>';
}
*/
?>