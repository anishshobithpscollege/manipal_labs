<!-- mlabs/labs/wireshark_lab/index.php -->
<?php
session_start();

$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    // Hardcoded credentials for the lab
    if ($username === 'admin' && $password === 'supersecret123') {
        $_SESSION['logged_in'] = true;
        header('Location: dashboard.php');
        exit;
    } else {
        $error_msg = "Invalid credentials!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Employee Portal Login</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f9; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .login-box { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); width: 300px; text-align: center; }
        input[type="text"], input[type="password"] { width: 90%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; width: 100%; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .error { color: red; font-size: 14px; }
        .info { font-size: 12px; color: #666; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Employee Portal</h2>
        <?php if ($error_msg) echo "<p class='error'>$error_msg</p>"; ?>
        <form method="POST" action="index.php">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" value="Login">
        </form>
        <p class="info">Lab Goal: Capture the POST request via HTTP to find the password, then try via HTTPS to see the encryption.</p>
    </div>
</body>
</html>
