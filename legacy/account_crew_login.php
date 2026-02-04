<?php
$state = $_GET['state'] ?? "0";
?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Cache-Control" content="no-cache">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="/css/styles.css?v=14">
    </head>
<body>
    <?php if ( $state === "0" ) { ?><p class = "p_class" >Please log in.<?php } ?></p>
    <?php if ( $state === "1" ) { ?><p class = "p_class" >Login failed. Please check your name and password.<?php } ?></p>
    <?php if ( $state === "2" ) { ?><p class = "p_class" >An account with that name already exists. Please log in.<?php } ?></p>
    <?php if ( $state === "3" ) { ?><p class = "p_class" >Registration successful. Please log in.<?php } ?></p>
    <form class = "form_class" action="/account_crew_login_process.php" method="POST">
        <label class = "label_class" >First name:</label>
        <input class = "text_class" type="text" name="fname" required><br><br>
        <label class = "label_class" >Last name:</label>
        <input class = "text_class" type="text" name="lname" required><br><br>

        <label class = "label_class" >Password:</label>
        <input class = "text_class" type="password" name="password" required><br><br>
        
        <button class = "button_class" type="submit">Login</button>
    </form>
    <p><a class = "p_class" href="/account_crew_data_form.html">Don't have an account yet? Register here</a></p>
</body>
</html>