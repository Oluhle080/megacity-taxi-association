<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

// Count drivers
$drivers = $conn->query("SELECT COUNT(*) as total FROM drivers")->fetch_assoc()['total'];

// Count taxis
$taxis = $conn->query("SELECT COUNT(*) as total FROM taxis")->fetch_assoc()['total'];

// Count payments
$payments = $conn->query("SELECT COUNT(*) as total FROM payments")->fetch_assoc()['total'];

// Total revenue
$revenue = $conn->query("SELECT SUM(Amount) as total FROM payments")->fetch_assoc()['total'];

// Count disputes
$disputes = $conn->query("SELECT COUNT(*) as total FROM disputes WHERE Status='open'")->fetch_assoc()['total'];

// Count routes
$routes = $conn->query("SELECT COUNT(*) as total FROM routes")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Dashboard - Megacity Taxi</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { background:#0a0a0f; color:#e8e8f0; font-family:Arial,sans-serif; display:flex; min-height:100vh; }

/* SIDEBAR */
.sidebar { width:220px; background:#13131a; border-right:1px solid #2a2a3a; display:flex; flex-direction:column; position:fixed; top:0; bottom:0; }
.logo { padding:24px 20px; border-bottom:1px solid #2a2a3a; }
.logo h1 { color:#f5c518; font-size:18px; }
.logo p { color:#6b6b80; font-size:11px; margin-top:4px; }
nav { padding:16px 10px; flex:1; }
nav a { display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:8px; color:#6b6b80; text-decoration:none; font-size:14px; margin-bottom:4px; }
nav a:hover { background:#1a1a24; color:#fff; }
nav a.active { background:#f5c51820; color:#f5c518; }
.sidebar-footer { padding:16px; border-top:1px solid #2a2a3a; font-size:12px; color:#6b6b80; }

/* MAIN */
.main { margin-left:220px; flex:1; }
.topbar { background:#13131a; border-bottom:1px solid #2a2a3a; padding:16px 28px; display:flex; justify-content:space-between; align-items:center; }
.topbar h2 { font-size:18px; }
.user-badge { background:#f5c51820; color:#f5c518; padding:6px 14px; border-radius:20px; font-size:13px; }
.logout-button { margin-left:12px; background:#f5c518; color:#0a0a0f; padding:8px 16px; border-radius:10px; text-decoration:none; font-weight:bold; transition: background .2s ease; }
.logout-button:hover { background:#e0b010; }
.content { padding:28px; }

/* STATS */
.stats { display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-bottom:28px; }
.stat { background:#1a1a24; border:1px solid #2a2a3a; border-radius:10px; padding:20px; border-top:3px solid #f5c518; }
.stat-label { font-size:11px; text-transform:uppercase; letter-spacing:1px; color:#6b6b80; margin-bottom:8px; }
.stat-value { font-size:32px; font-weight:bold; color:#f5c518; }
.stat-sub { font-size:12px; color:#6b6b80; margin-top:4px; }

/* TABLES */
.tables { display:grid; grid-template-columns:1fr 1fr; gap:20px; }
.card { background:#1a1a24; border:1px solid #2a2a3a; border-radius:10px; overflow:hidden; }
.card-header { padding:14px 18px; border-bottom:1px solid #2a2a3a; font-size:14px; font-weight:bold; }
table { width:100%; border-collapse:collapse; }
th { background:#13131a; padding:10px 16px; text-align:left; font-size:11px; text-transform:uppercase; color:#6b6b80; }
td { padding:12px 16px; border-bottom:1px solid #2a2a3a; font-size:13px; }
tr:last-child td { border-bottom:none; }
tr:hover td { background:#13131a; }
.badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:bold; }
.badge-open { background:#f8514920; color:#f85149; }
.badge-resolved { background:#3fb95020; color:#3fb950; }
.badge-fee { background:#58a6ff20; color:#58a6ff; }
.badge-fine { background:#f0883e20; color:#f0883e; }
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
    <div class="logo">
        <h1>🚕 Megacity</h1>
        <p>Taxi Management System</p>
    </div>
    <nav>
        <a href="dashboard.php" class="active">📊 Dashboard</a>
        <a href="drivers.php">👤 Drivers</a>
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

<!-- MAIN -->
<div class="main">
    <div class="topbar">
        <h2>Dashboard</h2>
        <div>
            <span class="user-badge">👋 Welcome, <?php echo $user['FullName']; ?></span>
            <a class="logout-button" href="logout.php">Logout</a>
        </div>
    </div>
    <div class="content">

        <!-- STATS -->
        <div class="stats">
            <div class="stat">
                <div class="stat-label">Total Drivers</div>
                <div class="stat-value"><?php echo $drivers; ?></div>
                <div class="stat-sub">Registered drivers</div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Taxis</div>
                <div class="stat-value"><?php echo $taxis; ?></div>
                <div class="stat-sub">Registered vehicles</div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">R<?php echo number_format($revenue, 2); ?></div>
                <div class="stat-sub">All time payments</div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Payments</div>
                <div class="stat-value"><?php echo $payments; ?></div>
                <div class="stat-sub">Transactions recorded</div>
            </div>
            <div class="stat">
                <div class="stat-label">Open Disputes</div>
                <div class="stat-value"><?php echo $disputes; ?></div>
                <div class="stat-sub">Needs attention</div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Routes</div>
                <div class="stat-value"><?php echo $routes; ?></div>
                <div class="stat-sub">Active routes</div>
            </div>
        </div>

        <!-- RECENT DATA -->
        <div class="tables">
            <div class="card">
                <div class="card-header">👤 Recent Drivers</div>
                <table>
                    <thead><tr><th>Name</th><th>Phone</th><th>License</th></tr></thead>
                    <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM drivers ORDER BY DriverID DESC LIMIT 5");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr><td>{$row['Name']}</td><td>{$row['Phone']}</td><td>{$row['LicenseNumber']}</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">💰 Recent Payments</div>
                <table>
                    <thead><tr><th>Driver</th><th>Amount</th><th>Type</th></tr></thead>
                    <tbody>
                    <?php
                    $result = $conn->query("SELECT p.*, d.Name FROM payments p JOIN drivers d ON p.DriverID = d.DriverID ORDER BY p.PaymentID DESC LIMIT 5");
                    while ($row = $result->fetch_assoc()) {
                        $badge = $row['PaymentType'] == 'fee' ? 'badge-fee' : 'badge-fine';
                        echo "<tr><td>{$row['Name']}</td><td>R{$row['Amount']}</td><td><span class='badge {$badge}'>{$row['PaymentType']}</span></td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">⚠️ Recent Disputes</div>
                <table>
                    <thead><tr><th>Driver</th><th>Description</th><th>Status</th></tr></thead>
                    <tbody>
                    <?php
                    $result = $conn->query("SELECT dp.*, d.Name FROM disputes dp JOIN drivers d ON dp.DriverID = d.DriverID ORDER BY dp.DisputeID DESC LIMIT 5");
                    while ($row = $result->fetch_assoc()) {
                        $badge = $row['Status'] == 'open' ? 'badge-open' : 'badge-resolved';
                        echo "<tr><td>{$row['Name']}</td><td>{$row['Description']}</td><td><span class='badge {$badge}'>{$row['Status']}</span></td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>

            <div class="card">
                <div class="card-header">🗺️ Routes</div>
                <table>
                    <thead><tr><th>Route</th><th>From</th><th>To</th></tr></thead>
                    <tbody>
                    <?php
                    $result = $conn->query("SELECT * FROM routes LIMIT 5");
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr><td>{$row['RouteName']}</td><td>{$row['StartPoint']}</td><td>{$row['EndPoint']}</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

</body>
</html>