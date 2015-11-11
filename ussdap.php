<?php
//Shehan Bhavan - hSenid Mobile Solutions
//shehanb@hsenidmobile.com
//Dialog Ideamart
ini_set('error_log', 'ussd-app-error.log');

require 'libs/MoUssdReceiver.php';
require 'libs/MtUssdSender.php';
require 'class/operationsClass.php';
require 'libs/Log.php';
require 'db.php';
require_once 'libs/SMSReceiver.php';
require_once 'libs/SMSSender.php';

define('APP_ID', 'APPID');
define('APP_PASSWORD', 'password');

$production=false;

	if($production==false){
		$ussdserverurl ='http://localhost:7000/ussd/send';
		$smsserverurl = 'http://localhost:7000/sms/send';
	}
	else{
		$ussdserverurl= 'https://api.dialog.lk/ussd/send';
		$smsserverurl = 'http://api.dialog.lk/sms/send';
	}


$receiver 	= new UssdReceiver();
$sender 	= new UssdSender($ussdserverurl,APP_ID, APP_PASSWORD);
$smssender = new SMSSender( $smsserverurl,APP_ID, APP_PASSWORD);
$operations = new Operations();

$content 			= 	$receiver->getMessage(); // get the message content
$address 			= 	$receiver->getAddress(); // get the sender's address
$requestId 			= 	$receiver->getRequestID(); // get the request ID
$applicationId 		= 	$receiver->getApplicationId(); // get application ID
$encoding 			=	$receiver->getEncoding(); // get the encoding value
$version 			= 	$receiver->getVersion(); // get the version
$sessionId 			= 	$receiver->getSessionId(); // get the session ID;
$ussdOperation 		= 	$receiver->getUssdOperation(); // get the ussd operation


$responseMsg = array(
    "main" =>  
    "Welcome To Arduino Demo

1. On
2. Off 

99. Exit"
);


if ($ussdOperation  == "mo-init") { 
   
	try {
		
		$sessionArrary=array("sessionsid"=>$sessionId,"tel"=>$address,"menu"=>"main","pg"=>"","others"=>"");

  		$operations->setSessions($sessionArrary);

		$sender->ussd($sessionId, $responseMsg["main"],$address );

	} catch (Exception $e) {
			$sender->ussd($sessionId, 'Please try again',$address );
	}
	
}else {

	$flag=0;
  	$sessiondetails=  $operations->getSession($sessionId);
  	$cuch_menu=$sessiondetails['menu'];
  	$operations->session_id=$sessiondetails['sessionsid'];

		switch($cuch_menu){
		
			case "main": 	// Following is the main menu
					switch ($receiver->getMessage()) {
						case "1":
							$operations->session_menu="On";
							$response=$smssender->sms('on', $address);
							$operations->saveSesssion();
							$sender->ussd($sessionId,'Enter Your ID',$address );
							break;
						case "2":
							$operations->session_menu="Off";
							$operations->saveSesssion();
							$response=$smssender->sms('off', $address);
							$sender->ussd($sessionId,'Enter Your ID',$address );
							break;
						default:
							$operations->session_menu="main";
							$operations->saveSesssion();
							$sender->ussd($sessionId, $responseMsg["main"],$address );
							break;
					}
					break;
			case "On":
				$operations->session_menu="Off";
				$operations->session_others=$receiver->getMessage();
				$operations->saveSesssion();
				$sender->ussd($sessionId,'On state'.$receiver->getMessage(),$address ,'mt-fin');
				break;
			case "Off":
				$sender->ussd($sessionId,'Off state'.$receiver->getMessage(),$address ,'mt-fin');
				break;
			default:
				$operations->session_menu="main";
				$operations->saveSesssion();
				$sender->ussd($sessionId,'Incorrect option',$address );
				break;
		}
	
}