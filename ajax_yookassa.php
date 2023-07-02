<?php

//---------------------------------------------------------------------------------------
// Y O O K A S S A
//---------------------------------------------------------------------------------------


require_once __DIR__.'/yookassa_lib/autoload.php';
use YooKassa\Client;
use YooKassa\Model\NotificationEventType;

require_once __DIR__.'/ajax_yookassa_config.php';
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';

// Получаем json из post-запроса
header('Content-type: application/json');
$jsonRequest = file_get_contents('php://input');
$request = json_decode($jsonRequest, true);

if(is_array($request) && array_key_exists("_function",$request))
{
	$response = [];
	$response["_id"]=$request["_id"];
	$response["_function"]=$request["_function"];
}


//error_log("ajax_yookassa.php request: ".$_SERVER['REQUEST_URI'], 0);

if(array_key_exists("f",$_GET) && $_GET['f']=='yookassa_notification')
{
	error_log("yookassa notification received: ".$_GET['f'], 0);
//	file_put_contents("/var/www/profitomer.ru_data/log/yookassa_log.txt",json_encode($request,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
	
	if(!array_key_exists("event",$request))
	{
		error_log("yookassa_notification - empty event type.", 0);
		exit();
	}
	$ykEvent=$request["event"]; // Тип события.
	$objectId=$request["object"]["id"]; // Получаем id объекта, у которого изменилось состояние.
	if(!strlen($objectId))
	{
		error_log("yookassa_notification - empty object id.", 0);
		exit();
	}
	
	// С О Б Ы Т И Е   "У С П Е Ш Н Ы Й   П Л А Т Ё Ж".
	if($ykEvent=="payment.succeeded")
	{
		// Берём на себя инициативу: запрашиваем состояние объекта.
		$client = new Client();
		$client->setAuth($ykShopId, $ykShopPrivateKey);
		$payment = $client->getPaymentInfo($objectId);
		// Флаг "test"
		if($payment["test"]=="true")
		{
			error_log("yookassa_notification - succeeded payment marked as TEST.", 0);
			exit();
		}
		// Сумма.
		$amount = (float)$payment["amount"]["value"];
		if($amount<=0)
		{
			error_log("yookassa_notification - invalid amount: ".$amount, 0);
			exit();
		}
		// Валюта
		$currency = $payment["amount"]["currency"];
		if($currency!="RUB")
		{
			error_log("yookassa_notification - invalid currency: ".$currency, 0);
			exit();
		}
		// Идентификатор аккаунта.
		$description = $payment["description"];
		$descriptionArray=explode('№',$description);
		$accountId=(int)$descriptionArray[1];
		if($accountId<=0)
		{
			error_log("yookassa_notification - invalid account id: ".$accountId, 0);
			exit();
		}

		// Проверка состояния аккаунта.
		if(!isAccountExists($accountId))
		{
			exit();
		}
		
		// Добавляем платёж в базу данных.
		if(!insertPayment($accountId, $amount))
		{
			exit();
		}


		// Добавляем чек в отложенную печать.
/*		$checkJsonData=[];
		$checkJsonData["type"]="sale";
		$checkJsonData["acceptedCash"]=0;
		$checkJsonData["acceptedCashless"]=$amount;
		$checkJsonData["prepaid"]=0;
		$checkJsonData["clientEmailOrPhone"]=$clientPhone;
		$checkProduct=[];
		$checkProduct["section"]="1";
		$checkProduct["paymentType"]=3;// 3 - Аванс. Признак способа расчета (реквизит 1214).
		$checkProduct["name"]="Пополнение аккаунта ".$accountId;
		$checkProduct["cost"]=$amount;
		$checkProduct["units"]="";
		$checkProduct["quantity"]=1;
		$checkProducts=[];
		$checkProducts[]=$checkProduct;
		$checkJsonData["products"]=$checkProducts;
	
		$queryString="INSERT INTO checks_to_print (orderid,jsondata,cuserid,ctime) VALUES ('".$orderId."','".json_encode($checkJsonData, JSON_UNESCAPED_UNICODE)."','".$globaluid."','".date("Y-m-d H:i:s")."');";
		if(!$mysqli->query($queryString))
		{
			error_log("yookassa_notification - MySQL error: ".$mysqli->error." query: ".$queryString, 0);
			exit();
		}
		*/
	}

	// С О Б Ы Т И Е   "У С П Е Ш Н Ы Й   В О З В Р А Т".
	if($ykEvent=="refund.succeeded")
	{
		// Берём на себя инициативу: запрашиваем состояние объекта.
		$client = new Client();
		$client->setAuth($ykShopId, $ykShopPrivateKey);
		$refund = $client->getRefundInfo($objectId);
		// Сумма.
		$amount = (float)$refund["amount"]["value"];
		if($amount<=0)
		{
			error_log("yookassa_notification - invalid amount: ".$amount, 0);
			exit();
		}
		// Валюта
		$currency = $refund["amount"]["currency"];
		if($currency!="RUB")
		{
			error_log("yookassa_notification - invalid currency: ".$currency, 0);
			exit();
		}
		// Номер заказа.
		$description = $refund["description"];
		$descriptionArray=explode('№',$description);
		$accountId=(int)$descriptionArray[1];
		if($accountId<=0)
		{
			error_log("yookassa_notification - invalid account id: ".$accountId, 0);
			exit();
		}

		// Проверка состояния аккаунта.
		if(!isAccountExists($accountId))
		{
			exit();
		}

		// Добавляем платёж в базу данных.
		if(!insertPayment($accountId, -$amount))
		{
			exit();
		}
	}
}
//---------------------------------------------------------------------------------------

if(is_array($request) && array_key_exists('_function',$request) && ($request['_function']=='yandexCreatePayment' || $request['_function']=='yookassaCreatePayment')) // Возвращает ссылку на страницу оплаты.
{
	$accountId=(int)$request["accountId"];
	$amount=(int)$request["amount"];

	if($amount<1)
	{
		error_log("Incorrect amount: ".$currency, 0);
		exit();
	}
	// Проверка состояния аккаунта.
	if(!isAccountExists($accountId))
	{
		error_log("Account not exists: ".$accountId, 0);
		exit();
	}

	// Запрос к API яндекса.

    $client = new Client();
    $client->setAuth($ykShopId, $ykShopPrivateKey);
    $payment = $client->createPayment(
        array(
            'amount' => array(
                'value' => $amount,
                'currency' => 'RUB',
            ),
            'confirmation' => array(
                'type' => 'redirect',
                'return_url' => $ykShopReturnUrl,
            ),
			'payment_method_data' => array(
				'type' => 'bank_card',
			),
            'capture' => true,
            'description' => 'Пополнение аккаунта №'.$accountId,
        ),
        uniqid('', true)
    );
	$jsonResponse["redirectUrl"]=$payment["confirmation"]["confirmation_url"]; // необходимо перенаправить клиента на этот адрес.
	$jsonResponse["accountId"]=$accountId;
	echo json_encode($jsonResponse);
	exit();
}
//---------------------------------------------------------------------------------------
function insertPayment($accountId, $amount)
{
	global $aDb;
	// Добавляем платёж в базу данных.
	$stmt = $aDb->prepare('INSERT INTO payments (accountid, amount, type, ctime) VALUES (:accountId,:amount,:type,:ctime);');
	$stmt->bindValue(":accountId", $accountId, PDO::PARAM_INT);
	$stmt->bindValue(":amount", $amount, PDO::PARAM_INT);
	$stmt->bindValue(':type', 2, PDO::PARAM_INT);
	$stmt->bindValue(":ctime", date("Y-m-d H:i:s"), PDO::PARAM_STR);
	if(!$stmt->execute())
	{
		error_log("yookassa_notification - MySQL error: ".$stmt->errorInfo()[2], 0);
		return false;
	}

	// Обновляем баланс.
	$stmt = $aDb->prepare('UPDATE accounts SET balance = balance + :amount WHERE id = :accountId;');
	$stmt->bindValue(":accountId", $accountId, PDO::PARAM_INT);
	$stmt->bindValue(":amount", $amount, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		error_log("yookassa_notification - MySQL error: ".$stmt->errorInfo()[2], 0);
		return false;
	}
	return true;
}
//---------------------------------------------------------------------------------------
function isAccountExists($accountId)
{
	global $aDb;
	// Проверка состояния аккаунта.
	$stmt = $aDb->prepare('SELECT active FROM accounts WHERE id=:accountId;');
	$stmt->bindValue(":accountId", $accountId, PDO::PARAM_INT);
	if(!$stmt->execute())
	{
		error_log("isAccountExists() MySQL error: ".$stmt->errorInfo()[2], 0);
		return false;
	}
	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!count($results))
	{
		error_log("isAccountExists() Account not found: ".$accountId, 0);
		return false;
	}
	return true;
}
//---------------------------------------------------------------------------------------
?>
