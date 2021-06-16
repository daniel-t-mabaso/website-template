<div id="footer-panel">
    <div class="content">
        <div><a href="index.php">Item 1</a></div>
        <div><a href="item2.php">Item 2</a></div>
        <div><a href="unsubscribe.php">Unsubscribe</a></div>
        <?php
            if ($_SESSION['custom_website_auth'] ?? false){
                if($current_user->get_type() == "root" || $current_user->get_type() == "admin" || $current_user->get_type() == "support")
                {
                    echo '<div><a href="dashboard.php">Dashboard</a></div>';
                }
                echo '<div><a href="logout">Logout</a></div>';
            }
            else{
                echo '<div><a href="login.php">Login</a></div>';
            }
        ?>
    </div>
</div>