<?php

require_once __DIR__.'/config.php';

use PHPMailer\PHPMailer\PHPMailer;
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';

function sendEmail($email, $subject, $message)
{
	global $smtpHost, $smtpPort, $smtpLogin, $smtpPassword;


	$email=str_replace("<", "&lt;", $email);
	$email=str_replace(">", "&gt;", $email);

	if(empty($email) || empty($message))
	{
		return false;
	}
//	require('./PHPMailer/PHPMailerAutoload.php');
	$mail=new PHPMailer();
	$mail->CharSet = 'UTF-8';

	$mail->IsSMTP();
	$mail->Host       = $smtpHost;

	$mail->SMTPSecure = 'ssl';
	$mail->Port       = $smtpPort;
//	$mail->SMTPDebug  = 1;
	$mail->SMTPAuth   = true;

	$mail->Username   = $smtpLogin;
	$mail->Password   = $smtpPassword;

	$mail->SetFrom($mail->Username, '');
	$mail->Subject    = $subject;
	$mail->MsgHTML($message);

	$mail->AddAddress($email);

	$mail->send();		

	return true;
}

?>
