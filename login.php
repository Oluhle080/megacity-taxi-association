<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE Username='$username'";
    $result = $conn->query($sql);

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        $storedPassword = $user['Password'];

        if (password_verify($password, $storedPassword) || $storedPassword === $password) {
            if ($storedPassword !== $password) {
                $newHash = password_hash($password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET Password='" . $conn->real_escape_string($newHash) . "' WHERE UserID=" . intval($user['UserID']));
                $user['Password'] = $newHash;
            }
            $_SESSION['user'] = $user;
            header('Location: dashboard.php');
            exit();
        }
    }

    $error = "Wrong username or password!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Megacity Taxi - Login</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }

body {
    background: #0a0a0f;
    font-family: Arial, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
}

.login-box {
    background: #1a1a24;
    border: 1px solid #2a2a3a;
    border-radius: 12px;
    padding: 40px;
    width: 100%;
    max-width: 400px;
}

.logo {
    text-align: center;
    margin-bottom: 30px;
}

.logo h1 {
    color: #f5c518;
    font-size: 28px;
}

.logo p {
    color: #6b6b80;
    font-size: 13px;
    margin-top: 5px;
}

label {
    display: block;
    color: #6b6b80;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 6px;
}

input {
    width: 100%;
    background: #0a0a0f;
    border: 1px solid #2a2a3a;
    border-radius: 8px;
    padding: 12px;
    color: #fff;
    font-size: 14px;
    margin-bottom: 20px;
    outline: none;
}

input:focus {
    border-color: #f5c518;
}

button {
    width: 100%;
    background: #f5c518;
    color: #000;
    border: none;
    border-radius: 8px;
    padding: 13px;
    font-size: 15px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #e0b010;
}

.error {
    background: #f8514920;
    color: #f85149;
    border: 1px solid #f85149;
    border-radius: 8px;
    padding: 10px;
    font-size: 13px;
    margin-bottom: 20px;
    text-align: center;
}

.hint {
    margin-top: 20px;
    background: #13131a;
    border-radius: 8px;
    padding: 14px;
}

.hint p {
    color: #6b6b80;
    font-size: 12px;
    margin-bottom: 6px;
}

.hint span {
    color: #f5c518;
    font-weight: bold;
}
</style>
</head>
<body>

<div class="login-box">
    <div class="logo">
        <h1>🚕 Megacity</h1>
        <p>Taxi Association Management System</p>
    </div>

    <?php if (isset($error)) { ?>
        <div class="error"><?php echo $error; ?></div>
    <?php } ?>

    <form method="POST">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter your username" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter your password" required>

        <button type="submit">Login</button>
    </form>

    <div class="hint">
        <p>Test accounts:</p>
        <p>👑 Admin: <span>admin</span> / <span>admin123</span></p>
        <p>🧑‍💼 Manager: <span>manager</span> / <span>manager123</span></p>
        <p>🧑‍💻 Clerk: <span>clerk</span> / <span>clerk123</span></p>
    </div>
</div>

</body>
</html>