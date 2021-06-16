<?php
// if (!isset($_SESSION)){
//     echo 'Forbidden!<script>
//     window.location = "../../../index.php";
//     </script>';
// }
/*
* THIS CLASS IS USED FOR USER ACTIONS THAT INTERACT WITH THE DATABASE
*/ 
class User{
    var $__first_name;
    var $__last_name;
    var $__email;
    var $__type;
    var $__status;
    var $__creation_date;
    var $__last_modified_date;
    var $errors;

    /*CONSTRUCTOR*/
    function __construct(){
        $this->__first_name = "";
        $this->__last_name = "";
        $this->__email = "";
        $this->__creation_date = "";
        $this->__last_modified_date = "";
        $this->__type = '';
        $this->errors = [];
    }
    
    /*SETTERS*/
    function set_details($user_name, $user_surname, $user_email, $user_type="", $creation_date = "", $last_modified_date=""){
        $this->__first_name = $user_name;
        $this->__last_name = $user_surname;
        $this->__email = $user_email;
        $this->__type = $user_type;
        $this->__creation_date = $creation_date;
        $this->__last_modified_date = $last_modified_date;
    }
    function set_first_name($name){
        $this -> __first_name = $name;
    }
    function set_last_name($surname){
        $this -> __last_name = $surname;
    }
    function set_email($email){
        $this -> __email = $email;
    }
    function set_type($type){
        $this -> __type = $type;
    }
    function set_date($creation_date){
        $this -> __creation_date = $creation_date;
    }
    function set_last_modified_date($last_modified_date){
        $this -> __last_modified_date = $last_modified_date;
    }

    /*GETTERS*/        
    function get_first_name(){
        return $this -> __first_name;
    }
    function get_last_name(){
        return $this -> __last_name;
    }
    function get_full_name(){
        $f = $this -> __first_name.' '.$this -> __last_name;
        return $f;
    }
    function get_email(){
        return $this -> __email;
    }
    function get_type(){
        return $this -> __type;
    }
    function get_creation_date(){
        return $this -> __creation_date;
    }
    function get_last_modified_date(){
        return $this -> __last_modified_date;
    }

    /* Functions*/
    function set_details_from_db($db_result){
        while($row = $db_result -> fetch_row()){
            $this -> set_details($row[2], $row[3], $row[0], $row[4], $row[5], $row[6]);
            return true;
        }
        return false;
    }
    function get_item_from_db($email = ""){
        if ($email == ""){
            $email = $this->get_email();
        }
        $result = db_call("select", "*","users", "`email` = '$email'");
        return $this->set_details_from_db($result);
    }
    function check_data_errors(){
        if($this->get_first_name()==""){
            array_push($this->errors, "First name required.");
        }
        if($this->get_last_name()==""){
            array_push($this->errors, "Last name required.");
        }
        if($this->get_email()==""){
            array_push($this->errors, "Email required.");
        }
    }
    function create_token($type){
        $email = $this->get_email();
        $string = "$email-$type";
        return $this->hash_password($string);
    }
    function hash_password($password){
        $options = array('cost'=>11);
        $hash = password_hash($password, PASSWORD_BCRYPT, $options);
        return $hash;
    }
    function verify_token($token, $type){
        $email = $this->get_email();
        $string = "$email-$type";
        return password_verify($string, $token);
    }

    /* Database related Functions*/
    function login($password){
        // check if email exists
        $email = $this->get_email();
        $result = db_call("select", "*", "users", "`email` = '$email'");
        $count = 0;
        while($row = $result -> fetch_row()){
            if(password_verify($password, $row[1])){
                $this -> set_details($row[2], $row[3], $row[0], $row[4], $row[5], $row[6]);
                return true;
            }
            $count++;
        }
        if($count == 0){
            array_push($this->errors, "User not found.");
            return false;
        }else{
            array_push($this->errors, "Incorrect password.");
            return false;
        }
    }
    function register($password1, $password2){
        // check if email exists
        $this->check_data_errors();
        if($password1 != $password2){
            array_push($this->errors, "Passwords do not match.");
        }
        if(strlen($password1)<8){
            array_push($this->errors, "Password must be at least 8 characters long.");
        }
        if(count($this->errors) == 0){
            $email = $this->get_email();
            $result = db_call("select", "*", "users", "`email` = '$email'");
            $count = 0;
            while($row = $result -> fetch_row()){
                $count++;
            break;
            }
            if($count == 1){
                array_push($this->errors, "Email already in use.");
                return false;
            }else{
                $hash = $this->hash_password($password1);
                $first_name = $this->get_first_name();
                $last_name = $this->get_last_name();
                db_call("insert", "users", "email, password, first_name, last_name, type", "$email [~] $hash [~] $first_name [~] $last_name [~] regular");
                return $this->login($password1);
            }
        }
        return false;
    }
    function request_password_reset(){
        $email = $this->get_email();
        $token = $this->create_token("password-reset");
        $now = date("Y-m-d H:i:s");
        $expiry_date =  date("Y-m-d H:i:s", strtotime('+24 hours'));
        $type = "password-reset";
        // expire existing tokens that are not already expired for this email
        db_call("update", "tokens", "expiry_date", $now, "`email` = '$email' AND `expiry_date` >= now()");
        // Create new token for this user
        db_call("insert", "tokens", "token, email, type", "$token [~] $email [~] $type");
        db_call("update", "tokens", "expiry_date", $expiry_date, "`token` = '$token'");

    }
    function is_token_expired($token){
        $result = db_call("select", "expiry_date", "tokens", "`token` = '$token'");
        $now = date("Y-m-d H:i:s");
        while($row = $result -> fetch_row()){
            $expiry_date =  $row[0];
        }
        if(isset($expiry_date) && $expiry_date <= $now){
            return true;
        }else if(!isset($expiry_date)){
            return true;
        }
        return false;
    }
    function change_password($token, $password1, $password2){
        // check if token is valid
        $email = $this->get_email();
        if($this->verify_token($token, "password-reset")){
            // check if token is in db and has not expired
            $password = "";
            if($password1 != $password2){
                array_push($this->errors, "Passwords do not match.");
            }else{
                $password = $password1;
            }
            if(strlen($password1)<8){
                array_push($this->errors, "Password must be at least 8 characters long.");
            }
            $result = db_call("select", "*", "users", "`email` = '$email'");
            $count = 0;
            while($row = $result -> fetch_row()){
                if(password_verify($password, $row[1])){
                    $count++;
                break;
                }
                $count++;
            break;
            }
            if($count == 0){
                array_push($this->errors, "User not found.");
            }else if ($count >= 2){
                array_push($this->errors, "New password cannot be the same as the old password.");
            }

            if(count($this->errors) == 0){
                $type = "changed-password";
                $now = date("Y-m-d H:i:s");
                $meta = str_replace(",",".",$_SERVER['HTTP_USER_AGENT'])."~".$_SERVER['REMOTE_ADDR'];
                if (!$this->is_token_expired($token)){
                    $hash = $this->hash_password($password1);
                    db_call("update", "users", "password", $hash, "`email` = '$email'");
                    db_call("insert", "users_history", "email, action, meta", "$email [~] $type [~] $meta");
                    db_call("update", "tokens", "expiry_date", $now, "`email` = '$email' AND `expiry_date` >= now()");
                    return true;
                }
            }
            return false;
        }
        // save new password
    }
    function subscribe($email = ""){
        // check if user has email
        $update = false;
        if ($email == ""){
            $email = $this->get_email();
        }
        if($email == ""){
            array_push($this->errors, "No email provided.");
        }else{
            // check if email already subscribed
            $result = db_call("select", "*", "subscribers", "`email` = '$email'");
            while($row = $result -> fetch_row()){
                if($row[1]=="subscribed"){
                    array_push($this->errors, "Email already subscribed.");
                }
                else{
                    $update = true;
                }
                break;
            }
        }
        if(count($this->errors) == 0){
            $status = "subscribed";
            if($update){
                return db_call("update", "subscribers", "status", $status, "`email` = '$email'");
            }else{
                return db_call("insert", "subscribers", "email, status", "$email [~] $status");
            }
            
        }
        return false;
    }
    function is_subscribed($email=""){
        if($email == ""){
            $email = $this->get_email();
        }
        $result = db_call("select", "*", "subscribers", "`email` = '$email'");
        while($row = $result -> fetch_row()){
            if($row[1] == "subscribed"){
                return true;
            }else{
                return false;
            }
        }
        return false;

    }
    function unsubscribe($email = ""){
        // check if user has email
        $this->set_email($email);
        if($this->get_email() == ""){
            array_push($this->errors, "No email provided.");
        }else{
            // check if email already subscribed
            $email = $this->get_email();
            $result = db_call("select", "*", "subscribers", "`email` = '$email'");
            while($row = $result -> fetch_row()){
                if($row[1] == "subscribed"){
                    $status = "unsubscribed";
                    db_call("update", "subscribers", "status", $status, "`email` = '$email'");
                    return true;
                }
                break;
            }
            array_push($this->errors, "Email not subscribed.");
        }
        return false;
    }
    function send_email($recepient, $type, $contents){
        // Check if user has permission
        // store email in DB
        // Send email
        // Change email status
    }
    function post_newsletter($newsletter){
        // Check if user has permission
        if($this->get_type() == "root" || $this->get_type() == "admin" || $this->get_type() == "support"){
            $newsletter_errors = $newsletter->has_errors();
            if($newsletter_errors){
                array_push($this->errors, $newsletter_errors);
                return false;
            }
            if ($newsletter->get_status() != "saved" && $newsletter->get_status() != "restored"){
                // newsletter not saved. Save it first
                $result =  $this->save_newsletter($newsletter);
            }
            if($newsletter->get_status() == "saved" || $newsletter->get_status() == "restored"){
                // Newsletter saved. Post it
                    $result = $newsletter -> post();
            }
            return $result;
        }else{
            array_push($this->errors, "Forbidden!");
            return false;
        }
        // store newsletter
        // send email to $mailing_list
        // check if all emails have been sent. If sent, mark status as sent, else mark as interupted
    }
    function save_newsletter($newsletter){
        // check if user has permission
        if($this->get_type() == "root" || $this->get_type() == "admin" || $this->get_type() == "support"){
            $newsletter_errors = $newsletter->has_errors();
            if($newsletter_errors){
                array_push($this->errors, $newsletter_errors);
                return false;
            }
            return $newsletter->save();
        }else{
            array_push($this->errors, "Forbidden!");
            return false;
        }
        // store newsletter. Save status as draft

    }
    function archive_newsletter($newsletter){
        // Check if user has permission
        if($this->get_type() == "root" || $this->get_type() == "admin" || $this->get_type() == "support"){
            if ($newsletter->get_id() == ""){
                array_push($this->errors, "Newsletter not in database");
                return false;
            }
            if ($newsletter->archive()){
                echo "Newsletter archived.";
                return true;    
            }
            echo "Error: something went wrong.";
            return false;
        }else{
            array_push($this->errors, "Forbidden!");
            return false;
        }
    }
    function delete_newsletter($newsletter){
        // Check if user has permission
        if($this->get_type() == "root" || $this->get_type() == "admin"){
            if ($newsletter->get_id() == ""){
                array_push($this->errors, "Newsletter not in database");
                return false;
            }
            if ($newsletter->detele()){
                echo "Newsletter permanantly deleted.";
                return true;    
            }
            echo "Error: something went wrong.";
            return false;
        // mark newsletter as deleted
        }else{
            array_push($this->errors, "Forbidden!");
            return false;
        }
    }
    function restore_newsletter($newsletter){
        // Check if user has permission
        if($this->get_type() == "root" || $this->get_type() == "admin" || $this->get_type() == "support"){
            if ($newsletter->get_id() == ""){
                array_push($this->errors, "Newsletter not in database");
                return false;
            }
            if ($newsletter->restore()){
                echo "Newsletter restored.";
                return true;    
            }
            echo "Error: something went wrong.";
            return false;
        // mark newsletter as deleted
        }else{
            array_push($this->errors, "Forbidden!");
            return false;
        }
    }
    function delete_subscriber($email){
        // check if user has permission
        // mark subscriber as deleted
    }

    function update($user){
        //update user in db
        if($user->get_email() != ''){
            array_push($this->errors, "Please select a user.");
        }
        if($user->get_type() != ''){
            array_push($this->errors, "Please select a role.");
        }
        if(count($user->errors) > 0){
            return false;
        }
        $type = $user->get_type();
        $email = $user->get_email();
        $last_modified_date = date("Y-m-d H:i:s");
        $result = db_call("update", "users", "type, last_modified", "$type [~] $last_modified_date", "`email` = '$email'");
        return $result;
    }

}