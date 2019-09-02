<?php
header('Content-Type: text/html; charset=utf-8');
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
?>
<table style="border:1px solid #69899F; border-collapse: collapse; table-layout: fixed; width:900px;">
 <thead>
  <tr>
	<th style="text-align:left" width='20%'>Key</th>
	<th style="text-align:left" width='80%'>Value</th>
   </tr>
 </thead>
 <tbody>
<?php
//Inpect a php session

try {
@session_id($_GET["name"]);
@session_start();
foreach ($_SESSION as $key => $value) {
	echo " <tr>
			<td style='border:1px dotted #000000;'>$key</td>
			<td style='border:1px dotted #000000; word-wrap:break-word;'>" . serialize($value) . "</td>
		</tr>";	
}
@session_abort();
} catch (Exception $e) {}
?>
 </tbody>
</table>
