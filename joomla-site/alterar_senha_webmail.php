<?php
require("configuration.php");
$config = new JConfig();
$mysqli = new mysqli($config->host, $config->user, $config->password, $config->db);
//if ($mysqli->connect_errno) {
    //echo "Sorry, this website is experiencing problems.";
    //echo "Error: Failed to make a MySQL connection, here is why: \n";
    //echo "Errno: " . $mysqli->connect_errno . "\n";
    //echo "Error: " . $mysqli->connect_error . "\n";
//}
$username = $_POST['email'];
$password = $_POST['senha'];
$new_password = $_POST['senha_nova'];
$sql = "UPDATE mailbox SET password = encrypt(\"$new_password\",\"$new_password\") WHERE username = \"$username\" and password = encrypt(\"$password\",\"$password\")";
$ret = $mysqli->query($sql);
if ($ret > 0) {
?>
    <div class="alert alert-success alert-dismissible fade show">
        <strong>Successo!</strong> Senha alterada.
    </div>
<?php
} else {
?>
    <div class="alert alert-danger alert-dismissible fade show">
        <strong>Erro!</strong> Falha na alteração da senha. 
    </div>
<?php
}
$mysqli -> close();
?>
