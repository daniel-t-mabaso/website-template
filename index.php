<?php include_once("./assets/php/session.php");
    $public_page = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include_once("./assets/php/dependencies.php")?>
    <title>Believe and Abide Bible Info Centre</title>
</head>
<body>
    <?php include 'assets/php/header.php'?>
    <div class="content minimum-max-screen">
        <h2>Item 1 <?=$current_user->get_full_name()?></h2>
        <?php include 'assets/php/extras.php';?>
    </div>
    <?php include 'assets/php/footer.php'?>
</body>
</html>