<?php 
include 'db.php'; if($_SESSION['role'] != 'Staff') { header("Location: login.php"); exit(); }
$uid = $_SESSION['user_id']; $uname = $_SESSION['username']; $msg = "";

// 1. Fetch Current Leave Balances
$bal_query = $conn->query("SELECT bal_paid, bal_unpaid, bal_medical FROM users WHERE id=$uid");
$bal = $bal_query->fetch_assoc();

// 2. Apply Logic
if(isset($_POST['apply'])){
    $type = $_POST['type']; $start = $_POST['start']; $end = $_POST['end']; $reason = $_POST['reason'];
    $days = (new DateTime($start))->diff(new DateTime($end))->days + 1;
    if($start > $end) $msg = "<div class='alert alert-danger'>Error: Invalid date range.</div>";
    else {
        // Basic check to see if they have enough balance (Optional but good practice)
        $can_apply = true;
        if($type == 'Paid Leave' && $bal['bal_paid'] < $days) $can_apply = false;
        if($type == 'Medical Leave' && $bal['bal_medical'] < $days) $can_apply = false;
        
        if($can_apply){
             $conn->query("INSERT INTO leaves (user_id, username, leave_type, start_date, end_date, days, reason) VALUES ('$uid', '$uname', '$type', '$start', '$end', '$days', '$reason')");
             $msg = "<div class='alert alert-success'>Application Submitted Successfully! Status is Pending.</div>";
        } else {
             $msg = "<div class='alert alert-danger'>Error: Insufficient leave balance for this request.</div>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4a90e2; --secondary: #50e3c2; --danger: #e74c3c; --dark: #2c3e50; --light: #f4f7f6; --white: #ffffff; --warning: #f39c12; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--light); margin: 0; padding: 0; }
        .navbar { background-color: var(--white); padding: 15px 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.5rem; font-weight: bold; color: var(--dark); }
        .user-profile { display: flex; align-items: center; gap: 15px; }
        .user-info { text-align: right; } .user-name { font-weight: bold; color: var(--dark); display: block; } .user-role { font-size: 0.85rem; color: #777; }
        .avatar { width: 40px; height: 40px; background-color: var(--primary); color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; }
        .btn-logout { color: var(--danger); text-decoration: none; font-size: 0.9rem; border: 1px solid var(--danger); padding: 5px 10px; border-radius: 4px; transition: 0.3s; }
        .btn-logout:hover { background: var(--danger); color: white; }
        .container { max-width: 1200px; margin: 40px auto; padding: 0 20px; }
        .page-title { margin-bottom: 30px; color: var(--dark); }
        
        /* Balance Cards Styling */
        .balance-container { display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap; }
        .balance-card { flex: 1; min-width: 200px; background: var(--white); padding: 20px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: flex; flex-direction: column; }
        .bal-title { font-size: 0.9rem; color: #777; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 10px; display: flex; justify-content: space-between; align-items: center;}
        .bal-count { font-size: 2.2rem; font-weight: bold; color: var(--dark); margin-bottom: 5px; }
        .bal-quota { font-size: 0.85rem; color: #aaa; }
        .bc-paid { border-left: 5px solid var(--primary); }
        .bc-unpaid { border-left: 5px solid var(--warning); }
        .bc-med { border-left: 5px solid var(--secondary); }

        .content-card { margin-top: 30px; background: var(--white); padding: 30px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        input, select, textarea { width: 100%; padding: 12px; margin: 8px 0 20px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background: var(--light); font-family: inherit; }
        .btn-apply { background-color: var(--primary); color: white; padding: 12px 30px; border: none; border-radius: 30px; font-weight: bold; cursor: pointer; width: 100%; transition: 0.3s; } .btn-apply:hover { background-color: #357abd; }
        .styled-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .styled-table th, .styled-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .styled-table th { background-color: var(--dark); color: white; }
        .status-badge { padding: 5px 12px; border-radius: 20px; font-size: 0.85rem; font-weight: bold; }
        .status-Pending { background: #fcf3cf; color: #f39c12; } .status-Approved { background: #d4efdf; color: #27ae60; } .status-Rejected { background: #fadbd8; color: #c0392b; }
        .alert { padding: 15px; margin-bottom: 20px; border-radius: 6px; } .alert-success { background: #d4efdf; color: #27ae60; } .alert-danger { background: #fadbd8; color: #c0392b; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">LeaveSystem</div>
        <div class="user-profile">
            <div class="user-info"><span class="user-name"><?php echo htmlspecialchars($uname); ?></span><span class="user-role">Staff Member</span></div>
            <div class="avatar"><?php echo strtoupper(substr($uname, 0, 1)); ?></div>
            <a href="logout.php" class="btn-logout">Logout</a>
        </div>
    </nav>

    <div class="container">
        <h2 class="page-title">Staff Dashboard</h2>

        <div class="balance-container">
            <div class="balance-card bc-paid">
                <div class="bal-title">Paid Leave <i class="fas fa-briefcase" style="color:var(--primary); opacity:0.5;"></i></div>
                <div class="bal-count"><?php echo $bal['bal_paid']; ?></div>
                <div class="bal-quota">Remaining (10)</div>
            </div>
            <div class="balance-card bc-unpaid">
                <div class="bal-title">Unpaid Leave(CL) <i class="fas fa-user-slash" style="color:var(--warning); opacity:0.5;"></i></div>
                <div class="bal-count"><?php echo $bal['bal_unpaid']; ?></div>
                <div class="bal-quota">Remaining (20)</div>
            </div>
            <div class="balance-card bc-med">
                <div class="bal-title">Medical Leave <i class="fas fa-notes-medical" style="color:var(--secondary); opacity:0.5;"></i></div>
                <div class="bal-count"><?php echo $bal['bal_medical']; ?></div>
                <div class="bal-quota">Remaining (15)</div>
            </div>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-paper-plane"></i> Apply for Leave</h3>
            <?php echo $msg; ?>
            <form method="POST">
                <label>Leave Type</label>
                <select name="type"><option>Paid Leave</option><option>Unpaid Leave(CL)</option><option>Medical Leave</option></select>
                <div style="display:flex; gap:20px; flex-wrap: wrap;">
                    <div style="flex:1; min-width: 200px;"><label>Start Date</label><input type="date" name="start" required></div>
                    <div style="flex:1; min-width: 200px;"><label>End Date</label><input type="date" name="end" required></div>
                </div>
                <label>Reason</label>
                <textarea name="reason" rows="3" required placeholder="Enter a brief reason..."></textarea>
                <button type="submit" name="apply" class="btn-apply">Submit Application</button>
            </form>
        </div>

        <div class="content-card">
            <h3><i class="fas fa-history"></i> My History</h3>
            <div style="overflow-x: auto;">
            <table class="styled-table">
                <tr><th>Type</th><th>Dates</th><th>Days</th><th>Status</th></tr>
                <?php
                $res = $conn->query("SELECT * FROM leaves WHERE user_id=$uid ORDER BY id DESC");
                while($row = $res->fetch_assoc()){
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
    </div>
</body>
</html>