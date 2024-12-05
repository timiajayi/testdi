<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // For testing purposes, let's use simple credentials
    if ($username === 'admin' && $password === 'password') {
        $_SESSION['user'] = $username;
        $_SESSION['authenticated'] = true;
        header('Location: home.php');
        exit;
    } else {
        $error = "Invalid credentials";
    }
}

// If there's an error, or if it's not a POST request, show the login form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - ID Card Generator</title>
    <!-- Use existing CSS styles -->
</head>
<body>
    <div class="form-container">
        <?php if (isset($error)): ?>
            <div style="color: red; margin-bottom: 10px;"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="post" action="login.php">
            <div class="form-group">
                <label>Username</label>
                <input type="text" name="username" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>
