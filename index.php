<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Leave System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; display: flex; align-items: center; min-height: 100vh; }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center">
                        <h4> Staff Leave Management System</h4>
                    </div>
                    <div class="card-body p-4">
                        <?php
                        if (isset($_POST['login'])) {
                            $email = $_POST['email'];
                            $password = $_POST['password'];
                            $res = $conn->query("SELECT * FROM users WHERE email='$email'");

                            if ($res->num_rows > 0) {
                                $row = $res->fetch_assoc();
                                // Using plain text '1234' for demo purposes as requested previously
                                if (password_verify($password, $row['password']) || $password == '1234') { 
                                    $_SESSION['user_id'] = $row['id'];
                                    $_SESSION['role'] = $row['role'];
                                    $_SESSION['username'] = $row['username'];
                                    
                                    if($row['role'] == 'Admin') header("Location: dashboard_admin.php");
                                    elseif($row['role'] == 'HOD') header("Location: dashboard_hod.php");
                                    else header("Location: dashboard_staff.php");
                                } else echo "<div class='alert alert-danger'>Wrong Password</div>";
                            } else echo "<div class='alert alert-danger'>User Not Found</div>";
                        }
                        ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" name="login" class="btn btn-primary btn-lg">Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center text-muted">
                        Don't have an account? <a href="register.php">Register here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>