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
// Usage: email_confirm.php?email=my@mail.com&activatecode=45389jfg320dk
// Script willi find the entry in 'new_accounts' table, and move it to 'accounts' table.

require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/send_email.php';
require_once __DIR__.'/random_str.php';

date_default_timezone_set('Europe/Moscow');

if(!array_key_exists("email",$_GET) || !array_key_exists("activatecode",$_GET))
{
	echo("<h4>Ошибка активации. Попробуйте повторить позже.</h4>");
	return;
}

// Find email
$stmt = $aDb->prepare("SELECT id, email, telegram, phone, surname, name, secondname, activate_code, password, tariff_id FROM new_accounts WHERE email=:email;");
$stmt->bindValue(':email', $_GET['email'], PDO::PARAM_STR);

if(!$stmt->execute())
{
	return error("На сервере произошла ошибка.","Ошибка бд при поиске аккаунта для активации. ".$stmt->errorInfo()[2].' line '.__LINE__);
}
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
//print_r($results);
if(!count($results))
{
	return error("Email не найден.");
}
$row=$results[0];
$activatingAccountId=$row['id'];
if($row['activate_code'] != $_GET['activatecode'])
{
	return error("Неверный код активации.");
}

//echo('id: '.$row['id'].' email: '.$row['email'].' phone: '.$row['phone']);

// ACTIVATE

// Copy to 'accounts'.
$stmt = $aDb->prepare("INSERT INTO accounts(active, email, telegram, phone, surname, name, secondname, password, tariff_id, blocking_date, balance) VALUES (1, :email, :telegram, :phone, :surname, :name, :secondname, :password, :tariff_id, :blocking_date, 0);");
$stmt->bindValue(':email', $row['email'], PDO::PARAM_STR);
$stmt->bindValue(':telegram', $row['telegram'], PDO::PARAM_STR);
$stmt->bindValue(':phone', $row['phone'], PDO::PARAM_STR);
$stmt->bindValue(':surname', $row['surname'], PDO::PARAM_STR);
$stmt->bindValue(':name', $row['name'], PDO::PARAM_STR);
$stmt->bindValue(':secondname', $row['secondname'], PDO::PARAM_STR);
$stmt->bindValue(':password', $row['password'], PDO::PARAM_STR);
$stmt->bindValue(':tariff_id', $row['tariff_id'], PDO::PARAM_INT);
$blockingDate = new DateTime();
$blockingDate->modify('+'.$trialPeriodDays.' days');
$stmt->bindValue(':blocking_date', $blockingDate->format("Y-m-d H:i:s.00"), PDO::PARAM_STR);
if(!$stmt->execute())
{
	return error("На сервере произошла ошибка.","Ошибка бд при активации аккаунта. ".$stmt->errorInfo()[2].' line '.__LINE__);
}

// Delete from 'new_accounts'.
$stmt = $aDb->prepare("DELETE FROM new_accounts WHERE id=:id;");
$stmt->bindValue(':id', $activatingAccountId, PDO::PARAM_INT);
if(!$stmt->execute())
{
	return error("На сервере произошла ошибка.","Ошибка бд при активации аккаунта. ".$stmt->errorInfo()[2].' line '.__LINE__);
}

echo('<h3>Аккаунт успешно активирован.</h3><p>Логин и пароль находятся в письме. Сохраните их.</p><p>Для начала работы необходимо в разделе &quot;Профиль&quot; добавить токен wildberries.</p><p>Вам доступен бесплатный тестовый период 3 дня.</p>');






function error($messageForUser,$messageForAdmin='')
{
	if(strlen($messageForAdmin))
	{
		sendMessageToTelegramChat($messageForAdmin);
	}
	echo($messageForUser);
}

?>
		<p><button class="w3-button w3-padding-large w3-black w3-block" onclick="window.open('https://profitomer.ru/?t=editWbToken')">Перейти в сервис profitomer.ru</button></p>
	</body>
</html>

