<?php
session_start();
include 'db.php';

// If not logged in, send back to login page
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

// ADDING A NEW DRIVER
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_driver'])) {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $license = trim($_POST['license']);

    // Remove spaces from phone number before checking
    $phone_clean = str_replace(' ', '', $phone);

    // Check if phone number is exactly 10 digits
    if (!preg_match('/^[0-9]{10}$/', $phone_clean)) {
        $error = "Phone number must be exactly 10 digits!";
    }
    // Check if license already exists in database
    $licenseStmt = $conn->prepare("SELECT DriverID FROM drivers WHERE LicenseNumber = ?");
    $licenseStmt->bind_param('s', $license);
    $licenseStmt->execute();
    $licenseStmt->store_result();

    if ($licenseStmt->num_rows > 0) {
        $error = "This license number already exists!";
    }
    // Everything is fine — save to database
    else {
        $insertStmt = $conn->prepare("INSERT INTO drivers (Name, Phone, LicenseNumber) VALUES (?, ?, ?)");
        $insertStmt->bind_param('sss', $name, $phone_clean, $license);
        $insertStmt->execute();
        $insertStmt->close();

        $success = "Driver added successfully!";
    }

    $licenseStmt->close();
}

// DELETING A DRIVER
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM drivers WHERE DriverID = ?");
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();
    $deleteStmt->close();
    header('Location: drivers.php');
    exit();
}

// GET ALL DRIVERS FROM DATABASE
$result = $conn->query("SELECT * FROM drivers ORDER BY DriverID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Drivers - Megacity Taxi</title>
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
.form-grid { display:grid; grid-template-columns:1fr 1fr 1fr; gap:16px; margin-bottom:16px; }
label { display:block; color:#6b6b80; font-size:11px; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; }
input { width:100%; background:#0a0a0f; border:1px solid #2a2a3a; border-radius:8px; padding:10px 12px; color:#fff; font-size:14px; outline:none; }
input:focus { border-color:#f5c518; }
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
td { padding:12px 16px; border-bottom:1px solid #2a2a3a; font-size:13px; }
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
        <a href="drivers.php" class="active">👤 Drivers</a>
        <a href="taxis.php">🚕 Taxis</a>
        <a href="payments.php">💰 Payments</a>
        <a href="routes.php">🗺️ Routes</a>
        <a href="assignments.php">📋 Assignments</a>
        <a href="disputes.php">⚠️ Disputes</a>
        <?php if ($user['Role'] == 'admin') { ?>
        <a href="users.php">👥 Users</a>
        <?php } ?>
        <a href="logout.php">🚪 Logout</a>
    </nav>
    <div class="sidebar-footer">
        Logged in as: <?php echo $user['FullName']; ?><br>
        Role: <?php echo strtoupper($user['Role']); ?>
    </div>
</div>

<div class="main">
    <div class="topbar">
        <h2>👤 Drivers</h2>
        <span style="color:#6b6b80; font-size:13px;"><?php echo date('l, d F Y'); ?></span>
    </div>
    <div class="content">

        <?php if ($user['Role'] == 'admin' || $user['Role'] == 'clerk') { ?>
        <div class="form-card">
            <div class="form-title">➕ Add New Driver</div>

            <?php if (isset($error)) { ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php } ?>
            <?php if (isset($success)) { ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php } ?>

            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Full Name</label>
                        <input type="text" name="name" placeholder="e.g. Sipho Dlamini" required>
                    </div>
                    <div>
                        <label>Phone Number (10 digits)</label>
                        <input type="text" name="phone" placeholder="e.g. 0712345678" maxlength="10" required>
                    </div>
                    <div>
                        <label>License Number</label>
                        <input type="text" name="license" placeholder="e.g. LIC1001" required>
                    </div>
                </div>
                <button type="submit" name="add_driver" class="btn btn-primary">Save Driver</button>
            </form>
        </div>
        <?php } ?>

        <div class="card">
            <div class="card-header">
                <span>All Drivers</span>
                <span class="badge-count"><?php echo $result->num_rows; ?> total</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>License Number</th>
                        <?php if ($user['Role'] == 'admin') { ?>
                        <th>Action</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                        <td>{$i}</td>
                        <td><strong>{$row['Name']}</strong></td>
                        <td>{$row['Phone']}</td>
                        <td>{$row['LicenseNumber']}</td>";
                    if ($user['Role'] == 'admin') {
                        echo "<td><a href='drivers.php?delete={$row['DriverID']}' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete this driver?\")'>Delete</a></td>";
                    }
                    echo "</tr>";
                    $i++;
                }
                ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

</body>
</html>