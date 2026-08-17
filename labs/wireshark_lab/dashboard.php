<!-- mlabs/labs/wireshark_lab/dashboard.php -->
<?php
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Secure Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #e8f5e9; padding: 50px; text-align: center;}
        .success { color: #2e7d32; }
    </style>
</head>
<body>
    <h1 class="success">Access Granted</h1>
    <p>Welcome to the secure administrative dashboard.</p>
    <p>Flag: <strong>FLAG{w1r3sh4rk_sn1ff3r_m4st3r}</strong></p>
    <a href="index.php">Logout</a>
</body>
</html>
