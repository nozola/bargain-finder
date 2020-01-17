<?php

$host="127.0.0.1";
$user="Alonzo";
$password="Chukasa22";
$dbname="genealogy";

$link = mysqli_connect($host, $user, $password, $dbname);

// if (mysqli_connect_errno()) {
//     printf("Connect failed: %s\n", mysqli_connect_error());
//     exit();
// }
//echo "Connected Successfully";

$con = new mysqli($host, $user, $password, $dbname)
	or die ('Could not connect to the database server' . mysqli_connect_error());

// Check connection
if ($con->connect_error) {
  die("Connection failed: " . $con->connect_error);
}
//echo "Connected successfully<br><br>";

//$con->close();

?>
