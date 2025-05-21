<?php 

$msg3="";
$sendtocustomermail="";
$headers1="";

if(isset($_POST['submit'])){
    $name=$_POST['name'];
     $email=$_POST['email'];
 $phone=$_POST['phone'];
 $service=$_POST['service'];
$message=$_POST['message'];
$s_id=$_POST['s_id'];

 $url = "https://lms.xcelmarketing.in/API/SaveLeads?Name=" .urlencode($name). "&Email=" .urlencode($email). "&Phno=" .urlencode($phone). "&SourceId=" .$s_id. "&CategoryId=" .urlencode($service). "&CityID=1&Remarks=". urlencode($message);
// print_r($url);
//$url = 'https://curlbasics.com?curl=basic&method=get';					 			
 $ch = curl_init();			 			
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);			 			
 curl_setopt($ch, CURLOPT_URL, $url);			 			
 $result = curl_exec($ch);	
 
 //echo $result;
 //die;
    $sendtocustomermail .= "sales@xcelmarketing.in";
   // $sendtocustomermail .= "sonulodhi.54321@gmail.com";
    $customersubject= 'New Enquiry Info';
     $customermessage = '         
    Dear Admin,'."\r\n".'
    <br>
   <h4><b> You Have an Enquiry from: '."\r\n".'</b></h4><br>
   <table style="border:1px solid black";>
    <tr>    
    Name = '.$name."\r\n".' <br>
   <hr>
    Phone Number = '.$phone."\r\n".' </hr><br>
    <hr>
      Email = '.$email."\r\n".'</hr>
<br>    <hr>
      Service = '.$service."\r\n".'</hr>
<br>
<hr><br>
     Message = '.$message."\r\n".'</tr></table>
    ';
    $headers1 .= 'From:  Xcelmarketing<noreply@xcelmarketing.com>' . "\r\n";
    $headers1 .= "Content-type: text/html \n";
    $mail = mail($sendtocustomermail,$customersubject,$customermessage,$headers1);
    if($mail){ 
header("Location: https://xcelmarketing.in/thank-you.php"); die();	
	echo "Mail Sent Successfully";	
	header("Location: https://xcelmarketing.in/thank-you.php"); die();
?>
<!--        <script>
        var a='<?php echo $name ?>';
        var b='<?php echo $phone ?>';
        var c='<?php echo $email ?>';
         var d='<?php echo $service ?>';
        var msg='<?php echo $message ?>';

    var window4=window.open("https://lms.xcelmarketing.in//API/SaveLeads?Name="+a+"&Email="+c+"&Phno="+b+"&SourceId=4&CategoryId="+d+"&CityID=1&Remarks="+msg+"");

       alert(window4);
        </script>-->
        <?php
            }else{
        $msg3="some thing went wrong";
    }
}
?>