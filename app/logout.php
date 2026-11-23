<?php
require_once __DIR__ . '/library/auth.php';
logout();
redirect('login.php');
