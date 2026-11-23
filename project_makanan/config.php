<?php

$host="localhost";
$username="root";
$password="";
$db="cart_db";

$conn = mysqli_connect($host, $username, $password, $db);

if($conn)
{
    echo "Koneksi berhasil";
}
else
{
    echo "Koneksi gagal".mysqli_connect_error();
}