<?php
session_start();
include 'db.php';

// If not logged in send back to login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

// ADDING A NEW TAXI
// This runs when someone clicks the Save button
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_taxi'])) {
    $reg = trim($_POST['registration']);
    $driver_id = intval($_POST['driver_id']);

    $stmt1 = $conn->prepare("SELECT TaxiID FROM taxis WHERE RegistrationNumber = ?");
    $stmt1->bind_param('s', $reg);
    $stmt1->execute();
    $stmt1->store_result();

    $stmt2 = $conn->prepare("SELECT TaxiID FROM taxis WHERE DriverID = ?");
    $stmt2->bind_param('i', $driver_id);
    $stmt2->execute();
    $stmt2->store_result();

    if ($stmt1->num_rows > 0) {
        $error = "This registration number already exists!";
    }
    elseif ($stmt2->num_rows > 0) {
        $error = "This driver already has a taxi assigned!";
    }
    else {
        $insertStmt = $conn->prepare("INSERT INTO taxis (RegistrationNumber, DriverID) VALUES (?, ?)");
        $insertStmt->bind_param('si', $reg, $driver_id);
        $insertStmt->execute();
        $insertStmt->close();

        $success = "Taxi added successfully!";
    }

    $stmt1->close();
    $stmt2->close();
}

// DELETING A TAXI
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM taxis WHERE TaxiID = ?");
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();
    $deleteStmt->close();
    header('Location: taxis.php');
    exit();
}

// GET ALL TAXIS — also fetch the driver name by joining the two tables
// JOIN means: connect taxis table with drivers table using DriverID
$result = $conn->query("SELECT t.*, d.Name as DriverName FROM taxis t LEFT JOIN drivers d ON t.DriverID = d.DriverID ORDER BY t.TaxiID DESC");

// GET ALL DRIVERS for the dropdown in the form
$drivers = $conn->query("SELECT * FROM drivers ORDER BY Name");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Taxis - Megacity Taxi</title>
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
select option { background:#0a0a0f; }
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
        <a href="drivers.php">👤 Drivers</a>
        <a href="taxis.php" class="active">🚕 Taxis</a>
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
        <h2>🚕 Taxis</h2>
        <span style="color:#6b6b80; font-size:13px;"><?php echo date('l, d F Y'); ?></span>
    </div>
    <div class="content">

        <?php if ($user['Role'] == 'admin' || $user['Role'] == 'clerk') { ?>
        <div class="form-card">
            <div class="form-title">➕ Add New Taxi</div>

            <?php if (isset($error)) { ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php } ?>
            <?php if (isset($success)) { ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php } ?>

            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Registration Number</label>
                        <!-- This is where you type the taxi plate number -->
                        <input type="text" name="registration" placeholder="e.g. ND 123-456" required>
                    </div>
                    <div>
                        <label>Assign to Driver</label>
                        <!-- This dropdown shows all drivers from the database -->
                        <select name="driver_id" required>
                            <option value="">-- Select Driver --</option>
                            <?php while ($d = $drivers->fetch_assoc()) { ?>
                                <option value="<?php echo $d['DriverID']; ?>"><?php echo $d['Name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
                <button type="submit" name="add_taxi" class="btn btn-primary">Save Taxi</button>
            </form>
        </div>
        <?php } ?>

        <div class="card">
            <div class="card-header">
                <span>All Taxis</span>
                <span class="badge-count"><?php echo $result->num_rows; ?> total</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Registration Number</th>
                        <th>Assigned Driver</th>
                        <?php if ($user['Role'] == 'admin') { ?>
                        <th>Action</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                while ($row = $result->fetch_assoc()) {
                    $driver_name = $row['DriverName'] ? $row['DriverName'] : 'Not Assigned';
                    echo "<tr>
                        <td>{$i}</td>
                        <td><strong>{$row['RegistrationNumber']}</strong></td>
                        <td>{$driver_name}</td>";
                    if ($user['Role'] == 'admin') {
                        echo "<td><a href='taxis.php?delete={$row['TaxiID']}' class='btn btn-danger' onclick='return confirm(\"Are you sure you want to delete this taxi?\")'>Delete</a></td>";
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