<?php

require_once __DIR__.'/../config.php';
require_once __DIR__.'/../db.php';
require_once __DIR__.'/../send_email.php';
require_once __DIR__.'/../random_str.php';

date_default_timezone_set('Europe/Moscow'); // Чтобы неделя начиналась с понедельника.

$lastUrl='';

// Получаем json из post-запроса
header('Content-type: application/json');
$jsonRequest = file_get_contents('php://input');
$request = json_decode($jsonRequest, true);

$response = [];
$response["_id"]=$request["_id"];
$response["_function"]=$request["_function"];

function exitWithError($errorString,$errorNumber=99)
{
	global $response;
	$response["_errorNumber"]=$errorNumber;
	$response["_errorString"]=$errorString;
	exit(json_encode($response,JSON_UNESCAPED_UNICODE));
}

function exitWithErrorTg($adminErrorString, $errorString, $errorNumber=99)
{
	global $response;
	sendMessageToTelegramChat($adminErrorString);
	$response["_errorNumber"]=$errorNumber;
	$response["_errorString"]=$errorString;
	exit(json_encode($response,JSON_UNESCAPED_UNICODE));
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




function error($messageForUser,$messageForAdmin)
{
	sendMessageToTelegramChat($messageForAdmin);
	return $messageForUser;
}



// Check.

if(!array_key_exists("_function",$request))
{
	exitWithError("Не задана функция.",9);
}




if(!array_key_exists("_login",$request))
{
	exitWithError("Не задан логин.",2);
}
if(!array_key_exists("_password",$request))
{
	exitWithError("Не задан пароль.",3);
}

// Authentication
$stmt = $aDb->prepare("SELECT admins.id, admins.name FROM admins WHERE admins.login=:login AND admins.password=:password;");
$stmt->bindValue(":login", $request["_login"], PDO::PARAM_STR);
$stmt->bindValue(":password", $request["_password"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
//print_r($results);
if(!count($results))
{
	exitWithError("Пользователь с таким логином/паролем не найден.",4);
}
$currentAdmin=$results[0];



// Process request

if($request["_function"]=="accounts")
{
	$response["responseData"]=accounts();
	exit(json_encode($response));
}
else if($request["_function"]=="account")
{
	$response["responseData"]=account($request["id"]);
	exit(json_encode($response));
}
if($request["_function"]=="newAccounts")
{
	$response["responseData"]=newAccounts();
	exit(json_encode($response));
}
else if($request["_function"]=="activateAccount")
{
	$response["responseData"]=activateAccount($request["id"]);
	exit(json_encode($response));
}
else if($request["_function"]=="wb1Supplies")
{
	$response["responseData"]=wb1Supplies($request["accountId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="wb1Stocks")
{
	$response["responseData"]=wb1Stocks($request["accountId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="wb1Orders")
{
	$response["responseData"]=wb1Orders($request["accountId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="wb1Sales")
{
	$response["responseData"]=wb1Sales($request["accountId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="wb2Supplies")
{
	$response["responseData"]=wb2Supplies($request["accountId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="wb2Stocks")
{
	$response["responseData"]=wb2Stocks($request["accountId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="wb2Orders")
{
	$response["responseData"]=wb2Orders($request["accountId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="accountsToCsv")
{
	$response["responseData"]=accountsToCsv();
	exit(json_encode($response));
}
else if($request["_function"]=="updateTariff")
{
	$response['responseData']=updateTariff($request['id'], $request['value']);//updateExpenseAmount($request['id'], $request['value']);
	exit(json_encode($response));
}


$response["responseData"]="<h3>Неизвестный запрос.</h3>";
exit(json_encode($response));



// ----- FUNCTIONS -----





function accounts()
{
	global $aDb,$currentAdmin;
	
	// Check if account with same email exists.
	$stmt = $aDb->prepare("SELECT accounts.id, accounts.email, accounts.telegram, accounts.phone, accounts.name AS accountName, accounts.wb1_token, accounts.ctime, accounts.stocks_upd_time, tariffs.name AS tariffName FROM accounts LEFT JOIN tariffs ON accounts.tariff_id = tariffs.id ORDER BY accounts.id;");
	if(!$stmt->execute())
	{
		exitWithErrorTg('accounts(). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$ht='<button onclick="loadPageWithHistoryPush(\'?t=accountstocsv\')">В csv</button>';
	$ht.='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><td>#</td><td>id</td><td>email</td><td>Телеграм</td><td>Телефон</td><td>Имя</td><td>Длина токена</td><td>Тариф</td><td>Создан</td><td>Загрузка</td></tr>';
	$rowNum=1;
	foreach ($result as $row)
	{
		$ht.='<tr><td>'.$rowNum++.'</td><td>'.$row['id'].'</td><td style="cursor:pointer;" onclick="loadPageWithHistoryPush(\'?t=account&id='.$row['id'].'\')">'.$row['email'].'</td><td>'.$row['telegram'].'</td><td>'.$row['phone'].'</td><td>'.$row['accountName'].'</td><td>'.strlen($row['wb1_token']).'</td><td>'.$row['tariffName'].'</td><td>'.$row['ctime'].'</td><td>'.$row['stocks_upd_time'].'</td></tr>';
	}
	$ht.='</table></div>';
	return $ht;
}




function account($accountId)
{
	global $aDb,$currentAdmin;
	
	// Check if account with same email exists.
	$stmt = $aDb->prepare("SELECT accounts.id, accounts.email, accounts.telegram, accounts.password, accounts.phone, accounts.name AS accountName, accounts.wb1_token, accounts.ctime, tariffs.name AS tariffName FROM accounts LEFT JOIN tariffs ON accounts.tariff_id = tariffs.id WHERE accounts.id=:id;");
	$stmt->bindValue(":id", $accountId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg('accounts(). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$ht='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><td>#</td><td>id</td><td>email</td><td>Телеграм</td><td>p</td><td>Телефон</td><td>Имя</td><td>Длина токена</td><td>Тариф <img src="../edit.png"></td><td>Создан</td></tr>';
	$rowNum=1;
	foreach ($result as $row)
	{
		$ht.='<tr><td>'.$rowNum++.'</td><td>'.$row['id'].'</td><td>'.$row['email'].'</td><td>'.$row['telegram'].'</td><td>'.$row['password'].'</td><td>'.$row['phone'].'</td><td>'.$row['accountName'].'</td><td>'.strlen($row['wb1_token']).'</td><td onclick="editElementSelect(\'updateTariff\','.$row['id'].',this,tariffsArray);">'.$row['tariffName'].'</td><td>'.$row['ctime'].'</td></tr>';
	}
	$ht.='</table></div>';
	$ht.='<p>API 1</p>';
	$ht.='<button id="suppliesBtn" class="w3-button" onclick="showWb1Supplies('.$row['id'].')">Поставки</button>';
	$ht.='<button id="stocksBtn" class="w3-button" onclick="showWb1Stocks('.$row['id'].')">Остатки</button>';
	$ht.='<button id="ordersBtn" class="w3-button" onclick="showWb1Orders('.$row['id'].')">Заказы</button>';
	$ht.='<button id="salesBtn" class="w3-button" onclick="showWb1Sales('.$row['id'].')">Выкупы</button>';
	$ht.='<p>API 2</p>';
	$ht.='<button id="supplies2Btn" class="w3-button" onclick="showWb2Supplies('.$row['id'].')">Активные поставки</button>';
	$ht.='<button id="stocks2Btn" class="w3-button" onclick="showWb2Stocks('.$row['id'].')">Остатки</button>';
	$ht.='<button id="orders2Btn" class="w3-button" onclick="showWb2Orders('.$row['id'].')">Заказы</button>';
	$ht.='<div id="accountData"> </div>';
	return $ht;
}




function newAccounts()
{
	global $aDb,$currentAdmin;
	
	// Check if account with same email exists.
	$stmt = $aDb->prepare("SELECT new_accounts.id, new_accounts.email, new_accounts.phone, new_accounts.name AS accountName FROM new_accounts ORDER BY new_accounts.id;");
	if(!$stmt->execute())
	{
		exitWithErrorTg('newAccounts(). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$ht='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><th>id</th><th>email</th><th>Телефон</th><th>Имя</th><th> </th></tr>';
	foreach ($result as $row)
	{
		$ht.='<tr><td>'.$row['id'].'</td><td>'.$row['email'].'</td><td>'.$row['phone'].'</td><td>'.$row['accountName'].'</td><td><button onclick="activateAccount('.$row['id'].')">Активировать</button></td></tr>';
	}
	$ht.='</table></div>';
	return $ht;
}




function activateAccount($newAccountId)
{
	global $aDb, $currentAdmin, $trialPeriodDays;
	$stmt = $aDb->prepare("SELECT id, email, telegram, phone, surname, name, secondname, activate_code, password, tariff_id FROM new_accounts WHERE id=:newAccountId;");
	$stmt->bindValue(':newAccountId', $newAccountId, PDO::PARAM_INT);

	if(!$stmt->execute())
	{
		exitWithErrorTg('activateAccount('.$newAccountId.'). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if(!count($results))
	{
		exitWithErrorTg('activateAccount('.$newAccountId.'). Аккаунт не найден.', 'На сервере произошла ошибка.');
	}

	$row=$results[0];

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
		exitWithErrorTg('activateAccount('.$newAccountId.'). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
// Delete from 'new_accounts'.
	$stmt = $aDb->prepare("DELETE FROM new_accounts WHERE id=:id;");
	$stmt->bindValue(':id', $newAccountId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg('activateAccount('.$newAccountId.'). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	return '<p>Аккаунт активирован.</p><p>Email: <a href="mailto:'.$row['email'].'">'.$row['email'].'</a></p><p>Пароль: '.$row['password'].'</p>';
}




function wb1Supplies($accountId)
{
	global $lastUrl;
	$result=curlWbStatisticsGet('supplier/incomes?dateFrom=2017-03-25T21%3A00%3A00.000Z', $accountId);
	return '{"url":"'.$lastUrl.'","результат":'.$result.'}';
}

function wb1Stocks($accountId)
{
//	error_log("wb1Stocks account: ".$accountId);
	return curlWbStatisticsGet('supplier/stocks?dateFrom=2017-03-25T21%3A00%3A00.000Z', $accountId);
}
function wb1Orders($accountId)
{
	return curlWbStatisticsGet('supplier/orders?dateFrom=2017-03-25T21%3A00%3A00.000Z', $accountId);
}
function wb1Sales($accountId)
{
	return curlWbStatisticsGet('supplier/sales?dateFrom=2017-03-25T21%3A00%3A00.000Z', $accountId);
}

function wb2Supplies($accountId) // Поставки
{
	return curlWb2Get('supplies?status=ACTIVE',$accountId);
}
function wb2Stocks($accountId) // Склад
{
	return curlWb2Get('stocks?skip=0&take=300',$accountId);
}
function wb2Orders($accountId) // Склад
{
	return curlWb2Get('orders?date_start=2017-03-25T21%3A00%3A00.000Z&take=100&skip=0',$accountId);
}



function accountsToCsv()
{
	global $aDb,$currentAdmin;
	
	// Check if account with same email exists.
	$stmt = $aDb->prepare("SELECT id, email, phone, name, wb1_token, ctime FROM accounts ORDER BY id;");
	if(!$stmt->execute())
	{
		exitWithErrorTg('accounts(). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$csv="ID, Имя, Мобильный телефон\r\n";
	$rowNum=1;
	foreach ($result as $row)
	{
		$csv.=$row['id'].','.$row['name'].','.$row['phone']."\r\n";
	}
	return $csv;
}




function curlWbStatisticsGet($query, $accountId)
{
	global $aDb, $wbStatisticsURL, $lastUrl;

// Get wb1 token
	$stmt = $aDb->prepare("SELECT accounts.wb1_token FROM accounts WHERE accounts.id=:id;");
	$stmt->bindValue(":id", $accountId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg('accounts(). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!count($result))
	{
		exitWithErrorTg('Не найден аккаунт с id = '.$accountId.' line '.__LINE__, 'На сервере произошла ошибка.');
	}

//
	$ch = curl_init();

	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'GET');
	$headers = [
    	'accept: application/json',
    	'Authorization: '.$result[0]['wb1_token']
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_HEADER, false);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
	//grab URL and pass it to the variable.
	$lastUrl=$wbStatisticsURL.$query;
	curl_setopt($ch, CURLOPT_URL, $lastUrl);

	$result = curl_exec($ch);
	return $result;
}




function curlWb1Get($query, $accountId)
{
	global $aDb, $wb1BaseURL, $lastUrl;

// Get wb1 token
	$stmt = $aDb->prepare("SELECT accounts.wb1_token FROM accounts WHERE accounts.id=:id;");
	$stmt->bindValue(":id", $accountId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg('accounts(). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!count($result))
	{
		exitWithErrorTg('Не найден аккаунт с id = '.$accountId.' line '.__LINE__, 'На сервере произошла ошибка.');
	}

//
	$ch = curl_init();

	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'GET');
	$headers = [
    	'accept: application/json',
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch,CURLOPT_HEADER,false);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
	//grab URL and pass it to the variable.
	$lastUrl=$wb1BaseURL.$query.'&key='.$result[0]['wb1_token'];
	curl_setopt($ch, CURLOPT_URL, $lastUrl);

	$result = curl_exec($ch);
	return $result;
}




function curlWb2Get($query, $accountId)
{
	global $aDb, $wb2BaseURL;
	
// Get wb2 token
	$stmt = $aDb->prepare("SELECT accounts.wb2_token FROM accounts WHERE accounts.id=:id;");
	$stmt->bindValue(":id", $accountId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg('accounts(). '.$stmt->errorInfo()[2].' line '.__LINE__, 'На сервере произошла ошибка.');
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!count($result))
	{
		exitWithErrorTg('Не найден аккаунт с id = '.$accountId.' line '.__LINE__, 'На сервере произошла ошибка.');
	}
//	error_log("wb2 token: ".$result[0]['wb2_token']);

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'GET');
	$headers = [
    	'accept: application/json',
		'Authorization: '.$result[0]['wb2_token']
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch,CURLOPT_HEADER,false);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
	//grab URL and pass it to the variable.
	curl_setopt($ch, CURLOPT_URL, $wb2BaseURL.$query);

	$result = curl_exec($ch);
	return $result;
}




function updateTariff($accountId, $tariffId)
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("UPDATE accounts SET tariff_id=:tariff_id WHERE id=:account_id;");
	$stmt->bindValue(":account_id", $accountId, PDO::PARAM_INT);
	$stmt->bindValue(":tariff_id", $tariffId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении тарифа: ".$stmt->errorInfo()[2]);
	}
}




function chartBar($val,$max,$height) // Столбик
{
	$barHeight=0;
	if($max>0)
	{
		$barHeight=$height*$val/$max;
	}
	$ht='<div style="display:inline-block; background-color:green; width:20px; height:'.$barHeight.'px;">';
	return $ht;
}




function timestampToMonthYearString($timestamp)
{
	$monthNames = ["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"];
	$monthNum=(int)substr($timestamp,5,2);
	return $monthNames[$monthNum-1]." ".substr($timestamp,0,4);
}

function formatDate($timestamp)
{
	$year=substr($timestamp,0,4);
	$month=substr($timestamp,5,2);
	$day=substr($timestamp,8,2);
	return $day.'.'.$month.'.'.$year;
}


?>
