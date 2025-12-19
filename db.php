<?php
$host = 'sql113.infinityfree.com';  
$db = 'if0_40713950_TidyTasks_db';  
$user = 'if0_40713950';  // 
$pass = 'DoEktnWYUI';  // 
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
