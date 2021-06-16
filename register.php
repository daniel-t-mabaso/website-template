<?php include_once("./assets/php/session.php");
    $public_page = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php
        include_once("./assets/php/dependencies.php");
    ?>
    <title>Register</title>
</head>
<body>
    <?php include 'assets/php/header.php'?>
    <div class="content minimum-max-screen">
        <form action="" method="post">
            <h2>REGISTER</h2>
            <P>Sign up as a new user</P>
            <input class="form-input" type="text" name="first_name" id="first-name" placeholder="First name">
            <input class="form-input" type="text" name="last_name" id="last-name" placeholder="Last name">
            <input class="form-input" type="email" name="email" id="email-address" placeholder="Email">
            <input class="form-input" type="password" name="password" id="password" placeholder="Password">
            <input class="form-input" type="password" name="confirm_password" id="confirm-password" placeholder="Confirm password">
            <input class="button primary-bg" type="submit" value="Register" name="register">
            <p>Already have an account? <a href="login.php">Sign in</a></p>
        </form>
        <?php include_once("./error.php");?>
    </div>
    <?php include 'assets/php/footer.php'?>
</body>
</html>