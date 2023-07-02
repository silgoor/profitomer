<?php
require __DIR__.'/config.php';
?>

<html>
 <head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="Keywords" content="профитомер, вайлдбериз, вайлдберриз партнеры, статистика вайлдберриз, wildberries аналитика, аналитика вайлдберриз бесплатно, сервис аналитики wildberries, аналитика продаж, аналитика вб">
	<meta name="Description" content="Сервис, помогающий партнерам вайлдберриз подсчитывать прибыль с продаж, смотреть аналитику по периодам.">	
	<title>Статистика wb - личный кабинет.</title>
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">

	<link rel="stylesheet" type="text/css" href="w3.css"/>
	<link rel="stylesheet" type="text/css" href="w3-colors-2021.css"/>
	<link rel="stylesheet" type="text/css" href="style.css"/>
	<script src="lk-ajax.js?v=24"></script>
 </head>
 <body class="w3-container w3-auto">
	<div id="contentTitle" style="display: inline-block; margin-bottom: 15px;">
		<a href="/"><img src="favicon-32x32.png" style="display:inline-block;"></a>
		<button id="mainMenu-dashboard" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=dashboard')">Аналитика</button>
		<button id="mainMenu-orders" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=orders')">Заказы</button>
		<button id="mainMenu-sales" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=sales')">Выкупы</button>
		<button id="mainMenu-stock" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=stock')">Остатки</button>
		<button id="mainMenu-supplies" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=supplies')">Поставки</button>
		<button id="mainMenu-expenses" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=expenses')">Расходы</button>
		<button id="mainMenu-load" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=load')">Загрузить данные</button>
		<button id="mainMenu-profile" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=profile')">Профиль</button>
		<button id="mainMenu-support" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=support')">Помощь</button>
		<button id="mainMenu-about" class="w3-button mainMenu" onclick="loadPageWithHistoryPush('?t=about')">О сервисе</button>
	</div>
	<div id="content" class="w3-container">
	</div>
  <div id='debug'></div>
  </div>
 </body>
</html>
