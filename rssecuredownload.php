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
else 
{
	header("HTTP/1.1 403 Forbidden");
}
*/
?>