<?php
include 'db.php'; if($_SESSION['role'] != 'HOD') { header("Location: login.php"); exit(); }
$uid = $_SESSION['user_id']; $uname = $_SESSION['username']; $msg = "";

$bal_query = $conn->query("SELECT bal_paid, bal_unpaid, bal_medical FROM users WHERE id=$uid");
$bal = $bal_query->fetch_assoc();

$today_date = date('Y-m-d');
$todays_leaves_sql = "SELECT * FROM leaves 
                      WHERE status = 'Approved' 
                      AND '$today_date' BETWEEN start_date AND end_date 
                      ORDER BY username ASC";
$todays_leaves_result = $conn->query($todays_leaves_sql);

if(isset($_POST['apply'])){
    $type = $_POST['type']; $start = $_POST['start']; $end = $_POST['end']; $reason = $_POST['reason'];
    $start_dt = new DateTime($start);
    $end_dt = new DateTime($end);
    $days = $end_dt->diff($start_dt)->format("%a") + 1;

    if($start_dt > $end_dt) $msg = "<div class='alert alert-danger'>Error: Invalid date range.</div>";
    else {
        $can_apply = true;
        if($type == 'Paid Leave' && $bal['bal_paid'] < $days) $can_apply = false;

        if($can_apply){
            $stmt = $conn->prepare("INSERT INTO leaves (user_id, username, leave_type, start_date, end_date, days, reason) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssis", $uid, $uname, $type, $start, $end, $days, $reason);
            
            if($stmt->execute()){
                 $msg = "<div class='alert alert-success'>Application Submitted Successfully!</div>";
                 $bal_query = $conn->query("SELECT bal_paid, bal_unpaid, bal_medical FROM users WHERE id=$uid");
                 $bal = $bal_query->fetch_assoc();
            } else {
                 $msg = "<div class='alert alert-danger'>Database Error: " . $stmt->error . "</div>";
            }
            $stmt->close();
        } else {
             $msg = "<div class='alert alert-danger'>Error: Insufficient leave balance for this request.</div>";
        }
    }
}

if(isset($_POST['update'])){
    $lid = $_POST['lid']; $target_uid = $_POST['uid']; $status = $_POST['status']; 
    $days = $_POST['days']; $type = $_POST['type'];

    if($target_uid != $uid) {
        $update_stmt = $conn->prepare("UPDATE leaves SET status=? WHERE id=?");
        $update_stmt->bind_param("si", $status, $lid);
        $update_stmt->execute();
        $update_stmt->close();

        if($status == 'Approved'){
            $col = '';
            if($type == 'Paid Leave') $col = 'bal_paid';
            elseif($type == 'Unpaid Leave') $col = 'bal_unpaid';
            elseif($type == 'Medical Leave') $col = 'bal_medical';
            
            if($col != '') {
                $conn->query("UPDATE users SET $col = $col - $days WHERE id=$target_uid");
            }
        }
        $todays_leaves_result = $conn->query($todays_leaves_sql);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HOD Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4a90e2; --secondary: #50e3c2; --danger: #e74c3c; --dark: #2c3e50; --light: #f4f7f6; --white: #ffffff; --success: #27ae60; --warning: #f39c12; --info: #3498db; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--light); margin: 0; padding: 0; }
        .navbar { background-color: var(--white); padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--dark); }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; } .user-name { font-weight: bold; color: var(--dark); display: block; } .user-role { font-size: 0.85rem; color: #777; }
        .avatar { width: 40px; height: 40px; background-color: var(--secondary); color: var(--dark); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .btn-logout { color: var(--danger); text-decoration: none; font-size: 0.9rem; border: 1px solid var(--danger); padding: 5px 10px; border-radius: 4px; transition: 0.3s; }
        .btn-logout:hover { background: var(--danger); color: white; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px 40px 20px; }
        .page-title { margin-bottom: 30px; color: var(--dark); }
        .balance-container { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .balance-card { flex: 1; min-width: 200px; background: var(--white); padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .bal-title { font-size: 0.9rem; color: #777; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;}
        .bal-count { font-size: 2.2rem; font-weight: bold; color: var(--dark); margin-bottom: 5px; }
        .bal-quota { font-size: 0.85rem; color: #aaa; }
        .bc-paid { border-left: 5px solid var(--primary); }
        .bc-unpaid { border-left: 5px solid var(--warning); }
        .bc-med { border-left: 5px solid var(--secondary); }
        .todays-absent-card { background: #e8f4fd; border-left: 5px solid var(--info); padding: 20px; border-radius: 12px; margin-bottom: 30px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .todays-absent-card h3 { color: #2980b9; margin-top: 0; margin-bottom: 15px; display: flex; align-items: center; gap: 10px; font-size: 1.1rem; }
        .absent-list { list-style: none; padding: 0; margin: 0; }
        .absent-list li { background: var(--white); padding: 10px 15px; margin-bottom: 8px; border-radius: 8px; display: flex; justify-content: space-between; align-items: center; border: 1px solid #d6eaf8; }
        .absent-name { font-weight: bold; color: var(--dark); display: flex; align-items: center; }
        .absent-details { font-size: 0.85rem; color: #555; background: #f4f6f7; padding: 4px 10px; border-radius: 12px; }
        .content-card { margin-top: 30px; background: var(--white); padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        input, select, textarea { width: 100%; padding: 12px; margin: 8px 0 20px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background: var(--light); font-family: inherit; }
        .btn-apply { background-color: var(--primary); color: white; padding: 12px 30px; border: none; border-radius: 30px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; } .btn-apply:hover { background-color: #357abd; }
        .styled-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .styled-table th, .styled-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; vertical-align: middle;}
        .styled-table th { background-color: var(--dark); color: white; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .status-Pending { background: #fcf3cf; color: #f39c12; } .status-Approved { background: #d4efdf; color: #27ae60; } .status-Rejected { background: #fadbd8; color: #c0392b; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; } .alert-success { background: #d4efdf; color: #27ae60; } .alert-danger { background: #fadbd8; color: #c0392b; }
        .btn-action { padding: 8px 15px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; transition: 0.2s; display: flex; align-items: center; gap: 5px; }
        .btn-approve { background-color: var(--success); color: white; } .btn-approve:hover { background-color: #219653; }
        .btn-reject { background-color: var(--danger); color: white; } .btn-reject:hover { background-color: #c0392b; }
        .action-form { display: flex; gap: 10px; }
        .section-divider { border-top: 2px dashed #ddd; margin: 40px 0; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">LeaveSystem</div>
        <div class="user-profile">
            <div class="user-info"><span class="user-name"><?php echo htmlspecialchars($uname); ?></span><span class="user-role">Head of Department</span></div>
            <div class="avatar"><?php echo strtoupper(substr($uname, 0, 1)); ?></div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2 class="page-title">HOD Dashboard & Approvals</h2>

        <div class="balance-container">
            <div class="balance-card bc-paid">
                <div class="bal-title">My Paid Leave <i class="fas fa-briefcase" style="color:var(--primary); opacity:0.5;"></i></div>
                <div class="bal-count"><?php echo $bal['bal_paid']; ?></div>
                <div class="bal-quota">Remaining (10)</div>
            </div>
            <div class="balance-card bc-unpaid">
                <div class="bal-title">My Unpaid Leave (CL)<i class="fas fa-user-slash" style="color:var(--warning); opacity:0.5;"></i></div>
                <div class="bal-count"><?php echo $bal['bal_unpaid']; ?></div>
                <div class="bal-quota">Remaining (20)</div>
            </div>
            <div class="balance-card bc-med">
                <div class="bal-title">My Medical Leave <i class="fas fa-notes-medical" style="color:var(--secondary); opacity:0.5;"></i></div>
                <div class="bal-count"><?php echo $bal['bal_medical']; ?></div>
                <div class="bal-quota">Remaining (15)</div>
            </div>
        </div>

        <div class="todays-absent-card">
            <h3><i class="fas fa-calendar-day"></i> On Leave Today (<?php echo date('d M Y'); ?>)</h3>
            <?php if ($todays_leaves_result->num_rows > 0): ?>
                <ul class="absent-list">
                    <?php while($t_row = $todays_leaves_result->fetch_assoc()): ?>
                        <li>
                            <span class="absent-name">
                                <i class="fas fa-user-circle" style="color: #aaa; margin-right: 8px;"></i>
                                <?php echo htmlspecialchars($t_row['username']); ?>
                            </span>
                            <span class="absent-details">
                                <?php echo $t_row['leave_type']; ?> (Ends: <?php echo date('d M', strtotime($t_row['end_date'])); ?>)
                            </span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p style="color: #555; margin: 0; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-check-circle" style="color: var(--success);"></i> All staff members are present today.
                </p>
            <?php endif; ?>
        </div>

        <div class="content-card" style="border-top: 5px solid var(--primary);">
            <h3><i class="fas fa-user-edit"></i> Apply for Personal Leave</h3>
            <?php echo $msg; ?>
            <form method="POST" style="display: flex; gap: 15px; flex-wrap:wrap; align-items: flex-end;">
                <div style="flex:1; min-width:150px;"><label>Type</label><select name="type"><option>Paid Leave</option><option>Unpaid Leave(CL)</option> <option>Medical Leave</option>></select></div>
                <div style="flex:1; min-width:150px;"><label>Start</label><input type="date" name="start" required></div>
                <div style="flex:1; min-width:150px;"><label>End</label><input type="date" name="end" required></div>
                <div style="flex:2; min-width:200px;"><label>Reason</label><input type="text" name="reason" required placeholder="Short reason..."></div>
                <div style="flex:1; min-width:150px;"><button type="submit" name="apply" class="btn-apply" style="margin:0;">Apply</button></div>
            </form>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-user-clock"></i> My Personal Application History</h3>
            <div style="overflow-x: auto;">
            <table class="styled-table">
                <tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr>
                <?php
                $my_history = $conn->query("SELECT * FROM leaves WHERE user_id=$uid ORDER BY id DESC");
                while($row = $my_history->fetch_assoc()){
                    echo "<tr>
                        <td>{$row['leave_type']}</td>
                        <td>{$row['start_date']} <small>to</small> {$row['end_date']}</td>
                        <td>{$row['days']}</td>
                        <td><span class='status-badge status-{$row['status']}'>{$row['status']}</span></td>
                    </tr>";
                }
                ?>
            </table>
            </div>
        </div>

        <div class="section-divider"></div>
        <h2 class="page-title"><i class="fas fa-users-cog"></i> Department Management</h2>

        <div class="content-card" style="border-top: 5px solid var(--danger);">
            <h3><i class="fas fa-tasks"></i> Staff Pending Requests</h3>
            <div style="overflow-x: auto;">
            <table class="styled-table">
                <tr><th>Staff Name</th><th>Type</th><th>Days</th><th>Reason</th><th>Action</th></tr>
                <?php
                $pending = $conn->query("SELECT * FROM leaves WHERE status='Pending' AND user_id != $uid ORDER BY applied_on DESC");
                if($pending->num_rows == 0) echo "<tr><td colspan='5' style='text-align:center; color:#777;'>No staff pending requests found.</td></tr>";
                while($row = $pending->fetch_assoc()){
                    echo "<tr>
                        <td><strong>{$row['username']}</strong></td>
                        <td>{$row['leave_type']}</td>
                        <td>{$row['days']}</td>
                        <td>{$row['reason']}</td>
                        <td>
                            <form method='POST' class='action-form'>
                                <input type='hidden' name='lid' value='{$row['id']}'>
                                <input type='hidden' name='uid' value='{$row['user_id']}'>
                                <input type='hidden' name='days' value='{$row['days']}'>
                                <input type='hidden' name='type' value='{$row['leave_type']}'>
                                <button type='submit' name='update' value='Approved' class='btn-action btn-approve' onclick=\"this.form.status.value='Approved'\"><i class='fas fa-check'></i> Approve</button>
                                <button type='submit' name='update' value='Rejected' class='btn-action btn-reject' onclick=\"this.form.status.value='Rejected'\"><i class='fas fa-times'></i> Reject</button>
                                <input type='hidden' name='status'>
                            </form>
                        </td>
                    </tr>";
                }
                ?>
            </table>
            </div>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-history"></i> Department History Log (All Staff)</h3>
             <div style="overflow-x: auto;">
            <table class="styled-table">
                <tr><th>Staff Name</th><th>Type</th><th>Days</th><th>Status</th></tr>
                <?php
                $history = $conn->query("SELECT * FROM leaves WHERE status!='Pending' AND user_id != $uid ORDER BY applied_on DESC LIMIT 20");
                while($row = $history->fetch_assoc()){
                    echo "<tr>
                        <td>{$row['username']}</td>
                        <td>{$row['leave_type']}</td>
                        <td>{$row['days']}</td>
                        <td><span class='status-badge status-{$row['status']}'>{$row['status']}</span></td>
                    </tr>";
                }
                ?>
            </table>
            </div>
        </div>
    </div>
</body>
</html>