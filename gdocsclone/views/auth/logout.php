<?php
session_start();
session_unset();
session_destroy();
header('Location: ../auth/login.php'); // Fixed relative path to login.php
exit;
