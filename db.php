<?php
// Connect to database.

require_once __DIR__.'/config.php';

$aDb = new PDO("mysql:host=$dbHostname;port=$dbPort;dbname=$dbName;charset=UTF8",$dbUsername,$dbPassword);

?>
