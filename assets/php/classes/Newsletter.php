<?php
// if (!isset($_SESSION)){
//     echo 'Forbidden!<script>
//     window.location = "../../../index.php";
//     </script>';
// }
/*
* THIS CLASS IS USED FOR ALL SYSTEM CALLS THAT DEAL WITH NEWSLETTERS
*/ 
class Newsletter{
    var $__id;
    var $__title;
    var $__content;
    var $__pdf;
    var $__image;
    var $__status;
    var $__last_modified_date;
    var $__creation_date;
    var $__creator_email;
    var $__mailing_list;

    /*CONSTRUCTOR*/
    function __construct(){
        $this->__id = "";
        $this->__title = "";
        $this->__content = "";
        $this->__pdf = "";
        $this->__image = "";
        $this->__status = "";
        $this->__last_modified_date = "";
        $this->__creation_date = "";
        $this->__creator_email = "Test";
        $this->__mailing_list = "";
    }
    

    /*SETTERS*/
    function set_details($title, $content, $image, $pdf, $creator_email, $mailing_list = "all", $id="", $status="posting", $creation_date="", $last_modified_date = ""){
        
        $this->__id = $id;
        $this->__title = $title;
        $this->__content = $content;
        $this->__creator_email = $creator_email;
        $this->__pdf = $pdf;
        $this->__image = $image;
        $this->__status = $status;
        $this->__creation_date = $creation_date;
        if($last_modified_date = ""){
            $last_modified_date = date("Y-m-d H:i:s");
        }
        $this->__last_modified_date = $last_modified_date;
        $this->__mailing_list = $mailing_list;
    }

    function set_id($id){
        $this -> __id = $id;
    }
    function set_title($title){
        $this -> __title = $title;
    }
    function set_content($content){
        $this -> __content = $content;
    }
    function set_pdf($pdf){
        $this -> __pdf = $pdf;
    }
    function set_image($image){
        $this -> __image = $image;
    }
    function set_status($status){
        $this -> __status = $status;
    }
    function set_creator_email($creator_email){
        $this -> __creator_email = $creator_email;
    }
    function set_creation_date($creation_date){
        $this -> __creation_date = $creation_date;
    }
    function set_last_modified_date($modified_date){
        $this -> __last_modified_date = $modified_date;
    }
    function set_mailing_list($mailing_list){
        $this -> __mailing_list = $mailing_list;
    }

    /*GETTERS*/        
    function get_id(){
        return $this -> __id;
    }
    function get_title(){
        return $this -> __title;
    }
    function get_content(){
        return $this -> __content;
    }
    function get_pdf(){
        return $this -> __pdf;
    }
    function get_image(){
        return $this -> __image;
    }
    function get_status(){
        return $this -> __status;
    }
    function get_creation_date(){
        return $this -> __creation_date;
    }
    function get_last_modified_date(){
        return $this -> __last_modified_date;
    }
    function get_creator_email(){
        return $this -> __creator_email;
    }
    function get_mailing_list(){
        return $this -> __mailing_list;
    }

    /* Functions */
    function update_newsletter(){
        //update newletter in db
    }

    function set_details_from_db($db_result){
        if (is_string($db_result)){
            return false;
        }
        while($row = $db_result -> fetch_row()){
            $this -> set_details($row[1],$row[2], $row[3], $row[4], $row[8], $row[9], $row[0], $row[5], $row[6], $row[7]);
            return true;
        }
        return false;
    }
    function refresh_from_db(){
        $title = htmlentities($this->get_title());
        $content = htmlentities($this->get_content());
        $creator_email = $this->get_creator_email();
        $result = db_call("select", "*","newsletters", "`creator_email` = '$creator_email' AND `title` = '$title' AND `content` = '$content'");
        // set details from db and return true if done successfully
        return $this->set_details_from_db($result);
    }
    
    function refresh_from_db_by_id($id=""){
        if ($id == ""){
            $id = $this->get_id();
        }
        if ($id != ""){
            $result = db_call("select", "*","newsletters", "`id` = $id");
            return $this->set_details_from_db($result);
        }
        return false;
    }

    function post(){
        // publish newsletter
        
        if (!$this->refresh_from_db()){
            return "Error: Newsletter not saved";
        }
        $status = "posted";
        if($this->get_status() == "saved"){
            $status = "posting";
        }
        $newsletter_id = $this->get_id();
        $creator_email = $this->get_creator_email();
        $result = db_call("update", "newsletters", "status, creator_email", "$status [~] $creator_email", "`id` = $newsletter_id");
        if (!$this->refresh_from_db()){
            return "Error: Newsletter not saved";
        }
        if ( $this ->get_status() == "posting") {
            // Send emails
            $mailing_list = $this->get_mailing_list();
            $this->send_emails($mailing_list);
            echo "Email sent to all subscribers.<br>";
            // Change status to posted
            $status = "posted";
            $result = db_call("update", "newsletters", "status", "$status", "`id` = $newsletter_id");
            if (!$this->refresh_from_db()){
                return "Error: Newsletter not saved";
            }
            return $result;
        } else {
            return false;
        }
    }

    function save(){
        $title = htmlentities($this-> get_title());
        $content = htmlentities($this-> get_content());
        $image_url = $this-> get_image();
        $status = "saved";
        $pdf_url = $this-> get_pdf();
        $creator_email = $this-> get_creator_email();
        $creation_date = $this-> get_creation_date();
        $last_modified_date = $this-> get_last_modified_date();
        $mailing_list = $this-> get_mailing_list();
        $newsletter_id = $this->get_id();

        $variables = "title, content, mailing_list";
        $values = "$title [~] $content [~] $mailing_list";
        if ($image_url != ""){
            $variables .= ", image_url";
            $values .= " [~] $image_url";
        }
        if ($pdf_url != ""){
            $variables .= ", pdf_url";
            $values .= " [~] $pdf_url";
        }
        if ($newsletter_id != ''){
            $result = db_call("update", "newsletters", $variables, $values, "`id` = $newsletter_id");
        } else {
            $result = db_call("insert", "newsletters", "title, content, image_url, pdf_url, status, creator_email, mailing_list", "$title [~] $content [~] $image_url [~] $pdf_url [~] $status [~] $creator_email [~] $mailing_list");
        }
        $this->refresh_from_db();
        return $result;
    }
    function archive(){
        $newsletter_id = $this->get_id();
        if($this->refresh_from_db_by_id($newsletter_id)){
            $status = "archived";
            $result = db_call("update", "newsletters", "status", "$status", "`id` = $newsletter_id");
            return $result;
        }
        echo "Newsletter doesn't exist.";
        return false;
    }
    function detele(){
        $newsletter_id = $this->get_id();
        if($this->refresh_from_db_by_id($newsletter_id)){
            $status = "deleted";
            $result = db_call("delete", "newsletters", "`id` = $newsletter_id");
            return $result;
        }
        echo "Newsletter doesn't exist.";
        return false;
    }
    function restore(){
        $newsletter_id = $this->get_id();
        if($this->refresh_from_db_by_id($newsletter_id)){
            $status = "restored";
            $result = db_call("update", "newsletters", "status", "$status", "`id` = $newsletter_id");
            return $result;
        }
        echo "Newsletter doesn't exist.";
        return false;
    }
    function has_errors(){
        $result = '[Newsletter]';
        if ($this->get_title() == ""){
            $result .= "<br>Title is required.";
        }
        if ($this->get_content() == ""){
            $result .= "<br>Description is required.";
        }
        if ($result == '[Newsletter]'){
            $result = false;
        }
        return $result;
    }

    function send_emails($mailing_list){
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= 'From: Believe And Abide Bible Info Centre <no-reply@believeandabide.org>' . "\r\n";    
        $headers .= "Reply-To: Bible Info Centre <bible@believeandabide.org>\r\n";
        

        $subject = $this->__title;

        $message = "<html><body style='font-family: sans-serif; padding: 40px;'><p>";
        $message .= nl2br(htmlentities($this->__content));
        $url = 'beta/';
        $pdf = $url . $this->__pdf;
        $website = $url. 'newsletters.php';
        $message .= "</p><br><br><a href='https://www.believeandabide.org/$pdf' style='margin: 10px; margin-left: 0px box-shadow: 1px 1px 3px 1px rgba(10,10,10,0.3); cursor: pointer; text-decoration: none; background-color: rgb(58, 58, 58); color: white; width: 150px; text-align: center; height: 20px; line-heigh: 20px; padding: 10px; border-radius: 10px;'>View PDF</a>";
        $message .= "</p><a href='https://www.believeandabide.org/$url' style='margin: 10px; box-shadow: 1px 1px 3px 1px rgba(10,10,10,0.3); cursor: pointer; text-decoration: none; background-color: rgb(125, 170, 177); color: white; width: 150px; text-align: center; height: 20px; line-heigh: 20px; padding: 10px; border-radius: 10px;'>Read on the website</a>";
        $message .= "</body></html>";
        // mail($to, $subject, $message, $headers);
        $emails_to_send_to = array();
        if ($mailing_list == 'all'){
            $controller =  new Controller();
            $emails = $controller->get('subscribers', '*');
            while($emails && $row = $emails -> fetch_array()){
                if ($row[1] == "subscribed"){
                    array_push($emails_to_send_to, $row[0]);
                }
            }
        }
        foreach ($emails_to_send_to as $to){
            mail($to, $subject, $message, $headers);
        }
    }
}