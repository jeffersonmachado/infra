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
$username = $_POST['username'];
$password = $_POST['password'];
$sql = "SELECT id FROM mailbox WHERE username = \"$username\" and password = encrypt(\"$password\",\"$password\")";
$result = $mysqli->query($sql);
if ($result->num_rows > 0) {
	echo "true";
} else {
	echo "false";
}
?>
