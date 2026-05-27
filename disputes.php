<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_dispute'])) {
    $driver_id = intval($_POST['driver_id']);
    $description = trim($_POST['description']);

    if ($driver_id <= 0) {
        $error = "Please select a driver.";
    } elseif (empty($description)) {
        $error = "Please provide a dispute description.";
    } else {
        $insertStmt = $conn->prepare("INSERT INTO disputes (DriverID, Description, Status) VALUES (?, ?, 'open')");
        $insertStmt->bind_param('is', $driver_id, $description);
        $insertStmt->execute();
        $insertStmt->close();
        $success = "Dispute was added successfully.";
    }
}

if (isset($_GET['resolve'])) {
    $id = intval($_GET['resolve']);
    $updateStmt = $conn->prepare("UPDATE disputes SET Status = 'resolved' WHERE DisputeID = ?");
    $updateStmt->bind_param('i', $id);
    $updateStmt->execute();
    $updateStmt->close();
    header('Location: disputes.php');
    exit();
}

if (isset($_GET['reopen'])) {
    $id = intval($_GET['reopen']);
    $updateStmt = $conn->prepare("UPDATE disputes SET Status = 'open' WHERE DisputeID = ?");
    $updateStmt->bind_param('i', $id);
    $updateStmt->execute();
    $updateStmt->close();
    header('Location: disputes.php');
    exit();
}

if (isset($_GET['delete']) && $user['Role'] === 'admin') {
    $id = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM disputes WHERE DisputeID = ?");
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();
    $deleteStmt->close();
    header('Location: disputes.php');
    exit();
}

$open_count = $conn->query("SELECT COUNT(*) as total FROM disputes WHERE Status='open'")->fetch_assoc()['total'];
$total_count = $conn->query("SELECT COUNT(*) as total FROM disputes")->fetch_assoc()['total'];
$resolved_count = $conn->query("SELECT COUNT(*) as total FROM disputes WHERE Status='resolved'")->fetch_assoc()['total'];

$drivers = $conn->query("SELECT * FROM drivers ORDER BY Name");
$result = $conn->query("SELECT dp.*, d.Name as DriverName FROM disputes dp JOIN drivers d ON dp.DriverID = d.DriverID ORDER BY dp.DisputeID DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Disputes - Megacity Taxi</title>
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
.stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:24px; }
.stat { background:#1a1a24; border:1px solid #2a2a3a; border-radius:10px; padding:20px; border-top:3px solid #f5c518; }
.stat-label { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#6b6b80; margin-bottom:8px; }
.stat-value { font-size:28px; font-weight:bold; color:#f5c518; }
.form-card { background:#1a1a24; border:1px solid #2a2a3a; border-radius:10px; padding:24px; margin-bottom:24px; }
.form-title { color:#f5c518; font-size:15px; font-weight:bold; margin-bottom:18px; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px; }
label { display:block; color:#6b6b80; font-size:11px; text-transform:uppercase; letter-spacing:1px; margin-bottom:6px; }
input, select, textarea { width:100%; background:#0a0a0f; border:1px solid #2a2a3a; border-radius:8px; padding:10px 12px; color:#fff; font-size:14px; outline:none; }
textarea { min-height:100px; resize:vertical; }
input:focus, select:focus, textarea:focus { border-color:#f5c518; }
.btn { padding:10px 22px; border-radius:8px; border:none; font-size:14px; font-weight:bold; cursor:pointer; }
.btn-primary { background:#f5c518; color:#000; }
.btn-primary:hover { background:#e0b010; }
.btn-danger { background:transparent; color:#f85149; border:1px solid #f85149; padding:6px 12px; font-size:12px; }
.btn-danger:hover { background:#f85149; color:#fff; }
.btn-secondary { background:#2f2f3a; color:#e8e8f0; border:1px solid #2a2a3a; padding:6px 12px; font-size:12px; }
.btn-secondary:hover { background:#383842; }
.card { background:#1a1a24; border:1px solid #2a2a3a; border-radius:10px; overflow:hidden; }
.card-header { padding:14px 18px; border-bottom:1px solid #2a2a3a; display:flex; justify-content:space-between; align-items:center; }
.card-header span { font-size:14px; font-weight:bold; }
.badge-count { background:#f5c51820; color:#f5c518; padding:2px 10px; border-radius:20px; font-size:12px; }
table { width:100%; border-collapse:collapse; }
th { background:#13131a; padding:10px 16px; text-align:left; font-size:11px; text-transform:uppercase; color:#6b6b80; }
td { padding:12px 16px; border-bottom:1px solid #2a2a3a; font-size:13px; vertical-align:top; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#13131a; }
.badge { padding:4px 10px; border-radius:20px; font-size:11px; font-weight:bold; text-transform:uppercase; display:inline-block; }
.badge-open { background:#f8514920; color:#f85149; }
.badge-resolved { background:#3fb95020; color:#3fb950; }
.alert { border-radius:8px; padding:10px 14px; margin-bottom:16px; font-size:13px; }
.alert-error { background:#f8514920; color:#f85149; border:1px solid #f85149; }
.alert-success { background:#3fb95020; color:#3fb950; border:1px solid #3fb950; }
.action-group { display:flex; flex-wrap:wrap; gap:8px; }
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
        <a href="disputes.php" class="active">⚠️ Disputes</a>
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
        <h2>⚠️ Disputes</h2>
        <span style="color:#6b6b80; font-size:13px;"><?php echo date('l, d F Y'); ?></span>
    </div>
    <div class="content">

        <?php if (isset($error)) { ?>
            <div class="alert alert-error">❌ <?php echo $error; ?></div>
        <?php } ?>

        <?php if (isset($success)) { ?>
            <div class="alert alert-success">✅ <?php echo $success; ?></div>
        <?php } ?>

        <?php if ($user['Role'] == 'admin' || $user['Role'] == 'clerk') { ?>
        <div class="form-card">
            <div class="form-title">➕ Report New Dispute</div>
            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Driver</label>
                        <select name="driver_id" required>
                            <option value="">-- Select Driver --</option>
                            <?php while ($driver = $drivers->fetch_assoc()) { ?>
                                <option value="<?php echo $driver['DriverID']; ?>"><?php echo $driver['Name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label>Status</label>
                        <select disabled>
                            <option value="open">Open</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label>Description</label>
                    <textarea name="description" placeholder="Enter dispute details..." required></textarea>
                </div>
                <button type="submit" name="add_dispute" class="btn btn-primary" style="margin-top:16px;">Save Dispute</button>
            </form>
        </div>
        <?php } ?>

        <div class="stats">
            <div class="stat">
                <div class="stat-label">Open Disputes</div>
                <div class="stat-value"><?php echo $open_count; ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Resolved Disputes</div>
                <div class="stat-value"><?php echo $resolved_count; ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Disputes</div>
                <div class="stat-value"><?php echo $total_count; ?></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <span>All Disputes</span>
                <span class="badge-count"><?php echo $result->num_rows; ?> total</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1;
                    while ($row = $result->fetch_assoc()) {
                        $statusClass = $row['Status'] === 'resolved' ? 'badge-resolved' : 'badge-open';
                        echo "<tr>";
                        echo "<td>{$i}</td>";
                        echo "<td><strong>{$row['DriverName']}</strong></td>";
                        echo "<td>" . htmlspecialchars($row['Description']) . "</td>";
                        echo "<td><span class='badge {$statusClass}'>" . ucfirst($row['Status']) . "</span></td>";
                        echo "<td><div class='action-group'>";
                        if ($user['Role'] == 'admin' || $user['Role'] == 'clerk') {
                            if ($row['Status'] === 'open') {
                                echo "<a href='disputes.php?resolve={$row['DisputeID']}' class='btn btn-secondary'>Resolve</a>";
                            } else {
                                echo "<a href='disputes.php?reopen={$row['DisputeID']}' class='btn btn-secondary'>Reopen</a>";
                            }
                        }
                        if ($user['Role'] === 'admin') {
                            echo "<a href='disputes.php?delete={$row['DisputeID']}' class='btn btn-danger' onclick='return confirm(\'Are you sure you want to delete this dispute?\')'>Delete</a>";
                        }
                        echo "</div></td>";
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
