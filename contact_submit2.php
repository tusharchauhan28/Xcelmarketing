<?php 






$msg3="";

$sendtocustomermail="";
$headers1="";
if(isset($_POST['submit'])){
   
    $name=$_POST['name'];
     $email=$_POST['email'];
 $phone=$_POST['phone'];
$message=$_POST['message'];

    $sendtocustomermail .= "sales@xcelmarketing.in";
   
    $customersubject= 'New Contact Info';
     $customermessage = '         
    Dear Admin,'."\r\n".'
    <br>
    
   <h4><b> You Have an Contact from: '."\r\n".'</b></h4><br>
   <table style="border:1px solid black";>
    <tr>    
    Name = '.$name."\r\n".' <br>
  
   <hr>
    Phone Number = '.$phone."\r\n".' </hr><br>
   <hr>
      Email = '.$email."\r\n".'</hr>
   <br>
<hr><br>
     Message = '.$message."\r\n".'</tr></table>
    ';
    $headers1 .= 'From:  Xcelmarketing<noreply@xcelmarketing.com>' . "\r\n";
    $headers1 .= "Content-type: text/html \n";
    $mail = mail($sendtocustomermail,$customersubject,$customermessage,$headers1);
     if($mail){
        $msg3="Mail Sent Successfully";
        header("location:index.php");
    }else{
        $msg3="some thing went wrong";
    }
}

  
    

?>



  


