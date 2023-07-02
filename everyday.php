<html>
 <head>
	<meta http-equiv="Content-Type" content="text/html;charset=utf-8" >
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="Keywords" content="профитомер, вайлдбериз, вайлдберриз партнеры, статистика вайлдберриз, wildberries аналитика, аналитика вайлдберриз бесплатно, сервис аналитики wildberries, аналитика продаж, аналитика вб">
	<meta name="Description" content="Сервис, помогающий партнерам вайлдберриз подсчитывать прибыль с продаж, смотреть аналитику по периодам.">	
	<title>Статискика wb - подтверждение email.</title>
	<link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">

	<link rel="stylesheet" type="text/css" href="w3.css"/>
	<link rel="stylesheet" type="text/css" href="w3-colors-2021.css"/>
	<link rel="stylesheet" type="text/css" href="style.css"/>
 </head>
	<body class="w3-container w3-auto">


<?php

require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';

date_default_timezone_set('Europe/Moscow');

$todayDate = new DateTime();

echo("today: ".$todayDate->format('Y-m-d H:i:s'));


// Find email
$stmt = $aDb->prepare("SELECT id, blocking_date FROM accounts WHERE active>0;");
if(!$stmt->execute())
{
	sendMessageToTelegramChat("Ошибка бд. ".$stmt->errorInfo()[2].' line '.__LINE__);
	return;
}
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row)
{
	$blockingDate = new DateTime(substr($row['blocking_date'], 0, 10));
	if($blockingDate < $todayDate)
	{
		echo("blocking date less: ".$blockingDate->format('Y-m-d H:i:s'));
	}
}

function sendMessageToTelegramChat($text)
{
	global $telegramChatID, $telegramBotToken;

	$data = [
		'text' => $text,
		'chat_id' => $telegramChatID
	];
	file_get_contents("https://api.telegram.org/bot".$telegramBotToken."/sendMessage?" . http_build_query($data));
}

?>

	</body>
</html>
