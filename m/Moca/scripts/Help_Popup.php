<?php
/**
  * File: Help_Popup_php
  *
  * Description: The Help popup template
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */

$head = $_COOKIE['smwi_help_head'];
$body = $_COOKIE['smwi_help_body'];

echo("<html>
	<head>
		<title>".$head."</title>
	</head>
	<body>
		<p style='font-size: 15px;font-family: arial;font-weight: bold;text-align: center;'>");
echo($head);
echo("		</p>
		<p style='font-size: 12px;font-family: arial;font-weight: normal;'>");
echo($body); 
echo("		</p>
		<hr />
		<p style='font-size: 13px;font-family: arial;font-weight: bold;text-align:center;cursor: pointer;' onclick='window.close()'>Close this window</p>

	</body>
</html>");
	
?>