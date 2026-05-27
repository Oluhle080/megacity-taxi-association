<?php
session_start();
include 'db.php';

// If not logged in send back to login
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

$user = $_SESSION['user'];

// ADDING A NEW PAYMENT
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_payment'])) {
    $driver_id = intval($_POST['driver_id']);
    $amount = $_POST['amount'];
    $type = trim($_POST['type']);
    $date = trim($_POST['date']);

    // Check amount is a valid number greater than zero
    if (!is_numeric($amount) || $amount <= 0) {
        $error = "Amount must be a valid number greater than zero!";
    }
    // Check date is not empty
    elseif (empty($date)) {
        $error = "Please select a date!";
    }
    elseif (!in_array($type, ['fee', 'fine'], true)) {
        $error = "Please select a valid payment type!";
    }
    // Everything fine — save to database
    else {
        $insertStmt = $conn->prepare("INSERT INTO payments (DriverID, Amount, PaymentType, Date) VALUES (?, ?, ?, ?)");
        $insertStmt->bind_param('idss', $driver_id, $amount, $type, $date);
        $insertStmt->execute();
        $insertStmt->close();

        $success = "Payment recorded successfully!";
    }
}

// DELETING A PAYMENT
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $deleteStmt = $conn->prepare("DELETE FROM payments WHERE PaymentID = ?");
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();
    $deleteStmt->close();
    header('Location: payments.php');
    exit();
}

// GET ALL PAYMENTS — join with drivers to show driver name
$result = $conn->query("SELECT p.*, d.Name as DriverName FROM payments p JOIN drivers d ON p.DriverID = d.DriverID ORDER BY p.PaymentID DESC");

// GET ALL DRIVERS for the dropdown
$drivers = $conn->query("SELECT * FROM drivers ORDER BY Name");

// GET TOTAL REVENUE
$total_revenue = $conn->query("SELECT SUM(Amount) as total FROM payments")->fetch_assoc()['total'];
$total_fees = $conn->query("SELECT SUM(Amount) as total FROM payments WHERE PaymentType='fee'")->fetch_assoc()['total'];
$total_fines = $conn->query("SELECT SUM(Amount) as total FROM payments WHERE PaymentType='fine'")->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payments - Megacity Taxi</title>
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
.form-grid { display:grid; grid-template-columns:1fr 1fr 1fr 1fr; gap:16px; margin-bottom:16px; }
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
.badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:bold; text-transform:uppercase; }
.badge-fee { background:#58a6ff20; color:#58a6ff; }
.badge-fine { background:#f0883e20; color:#f0883e; }
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
        <a href="payments.php" class="active">💰 Payments</a>
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
        <h2>💰 Payments</h2>
        <span style="color:#6b6b80; font-size:13px;"><?php echo date('l, d F Y'); ?></span>
    </div>
    <div class="content">

        <!-- PAYMENT SUMMARY STATS -->
        <div class="stats">
            <div class="stat">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">R<?php echo number_format($total_revenue, 2); ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Fees Collected</div>
                <div class="stat-value">R<?php echo number_format($total_fees, 2); ?></div>
            </div>
            <div class="stat">
                <div class="stat-label">Total Fines Collected</div>
                <div class="stat-value">R<?php echo number_format($total_fines, 2); ?></div>
            </div>
        </div>

        <?php if ($user['Role'] == 'admin' || $user['Role'] == 'clerk') { ?>
        <div class="form-card">
            <div class="form-title">➕ Record New Payment</div>

            <?php if (isset($error)) { ?>
                <div class="alert alert-error">❌ <?php echo $error; ?></div>
            <?php } ?>
            <?php if (isset($success)) { ?>
                <div class="alert alert-success">✅ <?php echo $success; ?></div>
            <?php } ?>

            <form method="POST">
                <div class="form-grid">
                    <div>
                        <label>Select Driver</label>
                        <select name="driver_id" required>
                            <option value="">-- Select Driver --</option>
                            <?php while ($d = $drivers->fetch_assoc()) { ?>
                                <option value="<?php echo $d['DriverID']; ?>"><?php echo $d['Name']; ?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div>
                        <label>Amount (R)</label>
                        <input type="number" name="amount" placeholder="e.g. 150.00" step="0.01" min="1" required>
                    </div>
                    <div>
                        <label>Payment Type</label>
                        <select name="type" required>
                            <option value="fee">Fee</option>
                            <option value="fine">Fine</option>
                        </select>
                    </div>
                    <div>
                        <label>Date</label>
                        <input type="date" name="date" required>
                    </div>
                </div>
                <button type="submit" name="add_payment" class="btn btn-primary">Save Payment</button>
            </form>
        </div>
        <?php } ?>

        <div class="card">
            <div class="card-header">
                <span>All Payments</span>
                <span class="badge-count"><?php echo $result->num_rows; ?> total</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Driver</th>
                        <th>Amount</th>
                        <th>Type</th>
                        <th>Date</th>
                        <?php if ($user['Role'] == 'admin') { ?>
                        <th>Action</th>
                        <?php } ?>
                    </tr>
                </thead>
                <tbody>
                <?php
                $i = 1;
                while ($row = $result->fetch_assoc()) {
                    $badge = $row['PaymentType'] == 'fee' ? 'badge-fee' : 'badge-fine';
                    echo "<tr>
                        <td>{$i}</td>
                        <td><strong>{$row['DriverName']}</strong></td>
                        <td>R" . number_format($row['Amount'], 2) . "</td>
                        <td><span class='badge {$badge}'>{$row['PaymentType']}</span></td>
                        <td>{$row['Date']}</td>";
                    if ($user['Role'] == 'admin') {
                        echo "<td><a href='payments.php?delete={$row['PaymentID']}' class='btn btn-danger' onclick='return confirm(\"Delete this payment?\")'>Delete</a></td>";
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