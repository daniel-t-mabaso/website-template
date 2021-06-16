<?php
    if(isset($_REQUEST) && @$_REQUEST['request']){
        session_start();
        $classes_dir =  __DIR__ . '/classes/';
        include_once($classes_dir . "Newsletter.php");
        include_once($classes_dir . "User.php");
        include_once($classes_dir . "Controller.php");
        $current_user = unserialize($_SESSION['user']);
    }
    $errors = array();
    if(!($_REQUEST['request'] ?? false) && !($_SESSION['custom_website_auth'] ?? false)){
        $ROOT =  __DIR__ ;
        include_once("$ROOT/controller.php");
        include_once("$ROOT/class_lib.php");
        if(isset($_POST["login"])){
            //  Get data from the form
            $email = addslashes(strtolower($_POST['email']));
            $password = addslashes($_POST['password']);
            // Attempt login
            $user = new User();
            $user->set_email($email);
            if($user->login($password)){
                $_SESSION['custom_website_auth'] = true;
                $serialized_user = serialize($user);
                $_SESSION['user'] = $serialized_user;
                if($user->is_subscribed()){
                    $_SESSION['subscribed'] = true;
                }else{
                    $_SESSION['subscribed'] = false;
                }
                // Go to home page
                echo '<script>
                window.location = "./";
                </script>';
            }
            else{
                $errors = $user->errors;
            }
        }else if(isset($_POST["register"])){
            // Get data from the form
            $email = addslashes(strtolower($_POST['email']));
            $password1 = addslashes($_POST['password']);
            $password2 = addslashes($_POST['confirm_password']);
            $first_name = addslashes($_POST['first_name']);
            $last_name = addslashes($_POST['last_name']);
            // Create user and attempt registration
            $user = new User();
            $user->set_details($first_name, $last_name, $email);
            if($user->register($password1, $password2)){
                $_SESSION['custom_website_auth'] = true;
                $serialized_user = serialize($user);
                $_SESSION['user'] = $serialized_user;
                // Go to home page
                echo '<script>
                window.location = "./";
                </script>';
            }
            else{
                $errors = $user->errors;
            }
        }else if(isset($_POST["forgot-password"])){
            // Get data from forgot password form
            $email = addslashes(strtolower($_POST['email']));
            // Create user and attempt registration
            $user = new User();
            $user->set_email($email);
            if($user->request_password_reset()){
                $serialized_user = serialize($user);
                $_SESSION['user'] = $serialized_user;
                // Go to home page
                echo '<script>
                alert("password reset link sent.");
                </script>';
                // echo '<script>
                // window.location = "./";
                // </script>';
            }
            else{
                $errors = $user->errors;
            }
        }else if(isset($_GET["reset-password"])){
            // Loading the reset password page
            // Get data from the form
            $email = addslashes(strtolower(@$_GET['email']));
            $token = @$_GET['token'];
            // Create user and attempt registration
            $user = new User();
            $user->set_email($email);
            if(!$user->is_token_expired($token)){
                //display form
                $reset_password_html = "<form action='reset.php' method='post'>
                    <p>You're about to reset your password</p>
                    <input class='form-input' type='password' name='password' placeholder='Enter new password...' id='password'>
                    <input class='form-input' type='password' name='confirm-password' placeholder='Confirm new password...' id='confirm-password'>
                    <input class='form-input' type='hidden' name='email' id='email' value='$email'>
                    <input type='hidden' name='password-reset-token' id='password-reset-token' value='$token'>
                    <input class='button primary-bg' type='submit' value='Reset password' name='reset-password'>
                </form>";
            }
            else{
                // Display error message
                $reset_password_html = "<h2>This link has expired</h2>";
            }
        }else if(isset($_POST["reset-password"])){
            // Loading the reset password page to enter new password
            // Get data from the form
            $email = addslashes(strtolower($_POST['email']));
            $token = $_POST['password-reset-token'];
            $password1 = addslashes($_POST['password']);
            $password2 = addslashes($_POST['confirm-password']);
            // Create user and attempt registration
            $user = new User();
            $user->set_email($email);
            if(!$user->is_token_expired($token)){
                //display form
                if($user->change_password($token, $password1, $password2)){
                    $reset_password_html = "<h2>Password successfully changed</h2>";
                    if($user->login($password1)){
                        $_SESSION['custom_website_auth'] = true;
                        $serialized_user = serialize($user);
                        $_SESSION['user'] = $serialized_user;
                        echo '<script>
                        window.location = "./";
                        </script>';
                    }
                    else{
                        echo "Somthing went wrong while changing the password.";
                    }
                }
                else{
                    $errors = $user->errors;
                    
                $reset_password_html = "<form action='reset.php' method='post'>
                <p>You're about to reset your password</p>
                <input class='form-input' type='password' name='password' placeholder='Enter new password...' id='password'>
                <input class='form-input' type='password' name='confirm-password' placeholder='Confirm new password...' id='confirm-password'>
                <input class='form-input' type='hidden' name='email' id='email' value='$email'>
                <input type='hidden' name='password-reset-token' id='password-reset-token' value='$token'>
                <input class='button primary-bg' type='submit' value='Reset password' name='reset-password'>
            </form>";
                }
            }
            else{
                // Display error message
                $reset_password_html = "<h2>This link has expired</h2>";
            }
        }
    }
// Go to home page
if(isset($public_page)){
    
    $ROOT =  __DIR__ ;
    include_once("$ROOT/controller.php");
    include_once("$ROOT/class_lib.php");
    if(isset($_POST["subscribe"])){
        $email = $_POST['subscriber-email'];
        $subscriber = new User();
        if($subscriber->subscribe($email)){
            // subscription successful
            $_SESSION['subscribed'] = true;
            // toast success
        }else{
            // an error occured
            $errors = $subscriber->errors;
        }
    }
    else if(isset($_POST["unsubscribe"])){
        $email = $_POST['email-address'];
        $subscriber = new User();
        if($subscriber->unsubscribe($email)){
            // unsubscription successful
            $_SESSION['subscribed'] = false;
            echo '<script>
            window.location = "./";
            </script>';
            // toast success
        }else{
            // an error occured
            $errors = $subscriber->errors;
        }
    }
    function display($type, $filter){
        $controller =  new Controller();
        $fetched_data = $controller->get($type, $filter);
        $html = "";
        $count = 0;
        switch($type){
            case "newsletters":
                // how to display newsletters
                while($fetched_data && $row = $fetched_data -> fetch_array()){
                    $id = $row[0];
                    $title = $row[1];
                    $content = nl2br($row[2]);
                    $content = (strlen($content) > 320) ? substr($content,0, 300).'...' : $content;
                    $img_url = $row[3];
                    $pdf_url = $row[4];
                    $status = $row[5];
                    if ($status != "posted"){
                        continue;
                    }
                    $modified = strtotime($row[7]);
                    $modified = date('d F Y', $modified);
                    $author_email = $row[8];
                    $author = new User();
                    $author->get_item_from_db($author_email);
                    $author = $author->get_full_name();
                    $mailing_list = $row[9];
                    $actions = "
                        <div class='button primary-bg center' onclick='view(\"newsletter\", \"$id\");'><img class='small-icon' src='assets/images/icon-view.png'>Read</div>
                        <a href='$pdf_url' download><div class='button secondary-bg center'><img class='small-icon' src='assets/images/icon-download.png'>Download PDF</div></a>
                    ";
                    $tmp = "
                        <div class='newsletter-panel'>
                                <h2 class=' center-txt'>
                                    $title
                                    <small><br><i>Posted $modified</i></small>
                                </h2>
                            <p>$content</p>
                            <div class='actions-panel'>$actions</div>
                        </div>
                    ";
                    echo $tmp;
                    $count++;
                }
                if($count == 0){
                    echo "No newsletters available.";
                }
            break;
            echo $count;
            echo $html;
        }
    }
}else if(($private_page ?? false) && ($_SESSION['custom_website_auth'] ?? false)){
    $ROOT =  __DIR__ ;
    include_once("$ROOT/controller.php");
    include_once("$ROOT/class_lib.php");
    function display($type, $filter){
        $fetched_data = "";
        $controller =  new Controller();
        $fetched_data = $controller->get($type, $filter);

        $html = "";
        $count = 0;
        switch($type){
            case "newsletters":
                // how to display newsletters
                while($fetched_data && $row = $fetched_data -> fetch_array()){
                    $id = $row[0];
                    $title = $row[1];
                    $content = nl2br($row[2]);
                    $img_url = $row[3];
                    $pdf_url = $row[4];
                    $status = $row[5];
                    $modified = $row[7];
                    $author_email = $row[8];
                    $author = new User();
                    $author->get_item_from_db($author_email);
                    $author = "<a href='mailto:$author_email'>" . $author->get_full_name() . '</a>';
                    $mailing_list = $row[9];
                    $archive_button = "<div class='button caution-bg' onclick='archive_item(\"newsletter\",\"$id\");'>Archive</div>";
                    $edit_button = "";
                    $classes = 'active-theme';
                    if ($status == "saved" || $status == "restored"){
                        $classes = 'inactive-theme';
                        $edit_button = "<div class='button primary-bg' onclick='edit_item(\"newsletter\",\"$id\");'><img class='small-icon' src='assets/images/icon-edit.png'>Edit</div>";
                    }
                    if($status == "archived"){
                        $classes = 'disabled-theme';
                        $archive_button = "<div class='button success-bg' onclick='restore_item(\"newsletter\",\"$id\");'>Restore</div>";
                    }
                    $actions = "
                        $edit_button
                        $archive_button
                        <div class='button danger-bg' onclick='delete_item(\"newsletter\",\"$id\");'>Delete</div>
                    ";
                    $tmp = "
                        <details class='newsletter-panel'>
                            <summary class='$classes'>
                                <b>$title</b>
                                <small>Published on $modified</small>
                            </summary>
                            <p><i>Created by $author</i></br></br><img src='$img_url'><br><br>$content<a href='$pdf_url' target='_blank'><div class='button primary-bg'>View PDF</div></a></p>
                            $actions
                        </details>
                    ";
                    $html .= $tmp;
                    $count++;
                }
                if($count == 0){
                    $html = "No newsletters available.";
                }
            break;
            case "subscribers":
                // // how to display subscribers
                while($fetched_data && $row = $fetched_data -> fetch_array()){
                    $email = $row[0];
                    $status = $row[1];
                    $modified = $row[3];
                    $classes = 'active-theme';
                    $actions = "<div class='button unsubscribe' onclick='unsubscribe_item(\"$email\")'>Unsubscribe</div>";
                    if($status == "unsubscribed"){
                        $classes = 'danger-bg';
                        $actions = "<div class='button subscribe' onclick='subscribe_item(\"$email\")'>Resubscribe</div>";
                    }
                    $tmp = "
                        <details class='newsletter-panel'>
                            <summary class='$classes'>
                                    $email
                                    <small>Modified on $modified</small>
                            </summary>
                            $actions
                        </details>
                    ";
                    $html .= $tmp;
                    $count++;
                }
                if($count==0){
                    $html = "No subscribers available.";
                }
            break;
            case "users":
                // how to display users
                while($fetched_data && $row = $fetched_data -> fetch_row()){
                    $email = $row[0];
                    $first_name = $row[2];
                    $last_name = $row[3];
                    $type = $row[4];
                    $modified = $row[6];
                    $actions = "";
                    if (($GLOBALS["current_user"]->get_email() != $email) && ($GLOBALS["current_user"]->get_type() == "admin" || $GLOBALS["current_user"]->get_type() == "root")){
                        $actions .= "<select onchange = 'changeUserRole(\"$email\", this.value);'>";
                        $actions .="<option selected disabled hidden>Change user Role</option>";
                        if($type != "admin"){
                            $actions .= "<option value='admin'>Make admin</option>";
                        }
                        if($type != "support"){
                            $actions .= "<option value='support'>Make support</option>";
                        }
                        if($type != "regular"){
                            $actions .= "<option value='regular'>Make regular</option>";
                        } 
                        $actions .="</select>";
                    }
                    $who_is_this ="";
                    if($GLOBALS["current_user"]->get_email() == $email){
                        $who_is_this = "[My account]";
                    }
                    $tmp = "
                        <details class='newsletter-panel'>
                            <summary>
                                <b>$who_is_this</b>
                                    $first_name $last_name ($email)<br>
                                    <small>Modified on $modified</small>
                            </summary>
                            $actions
                        </details>
                    ";
                    $html .= $tmp;
                    $count++;
                }
                if($count==0){
                    $html = "No users available.";
                }
            break;
        }
        echo $html;
    }
}
else if(isset($_REQUEST['request'])){
    $private_page = true;
    $ROOT =  __DIR__ ;
    include_once("$ROOT/controller.php");
    include_once("$ROOT/class_lib.php");
    $request = $_REQUEST["request"];
    $arguments = explode("~", $_REQUEST["arguments"]);
    if($request == "post-newsletter" || $request == "save-newsletter" ){
        $title = htmlentities($arguments[0]);
        $description = htmlentities($arguments[1]);
        $image = $arguments[2];
        // upload image and return url
        if ($image){
            $image = upload_image();
        }
        $pdf = $arguments[3];
        if ($pdf){
            $pdf = upload_pdf();
        }
        // upload pdf and return url
        $id = $arguments[4];
        // $mailing_list = $arguments[4];
        $mailing_list = "all";
        $creator_email = $current_user->get_email();

        $newsletter = new Newsletter();
        $newsletter->set_details($title, $description, $image, $pdf, $creator_email, $mailing_list, $id);
        $action = explode("-", $request)[0];
        if($action == "save"){
            $response = $current_user->save_newsletter($newsletter);
        }else if ($action == "post") {
            $response = $current_user->post_newsletter($newsletter);
        }
        if(!$response){
            $errors = join(";", $current_user->errors);
            echo "<b>Error:</b><br>$errors";
        }else{
            echo "The '$action' action was successful.";
        }
    }else if ($request == "subscribe"){
        $current_user->subscribe($arguments[0]);
        if($current_user->is_subscribed($arguments[0])){
            echo "User successfully subscribed.";
        }else{
            echo "<b>Error:</b> Something went wrong.";
        }
    }else if ($request == "unsubscribe"){
        $current_user->unsubscribe($arguments[0]);
        if(!$current_user->is_subscribed($arguments[0])){
            echo "User successfully unsubscribed.";
        }else{
            echo "<b>Error:</b> Something went wrong.";
        }
    }else if ($request == "delete-newsletter"){
        $newsletter = new Newsletter();
        $id =  $arguments[0];
        $newsletter->set_id($id);
        return $current_user->delete_newsletter($newsletter);
    }else if ($request == "archive-newsletter"){
        $newsletter = new Newsletter();
        $id =  $arguments[0];
        $newsletter->set_id($id);
        return $current_user->archive_newsletter($newsletter);
    }else if ($request == "restore-newsletter"){
        $newsletter = new Newsletter();
        $id =  $arguments[0];
        $newsletter->set_id($id);
        return $current_user->restore_newsletter($newsletter);
    } else if ($request == "fetch-newsletter-data-with-br"){
        $id =  $arguments[0];
        $newsletter = new Newsletter();
        $newsletter->set_id($id);
        $newsletter->refresh_from_db_by_id($id);
        $title = $newsletter->get_title();
        $content = nl2br($newsletter->get_content());
        $image = $newsletter->get_image();
        $pdf = $newsletter->get_pdf();
        echo "$title [~] $content [~] $image [~] $pdf [~] $id";
        return true;
    } else if ($request == "fetch-newsletter-data"){
        $id =  $arguments[0];
        $newsletter = new Newsletter();
        $newsletter->set_id($id);
        $newsletter->refresh_from_db_by_id($id);
        $title = $newsletter->get_title();
        $content = $newsletter->get_content();
        $image = $newsletter->get_image();
        $pdf = $newsletter->get_pdf();
        echo "$title [~] $content [~] $image [~] $pdf [~] $id";
        return true;
    } else if ($request == "load-newsletters"){
        displayForDashboard("newsletters", "*");
        return true;
    } else if ($request == "load-subscribers"){
        displayForDashboard("subscribers", "*");
        return true;
    } else if ($request == "load-users"){
        displayForDashboard("users", "*");
        return true;
    } else if ($request == "change-user-role"){
        if ($current_user->get_type() == 'admin' ||$current_user->get_type() == 'root'){
            
            // echo $arguments[1];
            $user = new User();
            $user->set_email($arguments[0]);
            $user->set_type($arguments[1]);
            $result = $current_user->update($user);
            if ($result){
                echo "User successfully updated.";
            }
            return $result;
        }
        return false;
    } else{
        echo "Request '$request' received but not processed.";
    }
}
else{
    echo '<script>
    window.location = "./";
    </script>';
}


$ROOT =  __DIR__ ;
include_once("$ROOT/controller.php");
include_once("$ROOT/class_lib.php");
function displayForDashboard($type, $filter){
    $fetched_data = "";
    $controller =  new Controller();
    $fetched_data = $controller->get($type, $filter);

    $html = "";
    $count = 0;
    switch($type){
        case "newsletters":
            // how to display newsletters
            while($fetched_data && $row = $fetched_data -> fetch_array()){
                $id = $row[0];
                $title = $row[1];
                $content = nl2br($row[2]);
                $img_url = $row[3];
                $pdf_url = $row[4];
                $status = $row[5];
                $modified = $row[7];
                $author_email = $row[8];
                $author = new User();
                $author->get_item_from_db($author_email);
                $author = "<a href='mailto:$author_email'>" . $author->get_full_name() . '</a>';
                $mailing_list = $row[9];
                $archive_button = "<span class='bottom-indicator tooltip inline-block shadow caution-bg' onclick='archive_item(\"newsletter\",\"$id\");'><img class='small-icon' src='assets/images/icon-archive.png'><span class='tooltip-text shadow rounded'>Archive Message</span></span>";
                $edit_button = "";
                $classes = 'active-theme';
                $icon = 'tick';
                $view_button = "<span class='bottom-indicator tooltip inline-block shadow disabled-theme'><img class='small-icon' src='assets/images/icon-view.png'><span class='tooltip-text shadow rounded'>View Message</span></span>";
                $download_button = "<span class='bottom-indicator tooltip inline-block shadow disabled-theme'><a href='$pdf_url' target='_blank'><img class='small-icon' src='assets/images/icon-download.png'></a><span class='tooltip-text shadow rounded'>Download PDF</span></span>";
                if ($status == "saved" || $status == "restored"){
                    $classes = 'normal-theme';
                    $icon = 'floppy-disk';
                    $archive_button = '';
                    $edit_button = "<span class='bottom-indicator tooltip inline-block shadow normal-theme' onclick='edit_item(\"newsletter\",\"$id\");'><img class='small-icon' src='assets/images/icon-edit.png'><span class='tooltip-text shadow rounded'>Edit Message</span></span>";
                }
                $restore_button = '';
                if($status == "archived"){
                    $restore_button = "<span onclick='restore_item(\"newsletter\",\"$id\");' class='bottom-indicator tooltip inline-block success-bg shadow'><img class='small-icon' src='assets/images/icon-plant.png'><span class='tooltip-text shadow rounded'>Restore Message</span></span>";
                    $classes = 'disabled-theme';
                    $archive_button = '';
                    $icon = 'archive';
                }
                $actions = "
                ";
                $summaryTheme = 'inactive-theme';
                $tmp = "
                    <details class='newsletter-panel'>
                        <summary class='normal-theme'>
                        <div class='indicator $classes shadow'><img class='small-icon' src='assets/images/icon-$icon.png'></div>
                        <div class='bottom-indicator-panel'>
                            $view_button
                            $edit_button
                            $restore_button
                            $archive_button
                            $download_button
                            <span onclick='delete_item(\"newsletter\",\"$id\");' class='bottom-indicator tooltip inline-block danger-bg shadow'><img class='small-icon' src='assets/images/icon-delete.png'><span class='tooltip-text shadow rounded'>Delete Message</span></span>
                        </div>
                            <img class='image' src='$img_url'>
                            <b>$title</b><br>
                            <small>Modified on $modified<br><b>Created by $author</b></small>
                        </summary>
                        <p>$content</p>
                        $actions
                    </details>
                ";
                $html .= $tmp;
                $count++;
            }
            if($count == 0){
                $html = "No newsletters available.";
            }
        break;
        case "subscribers":
            // // how to display subscribers 
            // $tmp = $fetched_data -> fetch_row()[0];
            // echo "<script>console.log('$tmp');</script>";
            while($fetched_data && $row = $fetched_data -> fetch_array()){
                $email = $row[0];
                $status = $row[1];
                $modified = $row[3];
                $classes = 'active-theme';
                $icon = 'tick';
                    $actions = "<div class='button unsubscribe right' onclick='unsubscribe_item(\"$email\")'>Unsubscribe</div>";
                    if($status == "unsubscribed"){
                        $classes = 'unsubscribe';
                        $icon = 'close';
                        $actions = "<div class='button subscribe right' onclick='subscribe_item(\"$email\")'>Resubscribe</div>";
                    }
                    $tmp = "
                        <details class='newsletter-panel'>
                            <summary class='normal-theme'>
                                $actions
                                <div class='indicator $classes shadow'><img class='small-icon' src='assets/images/icon-$icon.png'></div>
                                <b>$email</b><br>
                                <small>Modified on $modified<br><i>This user is <b>$status</b></i></small>
                            </summary>
                        </details>
                    ";
                $html .= $tmp;
                $count++;
            }
            if($count==0){
                $html = "No subscribers available.";
            }
        break;
        case "users":
            // how to display users
            while($fetched_data && $row = $fetched_data -> fetch_row()){
                $email = $row[0];
                $first_name = $row[2];
                $last_name = $row[3];
                $type = $row[4];
                $modified = $row[6];
                $actions = "";
                if (($GLOBALS["current_user"]->get_email() != $email) && ($GLOBALS["current_user"]->get_type() == "admin" || $GLOBALS["current_user"]->get_type() == "root")){
                    $actions .= "<select onchange = 'changeUserRole(\"$email\", this.value);'>";
                    $actions .="<option selected disabled hidden>Change user Role</option>";
                    if($type != "admin"){
                        $actions .= "<option value='admin'>Make admin</option>";
                    }
                    if($type != "support"){
                        $actions .= "<option value='support'>Make support</option>";
                    }
                    if($type != "regular"){
                        $actions .= "<option value='regular'>Make regular</option>";
                    } 
                    $actions .="</select>";
                }
                $who_is_this ="";
                $classes = "normal-theme";
                $icon = 'icon-man-user';
                if($GLOBALS["current_user"]->get_email() == $email || $type == 'root'){
                    $classes = "disabled-theme";
                    $icon = 'icon-system';
                }else{
                    if ($type == 'admin'){
                        $classes = "active-theme";
                        $icon = 'icon-admin-with-cogwheels';
                    }else if ($type == 'support'){
                        $classes = "inactive-theme";
                        $icon = 'icon-support-user';
                    }
                }
                $type = ucfirst($type);
                $tmp = "
                    <details class='newsletter-panel'>
                        <summary class='normal-theme'>
                            <div class='indicator $classes shadow'><img class='small-icon' src='assets/images/$icon.png'></div>
                            <div class='right'>$actions</div>
                            <b>$first_name $last_name</b><br><small>$type</small>
                        </summary>
                        <p>
                        <b>Email Address:</b> $email
                        <br>
                        <b>User role:</b> $type
                        <br>
                        <b>Modified:</b> $modified
                        </p>
                    </details>
                ";
                $html .= $tmp;
                $count++;
            }
            if($count==0){
                $html = "No users available.";
            }
        break;
    }
    echo $html;
}

function upload_image(){
    $target_dir = "../media/uploads/images/";
    $target_file = $target_dir . basename($_FILES['newsletter-image']["name"]);
    $url = "assets/media/uploads/images/"  . basename($_FILES['newsletter-image']["name"]);
    $uploadOk = 1;
    $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES['newsletter-image']["tmp_name"]);
    if($check !== false) {
        $uploadOk = 1;
    } else {
        echo "File is not an image.";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        $target_file = false;
    // if everything is ok, try to upload file
    } else {
    if (move_uploaded_file($_FILES['newsletter-image']["tmp_name"], $target_file)) {
        return $url;
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
    }
    return false;
}

function upload_pdf(){
    $target_dir = "../media/uploads/documents/";
    $target_file = $target_dir . basename($_FILES['newsletter-file']["name"]);
    $url = "assets/media/uploads/documents/"  . basename($_FILES['newsletter-file']["name"]);
    $uploadOk = 1;
    // Check if image file is a actual image or fake image
    $check = getimagesize($_FILES['newsletter-file']["tmp_name"]);
    if($_FILES['newsletter-file']['type'] == "application/pdf") {
        $uploadOk = 1;
    } else {
        echo "File is not a pdf.";
        $uploadOk = 0;
    }
    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        echo "Sorry, your file was not uploaded.";
        $target_file = false;
    // if everything is ok, try to upload file
    } else {
    if (move_uploaded_file($_FILES['newsletter-file']["tmp_name"], $target_file)) {
        return $url;
    } else {
        echo "Sorry, there was an error uploading your file.";
    }
    }
    return false;
}

?>