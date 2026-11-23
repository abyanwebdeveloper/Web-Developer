<?php

$host="localhost";
$username="root";
$password="";
$db="cart_db";

$conn = mysqli_connect($host, $username, $password, $db);

if(!$conn)
{
    die("Koneksi gagal: ".mysqli_connect_error());
}
