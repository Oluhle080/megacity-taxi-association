<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

if ($user['Role'] !== 'admin') {
    header('Location: dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $fullname = trim($_POST['fullname']);
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $role = trim($_POST['role']);

    if (empty($fullname) || empty($username) || empty($password) || empty($role)) {
        $error = 'All fields are required.';
    } elseif (strlen($password) < 4) {
        $error = 'Password must be at least 4 characters.';
    } else {
        $username = trim($username);
        $checkStmt = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
        $checkStmt->bind_param('s', $username);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            $error = 'That username is already in use.';
        } else {
            $fullname = trim($fullname);
            $role = trim($role);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $insertStmt = $conn->prepare("INSERT INTO users (FullName, Username, Password, Role) VALUES (?, ?, ?, ?)");
            $insertStmt->bind_param('ssss', $fullname, $username, $password_hash, $role);
            $insertStmt->execute();
            $insertStmt->close();

            $success = 'User created successfully.';
        }

        $checkStmt->close();
    }
}

if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE UserID = ?");
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();
    $deleteStmt->close();
    header('Location: users.php');
    exit();
}

$result = $conn->query("SELECT * FROM users ORDER BY UserID DESC");
$total_users = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Users - Megacity Taxi</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#0a0a0f; color:#e8e8f0; font-family:Arial,sans-serif; display:flex; min-height:100vh; }
.sidebar { width:220px; background:#13131a; border-right:1px solid #2a2a3a; display:flex; flex-direction:column; position:fixed; top:0; bottom:0; }
.logo { padding:24px 20px; border-bottom:1px solid #2a2a3a; }
.logo h1 { color:#f5c518; font-size:18px; }
.logo p { color:#6b6b80; font-size:11px; margin-top:4px; }
nav { padding:16px 10px; flex:1; }
nav a { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; color:#6b6b80; text-decoration:none; font-size:14px; margin-bottom:4px; }
nav a:hover { background:#1a1a24; color:#fff; }
nav a.active { background:#f5c51820; color:#f5c518; }
.sidebar-footer { padding:16px; border-top:1px solid #2a2a3a; font-size:12px; color:#6b6b80; }
.main { margin-left:220px; flex:1; }
.topbar { background:#13131a; border-bottom:1px solid #2a2a3a; padding:16px 28px; display:flex; justify-content:space-between; align-items:center; }
.topbar h2 { font-size:18px; }
.content { padding:28px; }
.form-card { background:#1a1a24; border:1px solid #2a2a3a; border-radius:10px; padding:24px; margin-bottom:24px; }
.form-title { color:#f5c518; font-size:15px; font-weight:bold; margin-bottom:18px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
label { display:block; color:#6b6b80; font-size:11px; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; }
input, select { width:100%; background:#0a0a0f; border:1px solid #2a2a3a; border-radius:8px; padding:10px 12px; color:#fff; font-size:14px; outline:none; }
input:focus, select:focus { border-color:#f5c518; }
.btn { padding:10px 22px; border-radius:8px; border:none; font-size:14px; font-weight:bold; cursor:pointer; }
.btn-primary { background:#f5c518; color:#000; }
.btn-primary:hover { background:#e0b010; }
.btn-danger { background:transparent; color:#f85149; border:1px solid #f85149; padding:6px 12px; font-size:12px; }
.btn-danger:hover { background:#f85149; color:#fff; }
.card { background:#1a1a24; border:1px solid #2a2a3a; border-radius:10px; overflow:hidden; }
.card-header { padding:14px 18px; border-bottom:1px solid #2a2a3a; display:flex; justify-content:space-between; align-items:center; }
.card-header span { font-size:14px; font-weight:bold; }
.badge-count { background:#f5c51820; color:#f5c518; padding:2px 10px; border-radius:20px; font-size:12px; }
table { width:100%; border-collapse:collapse; }
th { background:#13131a; padding:10px 16px; text-align:left; font-size:11px; text-transform:uppercase; color:#6b6b80; }
td { padding:12px 16px; border-bottom:1px solid #2a2a3a; font-size:13px; vertical-align:top; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#13131a; }
.alert { border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:13px; }
.alert-error { background:#f8514920; color:#f85149; border:1px solid #f85149; }
.alert-success { background:#3fb95020; color:#3fb950; border:1px solid #3fb950; }
</style>
</head>
<body>

<div class="sidebar">
    <div class="logo">
        <h1>🚕 Megacity</h1>
        <p>Taxi Management System</p>
    </div>
    <nav>
        <a href="dashboard.php">📊 Dashboard</a>
        <a href="drivers.php">👤 Drivers</a>
        <a href="taxis.php">🚕 Taxis</a>
        <a href="payments.php">💰 Payments</a>
        <a href="routes.php">🗺️ Routes</a>
        <a href="assignments.php">📋 Assignments</a>
        <a href="disputes.php">⚠️ Disputes</a>
        <a href="users.php" class="active">👥 Users</a>
        <a href="logout.php">🚪 Logout</a>
    </nav>
    <div class="sidebar-footer">
        Logged in as: <?php echo $user['FullName']; ?><br>
        Role: <?php echo strtoupper($user['Role']); ?>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h2>👥 Users</h2>
        <span style="color:#6b6b80; font-size:13px;"><?php echo date('l, d F Y'); ?></span>
    </div>
    <div class="content">

        <?php if (isset($error)) { ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php } ?>

        <?php if (isset($success)) { ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php } ?>

        <div class="form-card">
            <div class="form-title">➕ Add New User</div>
            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="fullname" placeholder="e.g. Thabo Nkosi" required>
                    </div>
                    <div>
                        <label>Username</label>
                        <input type="text" name="username" placeholder="e.g. thabo" required>
                    </div>
                    <div>
                        <label>Password</label>
                        <input type="password" name="password" placeholder="Minimum 4 characters" required>
                    </div>
                    <div>
                        <label>Role</label>
                        <select name="role" required>
                            <option value="">-- Select role --</option>
                            <option value="admin">Admin</option>
                            <option value="manager">Manager</option>
                            <option value="clerk">Clerk</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_user" class="btn btn-primary">Create User</button>
            </form>
        </div>

        <div class="card">
            <div class="card-header">
                <span>All Users</span>
                <span class="badge-count"><?php echo $total_users; ?> total</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $index = 1;
                    while ($row = $result->fetch_assoc()) {
                        echo '<tr>';
                        echo '<td>' . $index . '</td>';
                        echo '<td>' . htmlspecialchars($row['FullName']) . '</td>';
                        echo '<td>' . htmlspecialchars($row['Username']) . '</td>';
                        echo '<td>' . htmlspecialchars(ucfirst($row['Role'])) . '</td>';
                        echo '<td>';
                        if ($row['Username'] !== $user['Username']) {
                            echo '<a href="users.php?delete=' . $row['UserID'] . '" class="btn btn-danger" onclick="return confirm(\'Delete this user?\')">Delete</a>';
                        } else {
                            echo '<span style="color:#6b6b80; font-size:12px;">Current user</span>';
                        }
                        echo '</td>';
                        echo '</tr>';
                        $index++;
                    }
                    ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>
