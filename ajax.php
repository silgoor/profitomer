<?php
$periodsCount=6;


require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';
require_once __DIR__.'/send_email.php';
require_once __DIR__.'/random_str.php';

date_default_timezone_set('Europe/Moscow');

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

$loginForm='<p><input id="lk_login" type="text" placeholder="Email" class="w3-input w3-border"  autocomplete="username"><br><input id="lk_password" type="password" placeholder="Пароль" class="w3-input w3-border" autocomplete="current-password"><br><button class="w3-button w3-black" id="lk_login_button" onclick="onLogin()">Войти</button><hr><button class="w3-button w3-black" onclick="loadPageWithHistoryPush(\'?t=registration\');">Зарегистрироваться</button></p>';



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


// Функции без аутентификации.

if($request["_function"]=="createAccount")
{
	$tariffId=$defaultTariffId;
	if(array_key_exists('tariff',$request))
	{
		$tariffId=$request["tariff"];
	}

	$response["responseData"]=createAccount($request['email'], $request['telegram'], $request['phone'], $request['surname'], $request['name'], $request['secondname'], $tariffId);
	exit(json_encode($response));
}
else if($request["_function"]=="getAbout")
{
	$response['responseData']='';
	if(!array_key_exists('_login',$request) || strlen($request['_login'])==0)
	{
		$response['responseData']=$loginForm;
	}
	$response["responseData"].=getAbout();
	exit(json_encode($response));
}
else if($request["_function"]=="getSupport")
{
	$response["responseData"]=getSupport();
	exit(json_encode($response));
}




// АУТЕНТИФИКАЦИЯ.

if(!array_key_exists("_login",$request))
{
	exitWithError("Не задан логин.",2);
}
if(!array_key_exists("_password",$request))
{
	exitWithError("Не задан пароль.",3);
}
$stmt = $aDb->prepare("SELECT accounts.id, accounts.active, accounts.name, accounts.wb1_token, accounts.wb2_token, accounts.balance, accounts.tariff_id, accounts.tax_rate FROM accounts WHERE accounts.email=:login AND accounts.password=:password;");
$stmt->bindValue(":login", $request["_login"], PDO::PARAM_STR);
$stmt->bindValue(":password", $request["_password"], PDO::PARAM_STR);
$stmt->execute();
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
//print_r($results);
if(!count($results) || $results[0]["active"]==0)
{
	exitWithError("Пользователь с таким логином/паролем не найден.",4);
}
$currentUser=$results[0];


function exitWithErrorTg($adminErrorString, $errorString='На сервере произошла ошибка.', $errorNumber=99) // Высылает ошибку в телеграм (специальная группа).
{
	global $response, $currentUser;
	sendMessageToTelegramChat('Id пользователя: '.$currentUser['id'].'. '.$adminErrorString);
	$response["_errorNumber"]=$errorNumber;
	$response["_errorString"]=$errorString;
	exit(json_encode($response,JSON_UNESCAPED_UNICODE));
}


// TEST
/*$currentUser["permissions"]=0;
$testres=isAllowed(["root"]);
echo("1 isAllowed returned ".(int)$testres)."\r\n";
$currentUser["permissions"]=1;
$testres=isAllowed(["root"]);
echo("2 isAllowed returned ".(int)$testres);
*/

// Process request

if($request["_function"]=="activateAccount")
{
	$response["responseData"]=activateAccount($request['email'], $request['activateCode'], $request['surname'], $request['name'], $request['secondname']);
	exit(json_encode($response));
}
if($request["_function"]=="mainMenu")
{
	$response["responseData"]=mainMenu();
	exit(json_encode($response));
}
else if($request["_function"]=="stat")
{
	$response["responseData"]=weeksStatPage();
	exit(json_encode($response));
}
else if($request["_function"]=="getDashboard")
{
	$weekOffset=0;
	if(array_key_exists('weekOffset',$request))
	{
		$weekOffset=$request["weekOffset"];
	}
	$response["responseData"]=getDashboard($weekOffset);
	exit(json_encode($response));
}
else if($request["_function"]=="getStocks")
{
	$response["responseData"]=getStocks();
	exit(json_encode($response));
}
else if($request["_function"]=="getStocks2")
{
	$response["responseData"]=getStocks2();
	exit(json_encode($response));
}
else if($request["_function"]=="getProduct")
{
	$response["responseData"]=getProduct($request["barcode"]);
	exit(json_encode($response));
}
else if($request["_function"]=="getSupplies")
{
	$response["responseData"]=getSupplies($request["incomeId"]);
	exit(json_encode($response));
}
else if($request["_function"]=="getOrders")
{
	$response["responseData"]=getOrders();
	exit(json_encode($response));
}
else if($request["_function"]=="getWeeklyPlan")
{
	$response["responseData"]=getPlanInfo().getWeeklyPlan();
	exit(json_encode($response));
}
else if($request["_function"]=="getDailyPlan")
{
	$response["responseData"]=getPlanInfo().'<h3>Текущая неделя</h3>'.getDailyPlan(0).'<h3>Предыдущая неделя</h3>'.getDailyPlan(1);
	exit(json_encode($response));
}
else if($request["_function"]=="getSales")
{
	$response["responseData"]=getSales();
	exit(json_encode($response));
}
else if($request["_function"]=="getExpenses")
{
	$response["responseData"]=getExpenses();
	exit(json_encode($response));
}
else if($request["_function"]=="getProfile")
{
	$response["responseData"]=getProfile();
	exit(json_encode($response));
}
else if($request["_function"]=="editWbToken")
{
	$response["responseData"]=editWbToken();
	exit(json_encode($response));
}
else if($request["_function"]=="insertExpense")
{
	$response["responseData"]=insertExpense($request['amount'], $request['date'], $request['notes']);
	exit(json_encode($response));
}
else if($request["_function"]=="updateExpenseAmount")
{
	$response['responseData']=update('expenses', 'amount', $request['id'], $request['value']);//updateExpenseAmount($request['id'], $request['value']);
	exit(json_encode($response));
}
else if($request["_function"]=="updateStocksIncost")
{
	$response['responseData']=update('stocks', 'my_incost', $request['id'], $request['value']);
	exit(json_encode($response));
}
else if($request["_function"]=="updateTaxRate")
{
	$response["responseData"]=updateTaxRate($request['value']);
	exit(json_encode($response));
}
else if($request["_function"]=="updateWb1Token")
{
	$response["responseData"]=updateWb1Token($request['value']);
	exit(json_encode($response));
}
else if($request["_function"]=="updatePassword")
{
	$response["responseData"]=updatePassword($request['value']);
	exit(json_encode($response));
}
else if($request["_function"]=="updateSupplyIncost")
{
	$response["responseData"]=updateSupplyIncost($request['id'], $request['value']);
	exit(json_encode($response));
}
else if($request["_function"]=="getWb1Sales")
{
	$response["responseData"]=getWb1Sales();
	exit(json_encode($response));
}
else if($request["_function"]=="getWb1Supplies")
{
	$id=0;
	if(array_key_exists('id',$request))
	{
		$id=$request['id'];
	}
	$response["responseData"]=getWb1Supplies($id);
	exit(json_encode($response));
}
else if($request["_function"]=="getWb2Supplies")
{
	$response["responseData"]=getWb2Supplies();
	exit(json_encode($response));
}
else if($request["_function"]=="getWb1Stocks")
{
	$response["responseData"]=getWb1Stocks();
	exit(json_encode($response));
}
else if($request["_function"]=="importData")
{
	$response["responseData"]=insertToDbSupplies();
	$response["responseData"].=insertToDbStocks();
	$response["responseData"].=insertToDbOrders();
	$response["responseData"].=insertToDbSales();
	exit(json_encode($response));
}
else if($request["_function"]=="weekStatisticsCSV")
{
	$weekOffset=0;
	if(array_key_exists('weekOffset',$request))
	{
		$weekOffset=$request["weekOffset"];
	}
	$response["responseData"]=weekStatisticsCSV($weekOffset);
	exit(json_encode($response));
}
else if($request["_function"]=="importSelfPurchasesFromCSV")
{
	$response["responseData"]=importSelfPurchasesFromCSV($request['csv']);
	exit(json_encode($response));
}




// ----- FUNCTIONS -----




// WB API

function curlWbStatisticsGet($query)
{
	global $currentUser, $wbStatisticsURL;

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'GET');
	$headers = [
    	'accept: application/json',
		'Authorization: '.$currentUser['wb1_token']
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch,CURLOPT_HEADER,false);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
	//grab URL and pass it to the variable.
	curl_setopt($ch, CURLOPT_URL, $wbStatisticsURL.$query);

	$result = curl_exec($ch);
	return $result;
}

function curlWb1Get($query)
{
	global $currentUser,$wb1BaseURL;

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'GET');
	$headers = [
    	'accept: application/json',
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch,CURLOPT_HEADER,false);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
	//grab URL and pass it to the variable.
	curl_setopt($ch, CURLOPT_URL, $wb1BaseURL.$query.'&key='.$currentUser['wb1_token']);

	$result = curl_exec($ch);
	return $result;
}

function curlWb2Get($query)
{
	global $currentUser,$wb2BaseURL;

	$ch = curl_init();

	curl_setopt($ch,CURLOPT_CUSTOMREQUEST,'GET');
	$headers = [
    	'accept: application/json',
		'Authorization: '.$currentUser['wb2_token']
	];
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch,CURLOPT_HEADER,false);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 
	//grab URL and pass it to the variable.
	curl_setopt($ch, CURLOPT_URL, $wb2BaseURL.$query);

	$result = curl_exec($ch);
	return $result;
}



function getWb1Stocks() // Остатки
{
	return curlWb1Get('supplier/stocks?dateFrom=2017-03-25T21%3A00%3A00.000Z');
}
function getWb2Stocks() // Остатки
{
	$result = json_decode(curlWb2Get('stocks?skip=0&take=300'),true);
	$ht='';
	foreach ($result['stocks'] as $stock)
	{
		$ht.='<p>'.$stock['subject'].' размер '.$stock['size'].' остаток '.$stock['stock'].'</p>';
//		error_log("column: ".$columnName." role: ".column["role"]." inputType: ".column["inputType"]);
	}

	return $ht;
}
function getWb1Supplies($incomeId) // Поставки
{
	return curlWb1Get('supplier/incomes?dateFrom=2017-03-25T21%3A00%3A00.000Z');
}
function getWb2Supplies() // Поставки
{
	return curlWb2Get('supplies?status=ACTIVE');
}
function getWb1Sales() // Продажи
{
	return curlWb1Get('supplier/sales?dateFrom=2017-03-25T21%3A00%3A00.000Z');
}


function createAccount($email, $telegram, $phone, $surname, $name, $secondname, $tariffId)
{
	global $aDb,$currentUser;
	
	$email = str_replace(' ', '', $email);
	// Patch phone number.
	$phone=preg_replace('/[^0-9\+]/', "", $phone);
	if(strlen($phone)===10)
	{
		$phone="+7".$phone;
	}
	else if(strlen($phone)===11 && $phone[0]==='8')
	{
		$phone=substr($phone, 1);
		$phone="+7".$phone;
	}
	
	$telegram = trim($telegram);
	
	$surname = trim($surname);
	$name = trim($name);
	$secondname = trim($secondname);
	
	// Check if tariff exists.
	if($tariffId)
	{
		$stmt = $aDb->prepare("SELECT active FROM tariffs WHERE id = :id;");
		$stmt->bindValue(":id", $tariffId, PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg('createAccount. '.$stmt->errorInfo()[2].' line '.__LINE__);
		}
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(!count($result))
		{
			return "Указан некорректный тариф: ".$tariffId;
		}
/*		if($result[0]['active']<1)
		{
			return "Тариф отключен.";
		}*/
	}

	// Check if account with same email exists.
	{
		$stmt = $aDb->prepare("SELECT id FROM accounts WHERE email = :email;");
		$stmt->bindValue(":email", $email, PDO::PARAM_STR);
		if(!$stmt->execute())
		{
			exitWithErrorTg('createAccount. '.$stmt->errorInfo()[2].' line '.__LINE__);
		}
		$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($result))
		{
			return "Аккаунт с данной почтой уже существует. Воспользуйтесь восстановлением пароля.";
		}
	}
	// Remove records from new_accounts table with same email.
	$stmt = $aDb->prepare("DELETE FROM new_accounts WHERE email = :email;");
	$stmt->bindValue(':email', $email, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса бд при удалении дубликатов нового аккаунта: ".$stmt->errorInfo()[2]);
	}
	// Insert new account.
	$activate_code=random_str();
	$password=random_str();
	$stmt = $aDb->prepare("INSERT INTO new_accounts(email, telegram, phone, surname, name, secondname,  activate_code, password, tariff_id) VALUES (:email, :telegram, :phone, :surname, :name, :secondname, :activate_code, :password, :tariff_id);");
	$stmt->bindValue(':email', $email, PDO::PARAM_STR);
	$stmt->bindValue(':telegram', $telegram, PDO::PARAM_STR);
	$stmt->bindValue(':phone', $phone, PDO::PARAM_STR);
	$stmt->bindValue(':surname', $surname, PDO::PARAM_STR);
	$stmt->bindValue(':name', $name, PDO::PARAM_STR);
	$stmt->bindValue(':secondname', $secondname, PDO::PARAM_STR);
	$stmt->bindValue(':tariff_id', $tariffId, PDO::PARAM_INT);
	$stmt->bindValue(':activate_code', $activate_code, PDO::PARAM_STR);
	$stmt->bindValue(':password', $password, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса бд при создании аккаунта: ".$stmt->errorInfo()[2]);
	}
	$url='https://profitomer.ru/email_confirm.php?email='.$email.'&activatecode='.$activate_code;
$emailHtMessage = <<<EOT
<h3>Здравствуйте! Вы зарегистрировались на сервисе profitomer.ru</h3>
<hr>
<p>Логин: $email</p>
<p>Пароль: $password</p>
<hr>

<p>Для активации аккаунта жмите на кнопку:<a href="$url"><button style="padding: 16px; background-color: coral; color: white; font-size: 1.2em;">Активировать</button></a></p>
<p>Вступайте наш в чат, чтобы получать поддержку и узнавать о новых функциях сервиса: <a href="https://t.me/profitomer_support">https://t.me/profitomer_support</a></p>
EOT;
	sendEmail($email, "Профитомер - регистрация", $emailHtMessage);

	return '<p>Вам на почту выслано письмо. Откройте его и нажмите кнопку активации аккаунта.</p><p>Если письмо не приходит, напишите нам: <a href="mailto:support@profitomer.ru">support@profitomer.ru</a></p>';
}




function updateTaxRate($taxRate) // Изменить токен wb.
{
	global $aDb,$currentUser;
//	sendMessageToTelegramChat("update wb1 token: ".$wb1Token." account: ".$currentUser['id']);
	$stmt = $aDb->prepare("UPDATE accounts SET tax_rate=:tax_rate WHERE id=:account_id;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":tax_rate", $taxRate, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении налоговой ставки: ".$stmt->errorInfo()[2]);
	}
}



function updateWb1Token($wb1Token) // Изменить токен wb.
{
	global $aDb,$currentUser;
//	sendMessageToTelegramChat("update wb1 token: ".$wb1Token." account: ".$currentUser['id']);
	$stmt = $aDb->prepare("UPDATE accounts SET wb1_token=:wb1_token WHERE id=:account_id;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":wb1_token", $wb1Token, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении токена wb1: ".$stmt->errorInfo()[2]);
	}
}




function updatePassword($password) // Изменить пароль.
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("UPDATE accounts SET password=:password WHERE id=:account_id;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":password", $password, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении пароля: ".$stmt->errorInfo()[2]);
	}
}




function getStartOfWeekDate($date = null)
{
    if ($date instanceof \DateTime) {
        $date = clone $date;
    } else if (!$date) {
        $date = new \DateTime();
    } else {
        $date = new \DateTime($date);
    }
    
    $date->setTime(0, 0, 0);
    
    if ($date->format('N') == 1) {
        // If the date is already a Monday, return it as-is
        return $date;
    } else {
        // Otherwise, return the date of the nearest Monday in the past
        // This includes Sunday in the previous week instead of it being the start of a new week
        return $date->modify('last monday');
    }
}




function generateDaysOfWeekArray($week) // $week = 0 - текущая неделя. $week = 1 - предыдущая, и т.д.
{
	// Fill array.
	$statisticsArray=[];
	$monday=getStartOfWeekDate();
//	$sunday=clone($monday);
//	$sunday->modify('+7 days');
//	$sunday->modify("-1 second");
	if($week>0)
	{
		$monday->modify('-'.($week*7).' days');
//		$sunday->modify('+'.$offset.' days');
	}
	for($i = 0; $i < 7; $i++)
	{
		$period=[];
		$period['days'] = 1;
		
		$period['start'] = $monday->format("Y-m-d\T00:00:00.00");
		$period['end'] = $monday->format("Y-m-d\T23:59:59.00");
		$monday->modify('+1 days');

		$statisticsArray[] = $period;
	}
	return $statisticsArray;
}




// DATA
function generateWeeksArray($count, $offset)
{
	// Fill array.
	$statisticsArray=[];
	$monday=getStartOfWeekDate();
	$sunday=clone($monday);
	$sunday->modify('+7 days');
	$sunday->modify("-1 second");
	if($offset>0)
	{
		$monday->modify('+'.$offset.' days');
		$sunday->modify('+'.$offset.' days');
	}
	for($i = 0; $i < $count; $i++)
	{
		$period=[];
		$period['days'] = 7;
		
		$period['start'] = $monday->format("Y-m-d\T00:00:00.00");
		$period['end'] = $sunday->format("Y-m-d\T23:59:59.00");
		$monday->modify('-7 days');
		$sunday->modify('-7 days');

/*		$start = date('Y-m-d', strtotime('-'.($i*7-$offset).' days monday 00:00:00')); // week start Y-m-d\TH:i:s.00
		$start2 = date('Y-m-d', strtotime('-'.($i*7-$offset).' days last monday 00:00:00')); // week start Y-m-d\TH:i:s.00
//		if($start>$start2)
		{
			$period['start'] = $start;
		}
//		else
		{
			$period['start'] = $start2;
		}
		$period['end'] = date('Y-m-d', strtotime('-'.($i*7-$offset).' days sunday 23:59:59')); // week end
*/
		$statisticsArray[] = $period;
	}
	return $statisticsArray;
}




function generateMonthsArray($count)
{
	// Fill array.
	$statisticsArray=[];
	$startDate = new \DateTime();
	for($i = 0; $i < $count; $i++)
	{
		$period=[];
		$startDate->modify('last day of this month');
		$period['end'] = $startDate->format("Y-m-d\T23:59:59.00");
		$startDate->modify('first day of this month');
		$period['start'] = $startDate->format("Y-m-d\T00:00:00.00");
		$period['days'] = cal_days_in_month(CAL_GREGORIAN, $startDate->format('m'), $startDate->format('Y'));
//		echo 'month '.$startDate->format('m').' days '.$period['days'];
		$startDate->modify('-1 months');
		
//		$period['start'] = date('Y-m-01\TH:i:s.00', strtotime('-'.$i.' months 00:00:00'));
//		$period['end'] = date('Y-m-t\TH:i:s.00', strtotime('-'.$i.' months 23:59:59'));
		$statisticsArray[] = $period;
	}
	return $statisticsArray;
}




function getSuppliesSum($startDate, $endDate, $barcode='', $nmId='') // Сумма поставок за период. Возвращает ассоциативный массив с totalQuantity и totalSum
{
	global $aDb,$currentUser;
	$barcodeQuery='';
	if(strlen($barcode))
	{
		$barcodeQuery=' AND barcode=:barcode';
	}
	$nmIdQuery='';
	if(strlen($nmId))
	{
		$nmIdQuery=' AND nmId=:nmId';
	}
	$stmt = $aDb->prepare("SELECT SUM(quantity) AS totalQuantity, SUM(quantity * my_incost) AS totalSum FROM supplies WHERE account_id = :account_id AND date >= :start_date AND date <= :end_date".$barcodeQuery.";");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":start_date", $startDate, PDO::PARAM_STR);
	$stmt->bindValue(":end_date", $endDate, PDO::PARAM_STR);
	if(strlen($barcode))
	{
		$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	}
	if(strlen($nmId))
	{
		$stmt->bindValue(":nmId", $nmId, PDO::PARAM_STR);
	}
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при запросе суммы поставок: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
//	print_r($result);
	return $result[0];
}




function getSalesSum($startDate, $endDate, $barcode='', $nmId='') // Сумма продаж за период. Возвращает ассоциативный массив с totalQuantity и totalSum
{
	
	global $aDb,$currentUser;

//	error_log("#");
//	error_log("getSalesSum accountId:".$currentUser['id']." startDate: ".$startDate." endDate: ".$endDate);

	$totalQuantity=0;
	$totalIncost=0;
	$totalSum=0;
	$barcodeQuery='';
	if(strlen($barcode))
	{
		$barcodeQuery=' AND sales.barcode = :barcode';
	}
	$stmt = $aDb->prepare("SELECT sales.quantity, MAX(stocks.my_incost) AS my_incost, sales.forPay, sales.date, sales.my_selfpurchase FROM sales LEFT JOIN stocks ON stocks.barcode=sales.barcode WHERE sales.account_id = :account_id AND sales.date >= :start_date AND sales.date <= :end_date AND sales.my_selfpurchase = 0".$barcodeQuery." GROUP BY sales.id;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":start_date", $startDate, PDO::PARAM_STR);
	$stmt->bindValue(":end_date", $endDate, PDO::PARAM_STR);
	if(strlen($barcode))
	{
		$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	}
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при запросе суммы продаж: ".$stmt->errorInfo()[2]);
	}
//	$stmt->debugDumpParams();
//	error_log("row count: ".$stmt->rowCount()." start date: ".$startDate." end date: ".$endDate);
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $row)
	{
//		error_log("quantity: ".$row['quantity']." incost: ".$row['my_incost']." forPay: ".$row['forPay']." date: ".$row['date']);
		$totalQuantity+=$row['quantity'];
		$totalIncost+=$row['quantity']*$row['my_incost'];
		$totalSum+=$row['quantity']*abs($row['forPay']);
	}
	$ret=[];
	$ret['totalQuantity']=$totalQuantity;
	$ret['totalIncost']=$totalIncost;
	$ret['totalSum']=$totalSum;
	return $ret;


}




function getSelfPurchasesSum($startDate, $endDate, $barcode='') // Сумма затрат на самовыкупы за период. Возвращает ассоциативный массив с totalQuantity и totalSum
{
	
	global $aDb,$currentUser;

	$totalQuantity=0;
	$totalIncost=0;
	$totalSum=0;
	$barcodeQuery='';
	if(strlen($barcode))
	{
		$barcodeQuery=' AND sales.barcode = :barcode';
	}
	$stmt = $aDb->prepare("SELECT sales.quantity, sales.forPay, sales.finishedPrice, sales.my_selfpurchase FROM sales WHERE sales.account_id = :account_id AND sales.date >= :start_date AND sales.date <= :end_date AND sales.my_selfpurchase > 0".$barcodeQuery.";");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":start_date", $startDate, PDO::PARAM_STR);
	$stmt->bindValue(":end_date", $endDate, PDO::PARAM_STR);
	if(strlen($barcode))
	{
		$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	}
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при запросе суммы продаж: ".$stmt->errorInfo()[2]);
	}
//	error_log("row count: ".$stmt->rowCount()." start date: ".$startDate." end date: ".$endDate);
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $row)
	{
//		error_log("quantity: ".$row['quantity']." incost: ".$row['my_incost']." forPay: ".$row['forPay']." date: ".$row['date']);
		if($row['quantity']>0)
		{
			$selfpurchaseCost=0;
			if($row['my_selfpurchase'])
			{
				$selfpurchaseCost=$row['my_selfpurchase'];
			}
			$totalQuantity+=$row['quantity'];
			$tax=$row['forPay']*$currentUser['tax_rate']/100;
			$totalSum+=$row['quantity']*$row['finishedPrice']-$row['forPay']+$tax+$selfpurchaseCost;
		}
	}
//	error_log("totalSum: ".$totalSum);
//	error_log("*");
//	error_log("*");
	$ret=[];
	$ret['totalQuantity']=$totalQuantity;
	$ret['totalSum']=$totalSum;
	return $ret;
}




function getExpensesSum($startDate,$endDate) // Сумма расходов за период. Возвращает ассоциативный массив с totalSum
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("SELECT SUM(amount) AS totalSum FROM expenses WHERE account_id = :account_id AND date >= :start_date AND date <= :end_date;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":start_date", $startDate, PDO::PARAM_STR);
	$stmt->bindValue(":end_date", $endDate, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при запросе суммы расходов: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $result[0];
}




function getRest($barcode='')
{
	global $aDb,$currentUser;
	$barcodeQuery='';
	if(strlen($barcode))
	{
		$barcodeQuery=' AND barcode = :barcode';
	}
	$stmt = $aDb->prepare('SELECT SUM(quantity) AS quantitysum FROM stocks WHERE account_id=:account_id'.$barcodeQuery.';');
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(strlen($barcode))
	{
		$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	}
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении остатков: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $result[0]['quantitysum'];
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




function weeksStatPage($weekOffset) // Страница статистики
{
	global $periodsCount;
	$weeksArray = generateWeeksArray($periodsCount,$weekOffset);
	$selected0='';
	if($weekOffset==0)
	{
		$selected0=' selected';
	}
	$selected1='';
	if($weekOffset==1)
	{
		$selected1=' selected';
	}
	$selected2='';
	if($weekOffset==2)
	{
		$selected2=' selected';
	}
	$selected3='';
	if($weekOffset==3)
	{
		$selected3=' selected';
	}
	$selected4='';
	if($weekOffset==4)
	{
		$selected4=' selected';
	}
	$selected5='';
	if($weekOffset==5)
	{
		$selected5=' selected';
	}
	$selected6='';
	if($weekOffset==6)
	{
		$selected6=' selected';
	}
//	$selectedArray=[];
//	$selectedArray[$weekOffset]=' default';
$ht = <<<EOT
<p>
<label for="weekOffset">Начало отчета:</label>
<select name="weekOffset" id="weekOffset" onchange="loadPageWithHistoryPush('?t=dashboard&weekoffset='+this.value)">
  <option value="0"$selected0>Понедельник</option>
  <option value="1"$selected1>Вторник</option>
  <option value="2"$selected2>Среда</option>
  <option value="3"$selected3>Четверг</option>
  <option value="4"$selected4>Пятница</option>
  <option value="5"$selected5>Суббота</option>
  <option value="6"$selected6>Воскресенье</option>
</select>
</p>
EOT;

//	$weeksArray=fillPeriods($weeksArray);//,$barcode);
	$ht.=buildStatTable($weeksArray);
	$ht.='<p><button class="w3-button w3-black" onclick="const urlParams = new URLSearchParams(window.location.search);ajax(addIdAndAuth({\'_function\':\'weekStatisticsCSV\',\'weekOffset\':urlParams.get(\'weekoffset\')}),onWeekStatisticsCSVResponse);
">Экспорт в csv</button></p>';
	return $ht;
}




function array2csv($data, $delimiter = ',', $enclosure = '"', $escape_char = "\\")
{
    $f = fopen('php://memory', 'r+');
    foreach ($data as $item) {
        fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
    }
    rewind($f);
    return stream_get_contents($f);
}




function weekStatisticsCSV($weekOffset)
{
	global $periodsCount;
	$weeksArray = generateWeeksArray($periodsCount,$weekOffset);
	$weeksArray = fillPeriods($weeksArray);
	$csv = '';
	$csv.="Период, Выручка, Себестоимость, Расходы, Налоги, Прибыль\r\n";
	for($i=0;$i<count($weeksArray);$i++)
	{
		$csv.=formatDate($weeksArray[$i]['start']).' - '.formatDate($weeksArray[$i]['end']);
		$csv.=', '.round($weeksArray[$i]['sales']['totalSum']);
		$csv.=', '.round($weeksArray[$i]['sales']['totalIncost']);
//		$csv.=', '.round($weeksArray[$i]['supplies']['totalSum']);
		$csv.=', '.round($weeksArray[$i]['expenses']['totalSum']);
		$csv.=', '.round($weeksArray[$i]['tax']);
		$csv.=', '.round($weeksArray[$i]['profit']);
		$csv.="\r\n";
	}

	return $csv;//array2csv($weeksArray);
}




function monthsStatPage() // Страница статистики
{
	global $periodsCount;
	$monthsArray = generateMonthsArray($periodsCount);

//	$monthsArray=fillPeriods($monthsArray);//,$barcode);
	return buildStatTable($monthsArray);
}




function fillPeriods($periodsArray,$barcode='') // Заполняем массив статистики
{
	global $currentUser;
	$maxProfit=0;
	for($i=0;$i<count($periodsArray);$i++)
	{
		$periodsArray[$i]['sales']=getSalesSum($periodsArray[$i]['start'],$periodsArray[$i]['end'],$barcode);
		$periodsArray[$i]['supplies']=getSuppliesSum($periodsArray[$i]['start'],$periodsArray[$i]['end'],$barcode);
		$periodsArray[$i]['expenses']=getExpensesSum($periodsArray[$i]['start'],$periodsArray[$i]['end']);
		$periodsArray[$i]['selfPurchases']=getSelfPurchasesSum($periodsArray[$i]['start'],$periodsArray[$i]['end'],$barcode);
		$periodsArray[$i]['tax']=((int)$periodsArray[$i]['sales']['totalSum'])*$currentUser['tax_rate']/100;
		$periodsArray[$i]['profit']=$periodsArray[$i]['sales']['totalSum']-$periodsArray[$i]['sales']['totalIncost']-$periodsArray[$i]['selfPurchases']['totalSum']-$periodsArray[$i]['tax'];
		if(strlen($barcode)==0) // Расходы вычитаем только для общей статистики.
		{
			$periodsArray[$i]['profit']-=$periodsArray[$i]['expenses']['totalSum'];
		}
		if($maxProfit<$periodsArray[$i]['profit'])
		{
			$maxProfit=$periodsArray[$i]['profit'];
		}
	}
	return $periodsArray;
}

function getMaxProfit($periodsArray)
{
	$maxProfit=0;
	for($i=0;$i<count($periodsArray);$i++)
	{
		if($maxProfit<$periodsArray[$i]['profit'])
		{
			$maxProfit=$periodsArray[$i]['profit'];
		}
	}
	return $maxProfit;
}




function buildStatTable($periodsArray,$barcode='') // Страница статистики
{
	global $currentUser;
	$csv='';
	$taxRate=$currentUser['tax_rate'];
	$ht='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><td>Период</td>';
	$turnoverHt='<tr title="Хватит на столько дней. Оборачиваемость = текущий остаток / среднее кол-во продаж в день за период."><td>Оборачиваемость</td>';
	$salesHt='<tr><td><a href="?t=sales">Выкупы</a></td>';
	$salesInCostHt='<tr title="Себестоимость = кол-во продаж * себестоимость в последней поставке"><td>Себестоимость</td>';
	$suppliesHt='<tr><td><a href="?t=supplies">Поставки</a></td>';
	$expensesHt='<tr><td><a href="?t=expenses">Расходы</a></td>';
	$selfPurchasesHt='<tr title="Cамовыкупы = стоимость покупки - сумма к перечислению + налог"><td>Самовыкупы</td>';
	$taxHt='<tr title="Налоги = продажи * налоговая ставка"><td>Налоги</td>';
	$profitHt='<tr title="Прибыль = продажи - себестоимость - налоги - расходы - комиссия вб с самовыкупов"><td><b>Прибыль</b></td>';
	$profitChartHt='<tr><td> </td>';
	$profitRateHt='<tr title="Прибыльность = прибыль / продажи * 100"><td>Прибыльность, %</td>';

	$periodsArray=fillPeriods($periodsArray,$barcode);
	$maxProfit=getMaxProfit($periodsArray);
	$rest=getRest($barcode);

	for($i=0;$i<count($periodsArray);$i++)
	{
		$ht.='<th>'.formatDate($periodsArray[$i]['start']).'<br>'.formatDate($periodsArray[$i]['end']).'</th>';
		$dailySales=$periodsArray[$i]['sales']['totalQuantity']/$periodsArray[$i]['days'];
		$turnover='-';
		if($dailySales>0)
		{
			$turnover=round($rest/$dailySales,1);
		}
		if($i==0)
		{
			$turnover=' ';
		}
		$turnoverHt.='<td>'.$turnover.'</td>';
		$salesHt.='<td>'.round($periodsArray[$i]['sales']['totalSum']).'</td>';
		$salesInCostHt.='<td>'.round($periodsArray[$i]['sales']['totalIncost']).'</td>';
		$suppliesHt.='<td>'.round($periodsArray[$i]['supplies']['totalSum']).'</td>';
		$expensesHt.='<td>'.round($periodsArray[$i]['expenses']['totalSum']).'</td>';
		$selfPurchasesHt.='<td>'.round($periodsArray[$i]['selfPurchases']['totalSum']).'</td>';
		$taxHt.='<td>'.round($periodsArray[$i]['tax']).'</td>';
		$profitHt.='<td><b>'.round($periodsArray[$i]['profit']).'</b></td>';
		$profitChartHt.='<td style="vertical-align: bottom;">'.chartBar($periodsArray[$i]['profit'], $maxProfit, 100).'</td>';
		$profitRate='';
		if($periodsArray[$i]['sales']['totalSum']>0)
		{
			$profitRate=$periodsArray[$i]['profit']/$periodsArray[$i]['sales']['totalSum']*100;
		}
		$profitRateHt.='<td>'.round((float)$profitRate).'</td>';
//		$profitHt.='<td>'.($periodsArray[$i]['sales']['totalSum']-$periodsArray[$i]['supplies']['totalSum']-$periodsArray[$i]['expenses']['totalSum']-$periodsArray[$i]['tax']).'</td>';
	}
	$ht.='</tr>';
	$turnoverHt.='</tr>';
	$salesHt.='</tr>';
	$salesInCostHt.='</tr>';
	$suppliesHt.='</tr>';
	$expensesHt.='</tr>';
	$selfPurchasesHt.='</tr>';
	$taxHt.='</tr>';
	$profitHt.='</tr>';
	$profitChartHt.='</tr>';
	$profitRateHt.='</tr>';
	$ht.=$turnoverHt.$salesHt.$salesInCostHt.$taxHt;
	if(strlen($barcode)==0) // Расходы показываем только для общей статистики.
	{
		$ht.=$expensesHt;
	}
	$ht.=$selfPurchasesHt.$profitHt.$profitChartHt.$profitRateHt.$suppliesHt;
	$ht.='</table></div>';
	return $ht;
}




function getDashboard($weekOffset) // Статистика
{
	global $aDb,$currentUser;
	$ht='';
	$period='month';
	
	if(!strlen($currentUser['wb1_token']))
	{
		$ht.='<div class="w3-panel w3-red w3-center"><h3 style="color: white;">Отсутствует токен</h3> <button class="w3-button w3-black" onclick="loadPageWithHistoryPush(\'?t=editWbToken\')">Ввести токен</button></div>';
	}
	$ht.='<button class="w3-button w3-black" onclick="loadPageWithHistoryPush(\'?t=dailyplan\')">План по дням</button>';
	$ht.='<button class="w3-button w3-black" onclick="loadPageWithHistoryPush(\'?t=weeklyplan\')">План по неделям</button>';
	$ht.='<h4>Отчет по неделям</h4>';
	$ht.=weeksStatPage($weekOffset);
	
	$ht.='<br><h4>Отчет по месяцам</h4>';
	$ht.=monthsStatPage();
	$ht.='<br><br>';
	
	return $ht;
}





function getStocks() // Товары
{
	global $aDb,$currentUser;
	$ht='';
	// Время обновления
	{
		$stmt = $aDb->prepare("SELECT stocks_upd_time FROM accounts WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при получении времени обновления остатков: ".$stmt->errorInfo()[2]);
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$ht.='<p>Время обновления: '.substr($rows[0]['stocks_upd_time'],0,16).'</p>';
	}
	
	// Детализированные остатки
	$stmt = $aDb->prepare("SELECT id, subject, quantity, nmId, techSize, warehouseName, barcode, my_incost FROM stocks WHERE account_id=:account_id ORDER BY nmId, quantity DESC;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->execute();
	$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);

	// Группированные остатки
	$stmt = $aDb->prepare("SELECT SUM(quantity) AS quantitysum, nmId, MAX(subject) AS subject FROM stocks WHERE account_id=:account_id GROUP BY nmId ORDER BY quantitysum DESC;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->execute();
	$stocksGrouped = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$htTable='<table class="w3-table w3-bordered w3-centered"><tr><th>Фото</th><th>Категория</th><th>Артикул</th><th>Остаток</th></tr>';
	$stockTotalSum=0;
	foreach ($stocksGrouped as $sgRow)
	{
//		$ht.='<p>'.$row['nmId'].' '.$row['quantitysum'].'</p>';
		$imgFolder=substr($sgRow['nmId'], 0, -4).'0000';
		$htTable.='<tr>';
		$htTable.='<td><a href="https://www.wildberries.ru/catalog/'.$sgRow['nmId'].'/detail.aspx" target="_blank"><div style="display: inline-block; width:50; height:50; background-size:contain; background-repeat:no-repeat; background-image:url(https://images.wbstatic.net/c246x328/new/'.$imgFolder.'/'.$sgRow['nmId'].'-1.jpg)"></div></a></td>';
		$htTable.='<td>'.$sgRow['subject'].'</td>';
		$htTable.='<td>'.$sgRow['nmId'].'</td>';
		$htTable.='<td><b>'.$sgRow['quantitysum'].'</b></td>';
		$htTable.='</tr>';
		$nmId=$sgRow['nmId'];
		$htTable.='<tr><td colspan="6">';
		$htTable.='<table><tr><th>Размер</th><th>Склад</th><th>Остаток</th><th>Себестоимость <img src="edit.png"></th></tr>';
		foreach ($stocks as $stock)
		{
			if($nmId==$stock['nmId'])
			{
				$stockTotalSum+=$stock['quantity']*$stock['my_incost'];
				$htTable.='<tr>';
				$htTable.='<td><a href="?t=product&barcode='.$stock['barcode'].'">'.$stock['techSize'].'</a></td>';
				$htTable.='<td>'.$stock['warehouseName'].'</td>';
				$htTable.='<td>'.$stock['quantity'].'</td>';
//				$htTable.='<td id="incost'.$stock['id'].'"><span id="incostVal'.$stock['id'].'">'.(int)$stock['my_incost'].'</span> <img src="edit.png" onclick="editSI('.$stock['id'].');"></td>';
				$htTable.='<td id="incost'.$stock['id'].'" onclick="editStockIncost('.$stock['id'].')">'.(int)$stock['my_incost'].'</td>';
				
			}
		}
		$htTable.='</tr></table>';
		$htTable.='</td></tr>';

	}
	$htTable.='</table>';
	$ht.='<hr><h4>Итого по себестоимости: '.$stockTotalSum.'</h4><hr>';
	$ht.=$htTable;
	
	

	return $ht;
}



function getStocks2() // Остатки, оборачиваемость и рекомендация к закупке
{
	global $aDb,$currentUser;
	$periodDays=28;
	$startDate = new \DateTime();
	$startDate->modify('-'.$periodDays.' day');
	$startDateString=$startDate->format('Y-m-d');
	$ht='';
	// Время обновления
	{
		$stmt = $aDb->prepare("SELECT stocks_upd_time FROM accounts WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при получении времени обновления остатков: ".$stmt->errorInfo()[2]);
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$ht.='<p>Время обновления: '.substr($rows[0]['stocks_upd_time'],0,16).'</p>';
	}
	// Детализированные остатки
	$stmt = $aDb->prepare("SELECT MAX(nmId) AS nmId, barcode, MAX(techSize) AS techSize, SUM(quantity) AS sumquantity FROM stocks WHERE account_id=:account_id GROUP BY barcode;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении остатков: ".$stmt->errorInfo()[2]);
	}
	$stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$htTable='<table class="w3-table w3-bordered w3-centered"><tr><th>Артикул</th><th>Баркод</th><th>Размер</th><th>Остаток</th><th>Выкуплено за 4 недели</th><th>Оборачиваемость, дней</th><th>Закупить</th></tr>';
	foreach ($stocks as $stock)
	{
		$salesQuantity=getSalesQuantity($stock['barcode'],$startDateString);
		$salesPerDay=$salesQuantity/$periodDays;
		$turnover='-'; // На сколько дней хватит.
		$toBuy=0;
		$targetTurnover=200; // На сколько дней должно хватать.
		if($salesQuantity>0)
		{
			$turnover=round($stock['sumquantity']/$salesPerDay);
			$toBuy=floor(($targetTurnover-$turnover)*$salesPerDay);// к закупке 
			if($toBuy<0)
			{
				$toBuy=0;
			}
		}
		$htTable.='<tr>';
		$htTable.='<td>'.$stock['nmId'].'</td>';
		$htTable.='<td>'.$stock['barcode'].'</td>';
		$htTable.='<td><a href="?t=product&barcode='.$stock['barcode'].'">'.$stock['techSize'].'</a></td>';
		$htTable.='<td>'.$stock['sumquantity'].'</td>';
		$htTable.='<td>'.$salesQuantity.'</td>';
		$htTable.='<td>'.$turnover.'</td>';
		$htTable.='<td>'.$toBuy.'</td>';
		$htTable.='</tr>';
	}
	$htTable.='</table>';
	return $ht.$htTable;
}
function getSalesQuantity($barcode,$startDateStr)
{
	global $aDb,$currentUser;
	error_log("getSalesQuantity() barcode: ".$barcode." startDateStr: ".$startDateStr);
	$stmt = $aDb->prepare("SELECT SUM(quantity) AS quantitysum FROM sales WHERE account_id=:account_id AND my_selfpurchase=0 AND date>=:startDate AND barcode=:barcode;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":startDate", $startDateStr, PDO::PARAM_STR);
	$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении остатков: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	error_log("result: ".print_r($result,true));
	return $result[0]['quantitysum'];
}



function getProduct($barcode) // Информация по товару.
{
	global $aDb,$currentUser;
	$ht='';
	$stmt = $aDb->prepare("SELECT techSize FROM stocks WHERE account_id=:account_id AND barcode=:barcode;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении инфо о продукте: ".$stmt->errorInfo()[2]);
	}
	$ht.='<h3>Баркод: '.$barcode.'</h3>';
	
	
	$weeksArray = generateWeeksArray(10,0);
//	$weeksArray=fillPeriods($weeksArray,$barcode);
	$ht.=buildStatTable($weeksArray,$barcode);
	return $ht;
}




function getSuppliesNumbers() // Массив номеров поставок.
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("SELECT incomeId FROM supplies WHERE account_id=:account_id GROUP BY incomeId ORDER BY incomeId DESC;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении номеров поставок: ".$stmt->errorInfo()[2]);
	}
	return $stmt->fetchAll(PDO::FETCH_COLUMN);
}




function getSupplies($incomeId) // Поставки
{
	global $aDb,$currentUser;
	$ht='';

	// Время обновления
	{
		$stmt = $aDb->prepare("SELECT supplies_upd_time FROM accounts WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при получении времени обновления поставок: ".$stmt->errorInfo()[2]);
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$ht.='<p>Время обновления: '.substr($rows[0]['supplies_upd_time'],0,16).'</p>';
	}

	$incomeIdArray=getSuppliesNumbers();
	if($incomeId==0 && count($incomeIdArray))
	{
		$incomeId=$incomeIdArray[0];
	}
	$ht.='<p>';
	foreach ($incomeIdArray as $iId)
	{
		$current='';
		if($incomeId==$iId)
		{
			$current=' w3-2021-amethyst-orchid';
		}
		$ht.='<button class="w3-button'.$current.'" onclick="loadPageWithHistoryPush(\'?t=supplies&id='.$iId.'\')">'.$iId.'</button>';
//		error_log("column: ".$columnName." role: ".column["role"]." inputType: ".column["inputType"]);
	}
	$ht.='</p>';
	
	$stmt = $aDb->prepare("SELECT id, incomeId, date, quantity, my_incost, nmId, barcode, techSize FROM supplies WHERE account_id=:account_id AND incomeId=:incomeId;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":incomeId", $incomeId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении поставок: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$totalQuantity=0;
	$tableHt='<table class="w3-table-all"><tr><th>Фото</th><th>Дата</th><th>Артикул</th><th>Штрих-код</th><th>Размер</th><th>Кол-во</th><th>Себестоимость <img src="edit.png"></th></tr>';
	foreach ($result as $supply)
	{
		$totalQuantity+=(int)$supply['quantity'];
		$imgFolder=substr($supply['nmId'], 0, -4).'0000';
		$tableHt.='<tr>';
		$tableHt.='<td><a href="https://www.wildberries.ru/catalog/'.$supply['nmId'].'/detail.aspx" target="_blank"><div style="display: inline-block; width:50; height:50; background-size:contain; background-repeat:no-repeat; background-image:url(https://images.wbstatic.net/c246x328/new/'.$imgFolder.'/'.$supply['nmId'].'-1.jpg)"> </div></a></td>';
		$tableHt.='<td>'.formatDate($supply['date']).'</td>';
		$tableHt.='<td>'.$supply['nmId'].'</td>';
		$tableHt.='<td>'.$supply['barcode'].'</td>';
		$tableHt.='<td>'.$supply['techSize'].'</td>';
		$tableHt.='<td>'.$supply['quantity'].'</td>';
//		$tableHt.='<td><div id="incost'.$supply['id'].'" onclick="onIncostEditStart('.$supply['id'].');">'.(int)$supply['my_incost'].'</div><div id="editIncost'.$supply['id'].'" style="display:none;"><input id="incostEditInput'.$supply['id'].'" type="number" value="'.(int)$supply['my_incost'].'"><button onclick="onIncostEditEnd('.$supply['id'].')">OK</button></div></td>';
		$tableHt.='<td id="incost'.$supply['id'].'" onclick="editSupplyIncost('.$supply['id'].')">'.(int)$supply['my_incost'].'</td>';
		$tableHt.='</tr>';
	}
	$tableHt.='</table>';
	$ht.='<p>Общее количество: '.$totalQuantity.'</p>';
	$ht.=$tableHt;
	return $ht;
}




function getOrders() // Заказы
{
	global $aDb,$currentUser;
	$ht='';

	// Время обновления
	{
		$stmt = $aDb->prepare("SELECT orders_upd_time FROM accounts WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при получении времени обновления заказов: ".$stmt->errorInfo()[2]);
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$ht.='<p>Время обновления: '.substr($rows[0]['orders_upd_time'],0,16).'</p>';
	}

	$stmt = $aDb->prepare("SELECT orders.id, orders.date, orders.barcode, orders.nmId, orders.totalPrice, orders.discountPercent, CASE WHEN (sales.id>0) THEN 'Да' ELSE ' ' END AS purchased FROM orders LEFT JOIN sales ON orders.odid=sales.odid AND sales.account_id=:account_id WHERE orders.account_id=:account_id ORDER BY date DESC LIMIT 200;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении заказов: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ht.='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><th>Фото</th><th>Штрихкод</th><th>Выкуплен</th><th title="В цене не учтены дополнительные скидки вайлдберриз">Цена со скидкой</th></tr>';
	$groupDate='';
	$groupQuantity=0;
	$tr='';
	foreach ($result as $order)
	{
		$rowDate=formatDate($order['date']);
		if($groupDate!=$rowDate)
		{
			if(strlen($groupDate)>0)
			{
				$ht.='<tr><td colspan="5">'.$groupDate.' всего '.$groupQuantity.'</td></tr>';
				$ht.=$tr;
			}
			$groupDate=$rowDate;
			$tr='';
			$groupQuantity=0;
		}
		$imgFolder=substr($order['nmId'], 0, -4).'0000';
		$tr.='<tr>';
		$tr.='<td><a href="https://www.wildberries.ru/catalog/'.$order['nmId'].'/detail.aspx" target="_blank"><div style="display: inline-block; width:50; height:50; background-size:contain; background-repeat:no-repeat; background-image:url(https://images.wbstatic.net/c246x328/new/'.$imgFolder.'/'.$order['nmId'].'-1.jpg)"> </div></a></td>';
//		$tr.='<td>'.$order['quantity'].'</td>';
		$tr.='<td>'.$order['barcode'].'</td>';
		$tr.='<td>'.$order['purchased'].'</td>';
		$priceWithDiscount=round($order['totalPrice']-($order['totalPrice']*$order['discountPercent']/100));
		$tr.='<td>'.$priceWithDiscount.'</td>';
		$tr.='</tr>';
		$groupQuantity++;
	}
	$ht.='</table></div>';
	return $ht;
}



function getPlanInfo() // Инфо отчетов для терры
{
	$ht='<p>Выкупы отображаются по заказам за данный период (НЕ выкупы за данный период). По мере выкупа заказов, статистика за последние дни будет меняться. Такой подсчет даёт правильный процент выкупов.</p>';
	$ht.='<p>В столбцах &quot;Заказано&quot; и &quot;Выкупили&quot; суммы без учёта дополнительных скидок. Это связанно с тем, что вб показывает реальную цену продажи только после выкупа. Стоимость логистики вычисляется из реальной цены продажи и суммы к выплате.</p>';
	return $ht;
}



function getWeeklyPlan() // Отчет для терры
{
	global $aDb, $currentUser, $periodsCount;
	$ht='';

	$weeksArray = generateWeeksArray(10,0);//$weekOffset);
	$weeksArray=fillPeriods($weeksArray);
	\array_splice($weeksArray, 0, 1); // Убираем текущую неделю.
	for($i=0;$i<count($weeksArray);$i++)
	{
		$weeksArray[$i]['orderCount']=0;
		$weeksArray[$i]['orderSum']=0;
		$weeksArray[$i]['saleCount']=0;
		$weeksArray[$i]['saleSum']=0;
		$weeksArray[$i]['logisticSum']=0;
		$weeksArray[$i]['forPaySum']=0;
	}
	$startDateStr=$weeksArray[count($weeksArray)-1]['start'];

	$stmt = $aDb->prepare("SELECT orders.totalPrice, orders.discountPercent, orders.date, sales.id AS saleid, sales.my_selfpurchase, sales.finishedPrice, sales.forPay FROM orders LEFT JOIN sales ON orders.odid=sales.odid AND sales.account_id=:account_id WHERE orders.account_id=:account_id AND orders.date >= '".substr($startDateStr,0,10)."' ORDER BY orders.date DESC;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("getReport() Ошибка запроса к базе данных: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
//	$ht.='<p>rows: '.count($result).'</p>';

	$totalOrderSum=0;
	$totalOrderCount=0;
	$totalSaleSum=0;
	$totalSaleCount=0;
	$totalLogisticSum=0;
	$totalForPaySum=0;
	foreach ($result as $order)
	{
		if($order['my_selfpurchase']>0)
		{
			continue;
		}
		for($i=0;$i<count($weeksArray);$i++)
		{
			if($weeksArray[$i]['start'] <= $order['date'] && $weeksArray[$i]['end'] >= $order['date'])
			{
				$orderPrice=$order['totalPrice']*(100-$order['discountPercent'])/100;
				$weeksArray[$i]['orderSum']+=$orderPrice;
				$weeksArray[$i]['orderCount']++;
				$totalOrderSum+=$orderPrice;
				$totalOrderCount++;
				if($order['saleid'] >0)
				{
					$weeksArray[$i]['saleSum']+=$orderPrice;
					$weeksArray[$i]['saleCount']++;
					$totalSaleSum+=$orderPrice;
					$totalSaleCount++;
					$weeksArray[$i]['logisticSum']+=$order['finishedPrice']-$order['forPay'];
					$totalLogisticSum+=$order['finishedPrice']-$order['forPay'];
					$weeksArray[$i]['forPaySum']+=$order['forPay'];
					$totalForPaySum+=$order['forPay'];
//					$ht.='<p>finishedPrice: '.$order['finishedPrice'].' forPay: '.$order['forPay'].'</p>';
				}
			}
		}
//		$ht.='<p>sale id: '.$order['saleid'].'</p>';
	}
	$ht.='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><th>Период</th><th>Заказано, руб.</th><th>Заказано, шт.</th><th>Выкупили, руб.</th><th>Выкупили, шт.</th><th>Стоимость логистики</th><th>Итого к перечислению</th><th>% выкупа</th></tr>';
	foreach ($weeksArray as $period)
	{
		$ht.='<tr>';
		$ht.='<td>'.formatDate($period['start']).' - '.formatDate($period['end']).'</td>';
		$ht.='<td>'.round($period['orderSum']).'</td>';
		$ht.='<td>'.$period['orderCount'].'</td>';
		$ht.='<td>'.round($period['saleSum']).'</td>';
		$ht.='<td>'.$period['saleCount'].'</td>';
		$ht.='<td>'.round($period['logisticSum']).'</td>';
		$ht.='<td>'.round($period['forPaySum']).'</td>';
		$salePercent='-';
		if($period['orderSum']>0)//Защита от деления на ноль.
		{
			$salePercent=round($period['saleSum']/$period['orderSum']*100);
		}
		$ht.='<td>'.$salePercent.'</td>';
		$ht.='</tr>';
	}
	$ht.='<tr>';
	$ht.='<td><b>Итого</b></td>';
	$ht.='<td>'.round($totalOrderSum).'</td>';
	$ht.='<td>'.$totalOrderCount.'</td>';
	$ht.='<td>'.round($totalSaleSum).'</td>';
	$ht.='<td>'.$totalSaleCount.'</td>';
	$ht.='<td>'.round($totalLogisticSum).'</td>';
	$ht.='<td>'.round($totalForPaySum).'</td>';
	$totalSalePercent='-';
	if($totalOrderSum>0)//Защита от деления на ноль.
	{
		$totalSalePercent=round($totalSaleSum/$totalOrderSum*100);
	}
	$ht.='<td>'.$totalSalePercent.'</td>';
	$ht.='</tr>';
	$ht.='</table></div>';
	$ht.='<br>';
	return $ht;
}




function getDailyPlan($week) // 0 - текущая неделя, 1 - предыдущая
{
	global $aDb, $currentUser, $periodsCount;
	$ht='';

	$daysArray = generateDaysOfWeekArray($week);
	$daysArray=fillPeriods($daysArray);
//	\array_splice($daysArray, 0, 1); // Убираем текущий день.
	for($i=0;$i<count($daysArray);$i++)
	{
		$daysArray[$i]['orderCount']=0;
		$daysArray[$i]['orderSum']=0;
		$daysArray[$i]['saleCount']=0;
		$daysArray[$i]['saleSum']=0;
		$daysArray[$i]['logisticSum']=0;
		$daysArray[$i]['forPaySum']=0;
	}
	$startDateStr=$daysArray[0]['start'];
	
	$stmt = $aDb->prepare("SELECT orders.totalPrice, orders.discountPercent, orders.date, sales.id AS saleid, sales.my_selfpurchase, sales.finishedPrice, sales.forPay FROM orders LEFT JOIN sales ON orders.odid=sales.odid AND sales.account_id=:account_id WHERE orders.account_id=:account_id AND orders.date >= '".substr($startDateStr,0,10)."' ORDER BY orders.date DESC;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("getReport() Ошибка запроса к базе данных: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
//	$ht.='<p>rows: '.count($result).'</p>';

	$totalOrderSum=0;
	$totalOrderCount=0;
	$totalSaleSum=0;
	$totalSaleCount=0;
	$totalLogisticSum=0;
	$totalForPaySum=0;
	foreach ($result as $order)
	{
		if($order['my_selfpurchase']>0)
		{
			continue;
		}
		for($i=0;$i<count($daysArray);$i++)
		{
//			$ht.='<p>start '.$daysArray[$i]['start'].' end '.$daysArray[$i]['end'].' date '.$order['date'].'</p>';
			if($daysArray[$i]['start'] <= $order['date'] && $daysArray[$i]['end'] >= $order['date'])
			{
//				$ht.='<h3>FIT!</h3>';
				$orderPrice=$order['totalPrice']*(100-$order['discountPercent'])/100;
				$daysArray[$i]['orderSum']+=$orderPrice;
				$daysArray[$i]['orderCount']++;
				$totalOrderSum+=$orderPrice;
				$totalOrderCount++;
				if($order['saleid'] >0)
				{
					$daysArray[$i]['saleSum']+=$orderPrice;
					$daysArray[$i]['saleCount']++;
					$totalSaleSum+=$orderPrice;
					$totalSaleCount++;
					$daysArray[$i]['logisticSum']+=$order['finishedPrice']-$order['forPay'];
					$totalLogisticSum+=$order['finishedPrice']-$order['forPay'];
					$daysArray[$i]['forPaySum']+=$order['forPay'];
					$totalForPaySum+=$order['forPay'];
//					$ht.='<p>finishedPrice: '.$order['finishedPrice'].' forPay: '.$order['forPay'].'</p>';
				}
			}
		}
//		$ht.='<p>sale id: '.$order['saleid'].'</p>';
	}
	$ht.='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><th>Период</th><th>Заказано, руб.</th><th>Заказано, шт.</th><th>Выкупили, руб.</th><th>Выкупили, шт.</th><th>Стоимость логистики</th><th>Итого к перечислению</th><th>% выкупа</th></tr>';
	foreach ($daysArray as $period)
	{
		$ht.='<tr>';
		$ht.='<td>'.formatDate($period['start']).'</td>';
		$ht.='<td>'.round($period['orderSum']).'</td>';
		$ht.='<td>'.$period['orderCount'].'</td>';
		$ht.='<td>'.round($period['saleSum']).'</td>';
		$ht.='<td>'.$period['saleCount'].'</td>';
		$ht.='<td>'.round($period['logisticSum']).'</td>';
		$ht.='<td>'.round($period['forPaySum']).'</td>';
		$salePercent='-';
		if($period['orderSum']>0)//Защита от деления на ноль.
		{
			$salePercent=round($period['saleSum']/$period['orderSum']*100);
		}
		$ht.='<td>'.$salePercent.'</td>';
		$ht.='</tr>';
	}
	$ht.='<tr>';
	$ht.='<td><b>Итого</b></td>';
	$ht.='<td>'.round($totalOrderSum).'</td>';
	$ht.='<td>'.$totalOrderCount.'</td>';
	$ht.='<td>'.round($totalSaleSum).'</td>';
	$ht.='<td>'.$totalSaleCount.'</td>';
	$ht.='<td>'.round($totalLogisticSum).'</td>';
	$ht.='<td>'.round($totalForPaySum).'</td>';
	$totalSalePercent='-';
	if($totalOrderSum>0)//Защита от деления на ноль.
	{
		$totalSalePercent=round($totalSaleSum/$totalOrderSum*100);
	}
	$ht.='<td>'.$totalSalePercent.'</td>';
	$ht.='</tr>';
	$ht.='</table></div>';
	$ht.='<br>';
	return $ht;
}




function getSales() // Выкупы
{
	global $aDb,$currentUser;
	$ht='';

	// Время обновления
	{
		$stmt = $aDb->prepare("SELECT sales_upd_time FROM accounts WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при получении времени обновления выкупы: ".$stmt->errorInfo()[2]);
		}
		$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$ht.='<p>Время обновления: '.substr($rows[0]['sales_upd_time'],0,16).'</p>';
	}

	$ht.='<p>Прибыль = конечная цена - комиссия вайлдберриз - себестоимость - налог.</p>';
	$ht.='<p>Логистика, доставка, реклама, платная приёмка, штрафы и т.п. не учтены.</p>';

// Кнопка загрузки самовыкупов.
	$ht.='<label for="csvInput" class="w3-button w3-black" title="Файл таблицы в формате csv. В столбце G номер заказа, в столбце H стоимость самовыкупа.">Загрузить самовыкупы из csv</label><input id="csvInput" type="file" style="visibility:hidden;" onchange="importSelfPurchasesFromCSV(this)">';

	$stmt = $aDb->prepare("SELECT id, date, barcode, quantity, forPay, nmId, my_selfpurchase FROM sales WHERE account_id=:account_id ORDER BY date DESC LIMIT 200;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ht.='<div style="overflow: auto;white-space: nowrap;"><table class="w3-table-all"><tr><th>Фото</th><th>К выплате (прибыль)</th><th>Штрихкод</th></tr>';
	$groupDate='';
	$groupQuantity=0;
	$groupSum=0;
	$tr='';
	foreach ($result as $sale)
	{
		$rowDate=formatDate($sale['date']);
		if($groupDate!=$rowDate)
		{
			if(strlen($groupDate)>0)
			{
				$ht.='<tr><td colspan="5">'.$groupDate.' всего '.$groupQuantity.' на сумму '.$groupSum.'</td></tr>';
				$ht.=$tr;
			}
			$groupDate=$rowDate;
			$tr='';
			$groupQuantity=0;
			$groupSum=0;
		}
		$incost=lastSupplyIncost($sale['barcode']);
		$incostInfo='';
		if($incost>0)
		{
			$incostInfo=' ('.round($sale['forPay']-($incost+$sale['forPay']*$currentUser['tax_rate']/100)).')';
		}
		$imgFolder=substr($sale['nmId'], 0, -4).'0000';
		$rowColorStr='';
		if($sale['my_selfpurchase']==1)
		{
			$rowColorStr=' style="background-color: coral;" title="Самовыкуп"';
		}
		$tr.='<tr'.$rowColorStr.'>';
		$tr.='<td><a href="https://www.wildberries.ru/catalog/'.$sale['nmId'].'/detail.aspx" target="_blank"><div style="display: inline-block; width:50; height:50; background-size:contain; background-repeat:no-repeat; background-image:url(https://images.wbstatic.net/c246x328/new/'.$imgFolder.'/'.$sale['nmId'].'-1.jpg)"> </div></a></td>';
//		$tr.='<td>'.$sale['quantity'].'</td>';
		$tr.='<td>'.$sale['forPay'].$incostInfo.'</td>';
		$tr.='<td>'.$sale['barcode'].'</td>';
		$tr.='</tr>';
		if($sale['my_selfpurchase']==0)
		{
			$groupQuantity+=(int)$sale['quantity'];
			$groupSum+=(int)$sale['forPay'];
		}
	}
	$ht.='</table></div>';
	return $ht;
}




function getExpenses() // Расходы
{
	global $aDb,$currentUser;
$ht = <<<'EOT'
	<div id="newExpense" style="display:none">
		<p><label class="w3-text-grey">Сумма</label><input type="number" class="w3-input w3-border" id="expenseAmount"></p>
		<p><label class="w3-text-grey">Дата</label><input id="expenseDate"type="datetime-local" class="w3-input w3-border"></p>
		<p><label class="w3-text-grey">Примечания</label><input id="expenseNotes"type="text" class="w3-input w3-border"></p>
	</div>
	<button class="w3-button w3-black" onclick="onNewExpenseClicked()">Создать</button>
EOT;

	$stmt = $aDb->prepare("SELECT id, amount, notes, date FROM expenses WHERE account_id=:account_id ORDER BY date DESC;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$ht.='<table class="w3-table-all"><tr><th>Дата</th><th>Сумма <img src="edit.png"></th><th>Примечания</th></tr>';
	foreach ($result as $row)
	{
		$ht.='<tr>';
		$ht.='<td>'.formatDate($row['date']).'</td>';
//		$ht.='<td id="expenseAmount'.$row['id'].'"><span id="eaVal'.$row['id'].'">'.(int)$row['amount'].'</span> <img src="edit.png" onclick="editEA('.$row['id'].');"></td>';
		$ht.='<td id="expenseAmount'.$row['id'].'" onclick="editExpenseAmount('.$row['id'].')">'.(int)$row['amount'].'</td>';
		$ht.='<td>'.$row['notes'].'</td>';
		$ht.='</tr>';
//		$ht.='<p>';
//		$ht.=' дата '.$sale['date'].' сумма '.$sale['amount'].' '.$sale['notes'].'</p>';
//		error_log("column: ".$columnName." role: ".column["role"]." inputType: ".column["inputType"]);
	}
	return $ht;
}



/*function getPeriodButtons() // Профиль
{
	global $aDb,$currentUser;
$ht = <<<'EOT'
<div id="period">
<button onclick="onPeriodButtonClicked('week')">Неделя</button>
<button onclick="onPeriodButtonClicked('month')">Месяц</button>
<input id="periodStart"type="datetime-local" class="w3-input w3-border">
<input id="periodEnd"type="datetime-local" class="w3-input w3-border">
</div>
<script>
function onPeriodButtonClicked(period)
{
	if(period=='week')
	{
	}
	if(period=='month')
	{
	}
}
</script>
EOT;
	return $ht;
}*/




function getProfile() // Профиль
{
	global $aDb,$currentUser;
	$ht='';

// ACCOUNT INFO
	$tariffId=$currentUser['tariff_id'];
	$taxRate=$currentUser['tax_rate'];
	$balance=$currentUser['balance'];

	$ht.='<h3>'.$currentUser['name'].'</h3>';
// TARIFF
	$stmt = $aDb->prepare("SELECT id, name, price FROM tariffs WHERE active=1 OR id=:tariff_id;");
	$stmt->bindValue(":tariff_id", $tariffId, PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$ht.= '<p><label for="weekOffset">Тариф:</label> <select name="weekOffset" id="weekOffset" onchange="loadPageWithHistoryPush(\'?t=dashboard&weekoffset=\'+this.value)">';
	foreach ($result as $tariff)
	{
		$selected='';
		if($tariffId==$tariff['id'])
		{
			$selected=' selected';
		}
		$ht.='<option value="'.$tariff['id'].'"'.$selected.'>'.$tariff['name'].' ('.$tariff['price'].' ₽ / 30 дней)</option>';
	}
	$ht.='</select></p>';

	$ht.='<p>Баланс: '.$balance.' ₽</p>';
	$ht.='Пополнить: <button class="w3-button w3-black" onclick="onYandexCreatePayment('.$currentUser['id'].', 1000)">1000 ₽</button>';
//	$ht.='<button class="w3-button w3-black" onclick="onYandexCreatePayment('.$currentUser['id'].', 2)">2 ₽</button>';

// TAX RATE
//	<button id="changeTaxRateButton" class="w3-button w3-black" onclick="this.style='display:none;';el('taxRateBlock').style='display:block;';">Налоговая ставка</button>
$ht.= <<<EOT
<div class="w3-panel">
	<div id="taxRateBlock">
	<label class="w3-text-grey">Налоговая ставка, %</label><input type="number" class="w3-input w3-border" id="taxRateInput" value="$taxRate">
	<button onclick="ajax(addIdAndAuth({'_function':'updateTaxRate','value':el('taxRateInput').value}));">Сохранить</button>
	</div>
</div>

	<button id="changeWbToken1Button" class="w3-button w3-black" onclick="loadPageWithHistoryPush('?t=editWbToken');">Изменить токен wb</button>

<div class="w3-panel">
	<button id="changePasswordButton" class="w3-button w3-black" onclick="this.style='display:none;';el('passwordBlock').style='display:block;';">Изменить пароль</button>
	<div id="passwordBlock" style="display:none;">
	<label class="w3-text-grey">Пароль</label><input type="text" class="w3-input w3-border" id="passwordInput">
	<button onclick="ajax(addIdAndAuth({'_function':'updatePassword','value':el('passwordInput').value}));el('passwordBlock').style='display:none;';el('changePasswordButton').style='display:block;';">Сохранить</button>
	</div>
</div>

	<hr><button class="w3-button w3-black" onclick="onLogout()">Выход</button>
EOT;


/*<div class="w3-panel">
	<button id="changeWbToken1Button" class="w3-button w3-black" onclick="this.style='display:none;';el('wbTokenBlock').style='display:block;';">Изменить токен wb</button>
	<div id="wbTokenBlock" style="display:none;">
	<label class="w3-text-grey">Токен (<a href="https://seller.wildberries.ru/supplier-settings/access-to-api" target="_blank" rel="noopener">В лк wildberries: Профиль | Настройки | Доступ к API | Ключ для работы с API статистики x64</a>)</label><input type="text" class="w3-input w3-border" id="wbTokenInput">
	<button onclick="onUpdateToken();">Сохранить</button>
	</div>
</div>*/

	return $ht;
}




function editWbToken() // Токен
{
	global $aDb,$currentUser;
	$ht='';

$ht.= <<<EOT
<div class="w3-panel">
	<p>1. Скопируйте токен x64 в личном кабинете wildberries.</p>
	<p><a href="https://seller.wildberries.ru/supplier-settings/access-to-api" target="_blank" rel="noopener"><button class="w3-button w3-black">Перейти в личный кабинет wildberries</button></a></p>
	<p>2. Вставьте токен в поле ниже.</p>
	<label class="w3-text-grey">Токен</label><input type="text" class="w3-input w3-border" id="wbTokenInput">
	<p>3. Сохраните изменения.</p>
	<p><button class="w3-button w3-black" onclick="onUpdateToken();">Сохранить</button></p>
</div>


EOT;
//<video width="406" height="720" controls><source src="get_wb_token.mp4" type="video/mp4"></video>
	return $ht;
}




function getSupport() // Поддержка
{
$ht = <<<EOT
<br>
<button class="w3-button w3-padding-large w3-black w3-block" onclick="window.open('https://t.me/profitomer_support')">Чат поддержки в Telegram</button>
<br>
<h3>Начало работы с профитомером</h3>
<video width="406" height="720" controls><source src="start.mp4" type="video/mp4"></video>
<br>
EOT;
	return $ht;
}




function getAbout() // About
{
$ht = <<<EOT
<h1>Анализ продаж на Вайлдберриз</h1>
<ul>
	<li>Простой запуск за три шага</li>
	<li>Автоматическое получение данных от вб</li>
	<li>Считает чистую прибыль</li>
	<li>Показывает остатки по размерам и складам</li>
	<li>Показывает себестоимость всех остатков</li>
	<li>Хранит ваши расходы</li>
	<li>Хранит историю поставок</li>
	<li>Оперативная тех поддержка</li>
	<li>Быстрый и понятный интерфейс</li>
	<li>Подсказки и видеоуроки</li>
</ul>

<h3>Знакомство с сервисом profitomer.ru</h3>
<video width="1280" height="720" style="width:100%;height:auto;" class="w3-border" controls><source src="how_to_use_profitomer.mp4" type="video/mp4"></video>

<!--<div class="w3-container w3-padding-64">
	<img src="profitomer_dashboard_page.png" style="width:600px;">
</div>-->

<hr>

<div class="w3-row">
	<div class="w3-half w3-container">
		<div class="w3-card-4">
			<header class="w3-container w3-2021-green-ash">
				<h1>Тариф &quot;Базовый&quot;</h1>
			</header>
		
			<div class="w3-container">
				<ul>
					<li>Для одного ИП.</li>
				</ul>
			</div>
		
			<footer class="w3-container w3-2021-green-ash">
				<h5>1000 ₽ / 30 дней</h5>
			</footer>
		</div>
	</div>
</div>

<hr>


<br><br>
<div class="w3-container w3-padding-32">
	<h3>Реквизиты</h3>
	<p>ИП Кауфман В. С.</p>
	<p>ИНН 503002592143</p>
	<p>ОГРН 305503029100047</p>
	<p>Тел. +7 926 288 97 94</p>
</div>

<p><a href="https://profitomer.ru/eula.html">Пользовательское соглашение</a></p>

<br>
<br>
<br>
EOT;
	return $ht;
}


/*function updateAllStocksIncost() // Обновляет приходку для всех остатков.
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("SELECT barcode FROM stocks;");
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при поиске последней приходной цены: ".$stmt->errorInfo()[2]);
	}
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
	foreach ($result as $stock)
	{
		$barcode=$stock['barcode'];
		updateStocksIncost($barcode, lastSupplyIncost($barcode));
	}
}*/




function insertExpense($amount, $date, $notes) // Добавить расход
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("INSERT INTO expenses(account_id, amount, date, notes) VALUES (:account_id, :amount, :date, :notes);");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":amount", $amount, PDO::PARAM_INT);
	$stmt->bindValue(":date", $date, PDO::PARAM_STR);
	$stmt->bindValue(":notes", $notes, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при добавлении расхода: ".$stmt->errorInfo()[2]);
	}
}




/*function updateExpenseAmount($id, $amount) // Обновить сумму в расходе
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("UPDATE expenses SET amount = :amount WHERE account_id=:account_id AND id=:id;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":amount", $amount, PDO::PARAM_INT);
	$stmt->bindValue(":id", $id, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении суммы в расходе: ".$stmt->errorInfo()[2]);
	}
	return ['amount' => $amount];
}
*/



function updateStocksIncost($barcode, $incost) // Обновляпем себес в остатках.
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("UPDATE stocks SET my_incost=:my_incost WHERE account_id=:account_id AND barcode=:barcode;");
	$stmt->bindValue(":my_incost", $incost, PDO::PARAM_STR);
	$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении цены в остатках: ".$stmt->errorInfo()[2]);
	}
}




function update($table, $column, $rowId, $value) // Обновить поле в таблице
{
	global $aDb,$currentUser;
//	error_log("update table: ".$table." column: ".$column." row: ".$rowId);
	$stmt = $aDb->prepare('UPDATE '.$table.' SET '.$column.' = :value WHERE account_id=:account_id AND id=:rowId;');
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
//	$stmt->bindValue(":table", $table, PDO::PARAM_STR);
//	$stmt->bindValue(":column", $column, PDO::PARAM_STR);
	$stmt->bindValue(":value", $value, PDO::PARAM_INT);
	$stmt->bindValue(":rowId", $rowId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении поля: ".$stmt->errorInfo()[2]);
	}
	return ['value' => $value];
}




function updateSupplyIncost($id, $incost) // Обновляпем себес в поставке.
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("UPDATE supplies SET my_incost=:my_incost WHERE account_id=:account_id AND id=:id;");
	$stmt->bindValue(":my_incost", $incost, PDO::PARAM_STR);
	$stmt->bindValue(":id", $id, PDO::PARAM_INT);
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при обновлении цены в поставке: ".$stmt->errorInfo()[2]);
	}
	$barcode=supplyBarcode($id);
	updateStocksIncost($barcode, lastSupplyIncost($barcode));
}




function lastSupplyIncost($barcode) // Последняя приходная цена в поставках
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("SELECT my_incost FROM supplies WHERE account_id=:account_id AND barcode=:barcode ORDER BY incomeId DESC LIMIT 1;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":barcode", $barcode, PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при поиске последней приходной цены: ".$stmt->errorInfo()[2]);
	}
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	//print_r($results);
	if(!count($results))
	{
		return 0;
	}
	return (float)$results[0]['my_incost'];
}




function supplyBarcode($id) // Штрих-код
{
	global $aDb,$currentUser;
	$stmt = $aDb->prepare("SELECT barcode FROM supplies WHERE account_id=:account_id AND id=:id;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->bindValue(":id", $id, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		exitWithErrorTg("Ошибка запроса к базе данных при получении штрих-кода: ".$stmt->errorInfo()[2]);
	}
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	//print_r($results);
	if(!count($results))
	{
		return 0;
	}
	return $results[0]['barcode'];
}


// ЗАГРУЗКА В БАЗУ ДАННЫХ ИЗ ВАЙЛДБЕРРИЗ ПО АПИ.

function insertToDbSupplies() // Поставки
{
	global $aDb,$currentUser;
	$result = json_decode(curlWbStatisticsGet('supplier/incomes?dateFrom=2017-03-25T21%3A00%3A00.000Z'),true);
	if(!is_array($result) || !isset($result[0]))
	{
		return '<p>Не удалось обновить поставки.</p>';
	}
	
	foreach ($result as $supply)
	{
		if(!array_key_exists('incomeId',$supply)) // Проверка на наличие данных.
		{
			continue;
		}
		// Проверяем совпанение incomeId, barcode и techSize. Если есть - обновляем статус. Если нет - добавляем.
		$stmt = $aDb->prepare("SELECT id FROM supplies WHERE account_id=:account_id AND incomeId=:incomeId AND barcode=:barcode AND techSize=:techSize;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":incomeId", $supply['incomeId'], PDO::PARAM_INT);
		$stmt->bindValue(":barcode", $supply['barcode'], PDO::PARAM_STR);
		$stmt->bindValue(":techSize", $supply['techSize'], PDO::PARAM_STR);
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		//print_r($results);
		if(count($results)) // Обновляем статус
		{
			$stmt = $aDb->prepare("UPDATE supplies SET lastChangeDate=:lastChangeDate, status=:status WHERE id=:id AND account_id=:account_id;");
			$stmt->bindValue(":lastChangeDate", $supply['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":status", $supply['status'], PDO::PARAM_STR);
			$stmt->bindValue(":id", $results[0]['id'], PDO::PARAM_INT);
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
			if(!$stmt->execute())
			{
				exitWithErrorTg("Ошибка запроса к базе данных при загрузке поставок: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
			}
		}
		else // Новая запись
		{
			$my_incost=lastSupplyIncost($supply['barcode']); // Берём себестоимость из последней поставки.
/*			if($my_incost>0)
			{
				error_log("set incost: ".$my_incost." barcode: ".$supply['barcode']);
			}*/
			$stmt = $aDb->prepare("INSERT INTO supplies(account_id, incomeId, date, lastChangeDate, supplierArticle, techSize, barcode, quantity, totalPrice, dateClose, warehouseName, nmId, status, my_incost) VALUES (:account_id, :incomeId, :date, :lastChangeDate, :supplierArticle, :techSize, :barcode, :quantity, :totalPrice, :dateClose, :warehouseName, :nmId, :status, :my_incost);");
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
			$stmt->bindValue(":incomeId", $supply['incomeId'], PDO::PARAM_INT);
			$stmt->bindValue(":date", $supply['date'], PDO::PARAM_STR);
			$stmt->bindValue(":lastChangeDate", $supply['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":supplierArticle", $supply['supplierArticle'], PDO::PARAM_STR);
			$stmt->bindValue(":techSize", $supply['techSize'], PDO::PARAM_STR);
			$stmt->bindValue(":barcode", $supply['barcode'], PDO::PARAM_STR);
			$stmt->bindValue(":quantity", $supply['quantity'], PDO::PARAM_STR);
			$stmt->bindValue(":totalPrice", $supply['totalPrice'], PDO::PARAM_STR);
			$stmt->bindValue(":dateClose", $supply['dateClose'], PDO::PARAM_STR);
			$stmt->bindValue(":warehouseName", $supply['warehouseName'], PDO::PARAM_STR);
			$stmt->bindValue(":nmId", $supply['nmId'], PDO::PARAM_INT);
			$stmt->bindValue(":status", $supply['status'], PDO::PARAM_STR);
			$stmt->bindValue(":my_incost", $my_incost, PDO::PARAM_STR);
			if(!$stmt->execute())
			{
				exitWithErrorTg("Ошибка запроса к базе данных при загрузке поставок: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
			}
		}
	}
	// Обновляем время обновления.
	{
		$stmt = $aDb->prepare("UPDATE accounts SET supplies_upd_time=:timestamp WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":timestamp", date('Y-m-d H:i:s.00'), PDO::PARAM_STR);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке продаж: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
		}
	}
	return '<p>Поставки обновлены.</p>';
}




//
function insertToDbStocks() // Остатки
{
	global $aDb,$currentUser;
	$result = json_decode(curlWbStatisticsGet('supplier/stocks?dateFrom=2017-03-25T21%3A00%3A00.000Z'),true);
	if(!is_array($result) || !isset($result[0]))
	{
		return '<p>Не удалось обновить остатки.</p>';
	}
	// Чистим предыдущий склад
/*	$stmt = $aDb->prepare("DELETE FROM stocks WHERE account_id=:account_id;");
	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
	$stmt->execute();*/
	{
		$stmt = $aDb->prepare("UPDATE stocks SET my_updated=0 WHERE account_id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке поставок: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
		}
	}
	$updatedCnt=0;
	$insertedCnt=0;
	foreach ($result as $stock)
	{
		if(!array_key_exists('lastChangeDate',$stock)) // Проверка на наличие данных.
		{
			continue;
		}
/*		if($stock['isSupply']!=true)// Загружаем только отмеченные как договор поставки.
		{
			continue;
		}*/
		// Проверяем совпанение nmId, barcode и warehouse. Если есть - обновляем статус. Если нет - добавляем.
		$stmt = $aDb->prepare("SELECT id FROM stocks WHERE account_id=:account_id AND nmId=:nmId AND barcode=:barcode AND warehouse=:warehouse AND isSupply=:isSupply;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":nmId", $stock['nmId'], PDO::PARAM_INT);
		$stmt->bindValue(":barcode", $stock['barcode'], PDO::PARAM_STR);
		$stmt->bindValue(":warehouse", $stock['warehouse'], PDO::PARAM_INT);
		$stmt->bindValue(":isSupply", $stock['isSupply'], PDO::PARAM_STR);
		$stmt->execute();
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		//print_r($results);
		if(count($results)) // Обновляем статус
		{
			$stmt = $aDb->prepare("UPDATE stocks SET lastChangeDate=:lastChangeDate, supplierArticle=:supplierArticle, quantity=:quantity, quantityFull=:quantityFull, quantityNotInOrders=:quantityNotInOrders, inWayToClient=:inWayToClient, inWayFromClient=:inWayFromClient, subject=:subject, category=:category, daysOnSite=:daysOnSite, brand=:brand, SCCode=:SCCode, Price=:Price, Discount=:Discount, my_updated=1 WHERE id=:id AND account_id=:account_id;");
			$stmt->bindValue(":lastChangeDate", $stock['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":supplierArticle", $stock['supplierArticle'], PDO::PARAM_STR);
			$stmt->bindValue(":quantity", $stock['quantity'], PDO::PARAM_INT);
			$stmt->bindValue(":quantityFull", $stock['quantityFull'], PDO::PARAM_INT);
			$stmt->bindValue(":quantityNotInOrders", $stock['quantityNotInOrders'], PDO::PARAM_INT);
			$stmt->bindValue(":inWayToClient", $stock['inWayToClient'], PDO::PARAM_INT);
			$stmt->bindValue(":inWayFromClient", $stock['inWayFromClient'], PDO::PARAM_INT);
			$stmt->bindValue(":subject", $stock['subject'], PDO::PARAM_STR);
			$stmt->bindValue(":category", $stock['category'], PDO::PARAM_STR);
			$stmt->bindValue(":daysOnSite", $stock['daysOnSite'], PDO::PARAM_INT);
			$stmt->bindValue(":brand", $stock['brand'], PDO::PARAM_STR);
			$stmt->bindValue(":SCCode", $stock['SCCode'], PDO::PARAM_STR);
			$stmt->bindValue(":Price", $stock['Price'], PDO::PARAM_STR);
			$stmt->bindValue(":Discount", $stock['Discount'], PDO::PARAM_STR);
			$stmt->bindValue(":id", $results[0]['id'], PDO::PARAM_INT);
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
			if(!$stmt->execute())
			{
				exitWithErrorTg("Ошибка запроса к базе данных при загрузке поставок: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
			}
			$updatedCnt++;
		}
		else // Новая запись
		{
			$my_incost=lastSupplyIncost($supply['barcode']); // Берём себестоимость из последней поставки.
			$stmt = $aDb->prepare("INSERT INTO stocks(account_id, lastChangeDate, supplierArticle, techSize, barcode, quantity, isSupply, isRealization, quantityFull, quantityNotInOrders, warehouse, warehouseName, inWayToClient, inWayFromClient, nmId, subject, category, daysOnSite, brand, SCCode, Price, Discount, my_incost, my_updated) VALUES (:account_id, :lastChangeDate, :supplierArticle, :techSize, :barcode, :quantity, :isSupply, :isRealization, :quantityFull, :quantityNotInOrders, :warehouse, :warehouseName, :inWayToClient, :inWayFromClient, :nmId, :subject, :category, :daysOnSite, :brand, :SCCode, :Price, :Discount, :my_incost, 1);");
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
			$stmt->bindValue(":lastChangeDate", $stock['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":supplierArticle", $stock['supplierArticle'], PDO::PARAM_STR);
			$stmt->bindValue(":techSize", $stock['techSize'], PDO::PARAM_STR);
			$stmt->bindValue(":barcode", $stock['barcode'], PDO::PARAM_STR);
			$stmt->bindValue(":quantity", $stock['quantity'], PDO::PARAM_INT);
			$stmt->bindValue(":isSupply", $stock['isSupply'], PDO::PARAM_INT);
			$stmt->bindValue(":isRealization", $stock['isRealization'], PDO::PARAM_INT);
			$stmt->bindValue(":quantityFull", $stock['quantityFull'], PDO::PARAM_INT);
			$stmt->bindValue(":quantityNotInOrders", $stock['quantityNotInOrders'], PDO::PARAM_INT);
			$stmt->bindValue(":warehouse", $stock['warehouse'], PDO::PARAM_INT);
			$stmt->bindValue(":warehouseName", $stock['warehouseName'], PDO::PARAM_STR);
			$stmt->bindValue(":inWayToClient", $stock['inWayToClient'], PDO::PARAM_INT);
			$stmt->bindValue(":inWayFromClient", $stock['inWayFromClient'], PDO::PARAM_INT);
			$stmt->bindValue(":nmId", $stock['nmId'], PDO::PARAM_INT);
			$stmt->bindValue(":subject", $stock['subject'], PDO::PARAM_STR);
			$stmt->bindValue(":category", $stock['category'], PDO::PARAM_STR);
			$stmt->bindValue(":daysOnSite", $stock['daysOnSite'], PDO::PARAM_INT);
			$stmt->bindValue(":brand", $stock['brand'], PDO::PARAM_STR);
			$stmt->bindValue(":SCCode", $stock['SCCode'], PDO::PARAM_STR);
			$stmt->bindValue(":Price", $stock['Price'], PDO::PARAM_STR);
			$stmt->bindValue(":Discount", $stock['Discount'], PDO::PARAM_STR);
			$stmt->bindValue(":my_incost", $my_incost, PDO::PARAM_STR);
			if(!$stmt->execute())
			{
				exitWithError("Ошибка запроса к базе данных при загрузке склада: ".$stmt->errorInfo()[2],6);
			}
			$insertedCnt++;
		}
	}
	// Ищем сколько не обновилось.
	$removedCnt=0;
	{
		$stmt = $aDb->prepare("SELECT COUNT(id) AS not_updated FROM stocks WHERE account_id=:account_id AND my_updated=0;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке поставок: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
		}
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$removedCnt=$results[0]['not_updated'];
	}
	{
		$stmt = $aDb->prepare("DELETE FROM stocks WHERE account_id=:account_id AND my_updated=0;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке поставок: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
		}
	}
	// Обновляем время обновления.
	{
		$stmt = $aDb->prepare("UPDATE accounts SET stocks_upd_time=:timestamp WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":timestamp", date('Y-m-d H:i:s.00'), PDO::PARAM_STR);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке поставок: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
		}
	}
	
	
	return '<p>Остатки обновлены. Добавлено: '.$insertedCnt.', обновлено: '.$updatedCnt.', удалено: '.$removedCnt.'.</p>';
}





function insertToDbOrders() // Заказы
{
	global $aDb,$currentUser;
	$result = json_decode(curlWbStatisticsGet('supplier/orders?dateFrom=2017-03-25T21%3A00%3A00.000Z'),true);
	if(!is_array($result) || !isset($result[0]))
	{
		return '<p>Не удалось обновить заказы.</p>';
	}
	// Чистим предыдущие продажи
//	$stmt = $aDb->prepare("DELETE FROM sales WHERE account_id=:account_id;");
//	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
//	$stmt->execute();
	$updatedCnt=0;
	$insertedCnt=0;
	foreach ($result as $order)
	{
		if(!array_key_exists('lastChangeDate',$order)) // Проверка на наличие данных.
		{
			continue;
		}
		// Проверяем совпанение incomeId, barcode и techSize. Если есть - обновляем статус. Если нет - добавляем.
		$stmt = $aDb->prepare("SELECT id FROM orders WHERE account_id=:account_id AND odid=:odid;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":odid", $order['odid'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке заказов: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
		}
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		//print_r($results);
		if(count($results)) // Обновляем статус
		{
			$stmt = $aDb->prepare("UPDATE orders SET lastChangeDate=:lastChangeDate, isCancel=:isCancel, cancel_dt=:cancel_dt WHERE id=:id AND account_id=:account_id;");
			$stmt->bindValue(":lastChangeDate", $order['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":isCancel", $order['isCancel'], PDO::PARAM_INT);
			$stmt->bindValue(":cancel_dt", $order['cancel_dt'], PDO::PARAM_STR);
			$stmt->bindValue(":id", $results[0]['id'], PDO::PARAM_INT);
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
			if(!$stmt->execute())
			{
				exitWithErrorTg("Ошибка запроса к базе данных при загрузке заказов: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
			}
			$updatedCnt++;
		}
		else // Новая запись
		{
			$stmt = $aDb->prepare("INSERT INTO orders(account_id, date, lastChangeDate, supplierArticle, techSize, barcode, totalPrice, discountPercent, warehouseName, oblast, incomeID, odid, nmId, subject, category, brand, isCancel, cancel_dt, gNumber, sticker) VALUES (:account_id, :date, :lastChangeDate, :supplierArticle, :techSize, :barcode, :totalPrice, :discountPercent, :warehouseName, :oblast, :incomeID, :odid, :nmId, :subject, :category, :brand, :isCancel, :cancel_dt, :gNumber, :sticker);");
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
			$stmt->bindValue(":date", $order['date'], PDO::PARAM_STR);
			$stmt->bindValue(":lastChangeDate", $order['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":supplierArticle", $order['supplierArticle'], PDO::PARAM_STR);
			$stmt->bindValue(":techSize", $order['techSize'], PDO::PARAM_STR);
			$stmt->bindValue(":barcode", $order['barcode'], PDO::PARAM_STR);
			$stmt->bindValue(":totalPrice", $order['totalPrice'], PDO::PARAM_STR);
			$stmt->bindValue(":discountPercent", $order['discountPercent'], PDO::PARAM_STR);
			$stmt->bindValue(":warehouseName", $order['warehouseName'], PDO::PARAM_STR);
			$stmt->bindValue(":oblast", $order['oblast'], PDO::PARAM_STR);
			$stmt->bindValue(":incomeID", $order['incomeID'], PDO::PARAM_INT);
			$stmt->bindValue(":odid", $order['odid'], PDO::PARAM_INT);
			$stmt->bindValue(":nmId", $order['nmId'], PDO::PARAM_INT);
			$stmt->bindValue(":subject", $order['subject'], PDO::PARAM_STR);
			$stmt->bindValue(":category", $order['category'], PDO::PARAM_INT);
			$stmt->bindValue(":brand", $order['brand'], PDO::PARAM_STR);
			$stmt->bindValue(":isCancel", $order['isCancel'], PDO::PARAM_INT);
			$stmt->bindValue(":cancel_dt", $order['cancel_dt'], PDO::PARAM_STR);
			$stmt->bindValue(":gNumber", $order['gNumber'], PDO::PARAM_STR);
			$stmt->bindValue(":sticker", $order['sticker'], PDO::PARAM_STR);
			if(!$stmt->execute())
			{
				exitWithErrorTg("Ошибка запроса к базе данных при загрузке заказов: ".$stmt->errorInfo()[2]);
			}
			$insertedCnt++;
		}
	}
	// Обновляем время обновления.
	{
		$stmt = $aDb->prepare("UPDATE accounts SET orders_upd_time=:timestamp WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":timestamp", date('Y-m-d H:i:s.00'), PDO::PARAM_STR);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке продаж: ".$stmt->errorInfo()[2]);
		}
	}
	return '<p>Заказы обновлены. Добавлено: '.$insertedCnt.', обновлено: '.$updatedCnt.'.</p>';
}





function insertToDbSales() // Выкупы
{
	global $aDb,$currentUser;
	$result = json_decode(curlWbStatisticsGet('supplier/sales?dateFrom=2017-03-25T21%3A00%3A00.000Z'),true);
	if(!is_array($result) || !isset($result[0]))
	{
		return '<p>Не удалось обновить продажи.</p>';
	}
	// Чистим предыдущие продажи
//	$stmt = $aDb->prepare("DELETE FROM sales WHERE account_id=:account_id;");
//	$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
//	$stmt->execute();
	$updatedCnt=0;
	$insertedCnt=0;
	foreach ($result as $sale)
	{
		if(!array_key_exists('lastChangeDate',$sale)) // Проверка на наличие данных.
		{
			continue;
		}
		// Проверяем совпанение incomeId, barcode и techSize. Если есть - обновляем статус. Если нет - добавляем.
		$stmt = $aDb->prepare("SELECT id FROM sales WHERE account_id=:account_id AND odid=:odid;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":odid", $sale['odid'], PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке выкупов: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
		}
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		//print_r($results);
		$quantity=1;
		if($sale['forPay']<0) // Если возврат
		{
			$quantity=-1;
		}
		if(count($results)) // Обновляем статус
		{
			$stmt = $aDb->prepare("UPDATE sales SET lastChangeDate=:lastChangeDate, IsStorno=:IsStorno, totalPrice=:totalPrice, quantity=:quantity, forPay=:forPay WHERE id=:id AND account_id=:account_id;");
			$stmt->bindValue(":lastChangeDate", $sale['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":IsStorno", $sale['IsStorno'], PDO::PARAM_INT);
			$stmt->bindValue(":totalPrice", $sale['totalPrice'], PDO::PARAM_STR);
			$stmt->bindValue(":quantity", $quantity, PDO::PARAM_INT);
			$stmt->bindValue(":forPay", $sale['forPay'], PDO::PARAM_STR);

			$stmt->bindValue(":id", $results[0]['id'], PDO::PARAM_INT);
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);

			if(!$stmt->execute())
			{
				exitWithErrorTg("Ошибка запроса к базе данных при загрузке выкупов: ".$stmt->errorInfo()[2],"На сервере произошла ошибка.");
			}
			$updatedCnt++;
		}
		else // Новая запись
		{
			$stmt = $aDb->prepare("INSERT INTO sales(account_id, date, lastChangeDate, supplierArticle, techSize, barcode, quantity, totalPrice, discountPercent, isSupply, isRealization, orderId, promoCodeDiscount, warehouseName, countryName, oblastOkrugName, regionName, incomeID, saleID, odid, spp, forPay, finishedPrice, priceWithDisc, nmId, subject, category, brand, IsStorno, gNumber, sticker) VALUES (:account_id, :date, :lastChangeDate, :supplierArticle, :techSize, :barcode, :quantity, :totalPrice, :discountPercent, :isSupply, :isRealization, :orderId, :promoCodeDiscount, :warehouseName, :countryName, :oblastOkrugName, :regionName, :incomeID, :saleID, :odid, :spp, :forPay, :finishedPrice, :priceWithDisc, :nmId, :subject, :category, :brand, :IsStorno, :gNumber, :sticker);");
			$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
			$stmt->bindValue(":date", $sale['date'], PDO::PARAM_STR);
			$stmt->bindValue(":lastChangeDate", $sale['lastChangeDate'], PDO::PARAM_STR);
			$stmt->bindValue(":supplierArticle", $sale['supplierArticle'], PDO::PARAM_STR);
			$stmt->bindValue(":techSize", $sale['techSize'], PDO::PARAM_STR);
			$stmt->bindValue(":barcode", $sale['barcode'], PDO::PARAM_STR);
			$stmt->bindValue(":quantity", $quantity, PDO::PARAM_INT);
			$stmt->bindValue(":totalPrice", $sale['totalPrice'], PDO::PARAM_STR);
			$stmt->bindValue(":discountPercent", $sale['discountPercent'], PDO::PARAM_STR);
			$stmt->bindValue(":isSupply", $sale['isSupply'], PDO::PARAM_INT);
			$stmt->bindValue(":isRealization", $sale['isRealization'], PDO::PARAM_INT);
			$stmt->bindValue(":orderId", 0, PDO::PARAM_INT);//$sale['orderId'], PDO::PARAM_INT);
			$stmt->bindValue(":promoCodeDiscount", $sale['promoCodeDiscount'], PDO::PARAM_STR);
			$stmt->bindValue(":warehouseName", $sale['warehouseName'], PDO::PARAM_STR);
			$stmt->bindValue(":countryName", $sale['countryName'], PDO::PARAM_STR);
			$stmt->bindValue(":oblastOkrugName", $sale['oblastOkrugName'], PDO::PARAM_STR);
			$stmt->bindValue(":regionName", $sale['regionName'], PDO::PARAM_STR);
			$stmt->bindValue(":incomeID", $sale['incomeID'], PDO::PARAM_INT);
			$stmt->bindValue(":saleID", $sale['saleID'], PDO::PARAM_INT);
			$stmt->bindValue(":odid", $sale['odid'], PDO::PARAM_INT);
			$stmt->bindValue(":spp", $sale['spp'], PDO::PARAM_STR);
			$stmt->bindValue(":forPay", $sale['forPay'], PDO::PARAM_STR);
			$stmt->bindValue(":finishedPrice", $sale['finishedPrice'], PDO::PARAM_STR);
			$stmt->bindValue(":priceWithDisc", $sale['priceWithDisc'], PDO::PARAM_STR);
			$stmt->bindValue(":nmId", $sale['nmId'], PDO::PARAM_INT);
			$stmt->bindValue(":subject", $sale['subject'], PDO::PARAM_STR);
			$stmt->bindValue(":category", $sale['category'], PDO::PARAM_INT);
			$stmt->bindValue(":brand", $sale['brand'], PDO::PARAM_STR);
			$stmt->bindValue(":IsStorno", $sale['IsStorno'], PDO::PARAM_INT);
			$stmt->bindValue(":gNumber", $sale['gNumber'], PDO::PARAM_STR);
			$stmt->bindValue(":sticker", $sale['sticker'], PDO::PARAM_STR);
			if(!$stmt->execute())
			{
				exitWithErrorTg("Ошибка запроса к базе данных при загрузке выкупов: ".$stmt->errorInfo()[2]);
			}
			$insertedCnt++;
		}
	}
	// Обновляем время обновления.
	{
		$stmt = $aDb->prepare("UPDATE accounts SET sales_upd_time=:timestamp WHERE id=:account_id;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":timestamp", date('Y-m-d H:i:s.00'), PDO::PARAM_STR);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при загрузке выкупов: ".$stmt->errorInfo()[2]);
		}
	}
	return '<p>Выкупы обновлены. Добавлено: '.$insertedCnt.', обновлено: '.$updatedCnt.'.</p>';
}




function importSelfPurchasesFromCSV($csv) // Помечает записи в таблице продаж как самовыкупы, если номер заказа найден в в файле самовыкупов.
{
	global $aDb,$currentUser;
	$ht='';
//	$ht.='<pre>'.$csv.'</pre>';
	$lines = preg_split("/\r\n|\n|\r/", $csv);
	$data=[];
	for($lineIndex=0;$lineIndex<count($lines);$lineIndex++)
	{
		$data[]=str_getcsv($lines[$lineIndex]);
	}
	$selfPurchasesCount=0;
	$selfPurchasesUpdatedCount=0;
	for($rowIndex=0;$rowIndex<count($data);$rowIndex++)
	{
		$row=$data[$rowIndex];
//		$ht.='<p>Строка '.$rowIndex.': '.print_r($row, true);
		// Проверки.
		if(count($row)<7)
		{
//			$ht.='<p>Строка '.$rowIndex.'. Отсутствует шестой столбец.</p>';
			continue;
		}
		$orderId=$row[6];
		if(strlen($orderId<11))
		{
//			$ht.='<p>Строка '.$rowIndex.'. Слишком мало символов в номере заказа: '.$orderId.'</p>';
			continue;
		}
		$orderId=(int)$orderId;
		if($orderId<=0)
		{
//			$ht.='<p>Строка '.$rowIndex.'. Нулевой или отрицательный номер заказа.</p>';
			continue;
		}
		// Ищем стоимость самовыкупа в столбце 7 (J).
		$selfPurchaseCost=1;
		if(count($row)>=8)
		{
			$tmpCost=(int)$row[7];
			if($tmpCost>0 && $tmpCost<1000)
			{
				$selfPurchaseCost=$tmpCost;
			}
		}
		$selfPurchasesCount++;
		// Ищем заказ.
		// Проверяем совпанение nmId, barcode и warehouse. Если есть - обновляем статус. Если нет - добавляем.
		$stmt = $aDb->prepare("SELECT id FROM sales WHERE account_id=:account_id AND odid=:odid;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":odid", $orderId, PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при импорте самовыкупов: ".$stmt->errorInfo()[2]);
		}
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($results)) // Найден самовыкуп.
		{
			update('sales', 'my_selfpurchase', $results[0]['id'], $selfPurchaseCost);
			$selfPurchasesUpdatedCount++;
			
		}
		else
		{
			$ht.='<p>Строка '.$rowIndex.'. Заказ не найден: '.$orderId.'</p>';
		}
	}
	$ht.='<p>Самовыкупы импортированы. Загружено: '.$selfPurchasesCount.', обновлено: '.$selfPurchasesUpdatedCount.'</p>';
	return $ht;
}




function importWeekReportFromCSV($csv) // Помечает записи в таблице продаж как самовыкупы, если номер заказа найден в в файле самовыкупов.
{
	global $aDb,$currentUser;
	$ht='';
//	$ht.='<pre>'.$csv.'</pre>';
	$lines = preg_split("/\r\n|\n|\r/", $csv);
	$data=[];
	for($lineIndex=0;$lineIndex<count($lines);$lineIndex++)
	{
		$data[]=str_getcsv($lines[$lineIndex]);
	}
	for($rowIndex=1;$rowIndex<count($data);$rowIndex++)
	{
		$row=$data[$rowIndex];
//		$ht.='<p>Строка '.$rowIndex.': '.print_r($row, true);
		// Проверки.
		if(count($row)<45)
		{
//			$ht.='<p>Строка '.$rowIndex.'. Отсутствует шестой столбец.</p>';
			continue;
		}
		$reportId=$row[0];
		$supplyId=$row[1];

		if(strlen($orderId<11))
		{
//			$ht.='<p>Строка '.$rowIndex.'. Слишком мало символов в номере заказа: '.$orderId.'</p>';
			continue;
		}
		$orderId=(int)$orderId;
		if($orderId<=0)
		{
//			$ht.='<p>Строка '.$rowIndex.'. Нулевой или отрицательный номер заказа.</p>';
			continue;
		}
		// Ищем стоимость самовыкупа в столбце 7 (J).
		$selfPurchaseCost=1;
		if(count($row)>=45)
		{
			$tmpCost=(int)$row[7];
			if($tmpCost>0 && $tmpCost<1000)
			{
				$selfPurchaseCost=$tmpCost;
			}
		}
		$selfPurchasesCount++;
		// Ищем заказ.
		// Проверяем совпанение nmId, barcode и warehouse. Если есть - обновляем статус. Если нет - добавляем.
		$stmt = $aDb->prepare("SELECT id FROM sales WHERE account_id=:account_id AND odid=:odid;");
		$stmt->bindValue(":account_id", $currentUser['id'], PDO::PARAM_INT);
		$stmt->bindValue(":odid", $orderId, PDO::PARAM_INT);
		if(!$stmt->execute())
		{
			exitWithErrorTg("Ошибка запроса к базе данных при импорте самовыкупов: ".$stmt->errorInfo()[2]);
		}
		$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if(count($results)) // Найден самовыкуп.
		{
			update('sales', 'my_selfpurchase', $results[0]['id'], $selfPurchaseCost);
			$selfPurchasesUpdatedCount++;
			
		}
		else
		{
			$ht.='<p>Строка '.$rowIndex.'. Заказ не найден: '.$orderId.'</p>';
		}
	}
	$ht.='<p>Самовыкупы импортированы. Загружено: '.$selfPurchasesCount.', обновлено: '.$selfPurchasesUpdatedCount.'</p>';
	return $ht;
}




function addSqlFilter($newCondition,$initialSqlFilter)
{
	if(strlen($initialSqlFilter)==0)
	{
		return " WHERE ".$newCondition;
	}
	return " AND ".$newCondition;
}


/*

function mainMenu()
{
$ht = <<<'EOT'
	<button id="mainMenu-dashboard" name="mainMenu" class="w3-button" onclick="loadPageWithHistoryPush('?t=dashboard')">Дашборд</button>
	<button id="mainMenu-sales" name="mainMenu" class="w3-button" onclick="loadPageWithHistoryPush('?t=sales')">Продажи</button>
	<button id="mainMenu-stock" name="mainMenu" class="w3-button" onclick="loadPageWithHistoryPush('?t=stock')">Остатки</button>
	<button id="mainMenu-supplies" name="mainMenu" class="w3-button" onclick="loadPageWithHistoryPush('?t=supplies')">Поставки</button>
	<button id="mainMenu-expenses" name="mainMenu" class="w3-button" onclick="loadPageWithHistoryPush('?t=expenses')">Расходы</button>
	<button id="mainMenu-load" name="mainMenu" class="w3-button" onclick="loadPageWithHistoryPush('?t=load')">Загрузить данные</button>
	<button id="mainMenu-profile" name="mainMenu" class="w3-button" onclick="loadPageWithHistoryPush('?t=profile')">Профиль</button>
	<button name="mainMenu" class="w3-button" onclick="window.open('https://t.me/profitomer_support')">Поддержка</button>
EOT;
	return $ht;
}

*/







// O T H E R

function periodDateStrings($periodName)
{
	if($periodName=='week')
	{
	}
}



function timestampToMonthYearString($timestamp)
{
	$monthNames = ["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"];
	$monthNum=(int)substr($timestamp,5,2);
	return $monthNames[$monthNum-1]." ".substr($timestamp,0,4);
}

function formatDate($timestamp)
{
	$year=substr($timestamp,2,2);
	$month=substr($timestamp,5,2);
	$day=substr($timestamp,8,2);
	return $day.'.'.$month.'.'.$year;
}


?>
