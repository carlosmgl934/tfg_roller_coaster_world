<?php

require_once "../db_conexion.php";
session_start();

if(!isset($_SESSION["user"])){
    header("Location: ../../web/views/html/contact.html");
    exit;
}


try{
$db = new DBConexion();
$sql = ("INSERT INTO contact_messages (user_id, user_name, user_email, subject, user_message, is_read, created_at) VALUES (:user_id, :user_name, :user_email, :subject, :user_message, :is_read, :created_at)");
$stmt = $conn->prepare($sql)

$stmt->bindValue(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(':user_name', $username, PDO::PARAM_STR);
$stmt->bindValue(':user_email', $_SESSION['user_email'], PDO::STR);



}catch(PDOException){
    header("Location: ../../web/views/html/contact.html");
    exit;
}




