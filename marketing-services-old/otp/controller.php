<?php
session_start();
error_reporting(E_ALL & ~ E_NOTICE);
// require ('textlocal.class.php');

class Controller
{
    function __construct() {
        $this->processMobileVerification();
    }
    function processMobileVerification()
    {
        switch ($_POST["action"]) {
            case "send_otp":
                
                $name = $_POST['name'];
                $s_id = $_POST['s_id'];
                $email = $_POST['email'];
                $mobile_number = $_POST['mobile_number'];
                $service = $_POST['service'];
                $message = $_POST['message'];
                // print_r($mobile_number);
					// die;
                // $apiKey = urlencode('YOUR_API_KEY');
                // $Textlocal = new Textlocal(false, false, $apiKey);
                
                $numbers = array(
                    $mobile_number
                );
                $sender = 'PHPPOT';
                $otp = rand(1000, 9999);
                $_SESSION['session_otp'] = $otp;
                $_SESSION['name'] = $name;
                $_SESSION['s_id'] = $s_id;
                $_SESSION['email'] = $email;
                $_SESSION['mobile_number'] = $mobile_number;
                $_SESSION['service'] = $service;
                $_SESSION['message'] = $message;
                // $message = "Your One Time Password is " . $otp;
                $message = "Thank%20you%20for%20showing%20your%20interest%20in%20Xcel%20Marketing.%20your%20mobile%20verification%20OTP%20is%20". $otp .".%20Do%20not%20share%20OTP%20with%20anyone.%20";
                
                try{
                    // $response = $Textlocal->sendSms($numbers, $message, $sender);
					//CURL start
					$curl = curl_init();

					curl_setopt_array($curl, array(
					  CURLOPT_URL => 'https://sms.xcelmarketing.in/api/SmsApi/SendSingleApi?UserID=XCEL&Password=Om%40nlum5749NL&SenderID=XCLSMS&Phno='. $mobile_number .'&Msg='. $message .'&EntityID=1701159834356736157&TemplateID=1707168965160700422',
					  CURLOPT_RETURNTRANSFER => true,
					  CURLOPT_ENCODING => '',
					  CURLOPT_MAXREDIRS => 10,
					  CURLOPT_TIMEOUT => 0,
					  CURLOPT_FOLLOWLOCATION => true,
					  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					  CURLOPT_CUSTOMREQUEST => 'GET',
					));

					$response = curl_exec($curl);

					curl_close($curl);
					// echo $response;
					// print_r($response);
					// die;
					//CURL end
					// header("Location: https://www.xcelmarketing.in/marketing-services/otp/verification-form.php");
                    require_once("verification-form.php");
                    exit();
                }catch(Exception $e){
                    die('Error: '.$e->getMessage());
                }
                break;
                
            case "verify_otp":
                $otp = $_POST['otp'];
                
                if ($otp == $_SESSION['session_otp']) {
					$name=$_SESSION['name'];
					$email=$_SESSION['email'];
					 $phone=$_SESSION['mobile_number'];
					 $service=$_SESSION['service'];
					$message=$_SESSION['message'];
					$s_id=$_SESSION['s_id'];
					$url = "https://lms.xcelmarketing.in/API/SaveLeads?Name=" .urlencode($name). "&Email=" .urlencode($email). "&Phno=" .urlencode($phone). "&SourceId=" .$s_id. "&CategoryId=" .urlencode($service). "&CityID=1&Remarks=". urlencode($message);
					// print_r($url);
					//$url = 'https://curlbasics.com?curl=basic&method=get';					 			
					 $ch = curl_init();			 			
					 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);			 			
					 curl_setopt($ch, CURLOPT_URL, $url);			 			
					 $result = curl_exec($ch);
					 //$result = str_replace('"',"",$result);
					 //print_r($result);die;
                    unset($_SESSION['session_otp']);
                    unset($_SESSION['name']);
                    unset($_SESSION['email']);
                    unset($_SESSION['mobile_number']);
                    unset($_SESSION['service']);
                    unset($_SESSION['message']);
                    echo json_encode(array("type"=>"success", "message"=>$result));
                } else {
                    echo json_encode(array("type"=>"error", "message"=>"Mobile number verification failed"));
                }
                break;
        }
    }
}
$controller = new Controller();
?>