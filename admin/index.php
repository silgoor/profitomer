<?php
require __DIR__.'/../config.php';
?>

<html>
 <head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Profitomer.</title>
	<link rel="manifest" href="/site.webmanifest">

	<link rel="stylesheet" type="text/css" href="../w3.css"/>
	<link rel="stylesheet" type="text/css" href="../w3-colors-2021.css"/>
	<link rel="stylesheet" type="text/css" href="../style.css"/>
	<script src="aajax.js?v=4"></script>
 </head>
 <body class="w3-container w3-auto">
	<div id="contentTitle" style="display: inline-block; margin-bottom: 15px;">
		<img src="../favicon-32x32.png" style="display:inline-block;">
		<button id="mainMenu-accounts" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=accounts')">Аккаунты</button>
		<button id="mainMenu-accounts" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=newaccounts')">Не активированные</button>
	</div>
	<div id="content" class="w3-container">
	</div>
  <div id='debug'></div>
  </div>
 </body>
</html>
