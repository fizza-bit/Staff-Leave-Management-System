<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register | Leave System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary: #4a90e2; --secondary: #50e3c2; --dark: #2c3e50; --light: #f4f7f6; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: var(--light); display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .register-card { background: white; padding: 40px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .register-card h2 { color: var(--dark); margin-bottom: 30px; }
        input, select { width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; background: var(--light); }
        .btn-register { background-color: var(--secondary); color: var(--dark); padding: 12px; border: none; border-radius: 6px; width: 100%; font-weight: bold; cursor: pointer; transition: background 0.3s; }
        .btn-register:hover { background-color: #3ce0b8; }
        .links { margin-top: 20px; font-size: 0.9rem; }
        .links a { color: var(--primary); text-decoration: none; }
        .alert { padding: 10px; margin-bottom: 15px; border-radius: 6px; }
        .alert-success { background-color: #e6ffe6; color: #27ae60; border: 1px solid #ccffcc; }
        .alert-danger { background-color: #ffe6e6; color: #c0392b; border: 1px solid #ffcccc; }
    </style>
</head>
<body>
    <div class="register-card">
        <h2><i class="fas fa-user-plus"></i> Create Account</h2>
        <?php
        if (isset($_POST['reg'])) {
            $u = $_POST['user']; $e = $_POST['email']; $r = $_POST['role'];
            if($r !== 'Staff' && $r !== 'HOD') {
                 echo "<div class='alert alert-danger'>Invalid role selected.</div>";
            } else {
                $p = password_hash($_POST['pass'], PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, email, password, role) VALUES ('$u', '$e', '$p', '$r')";
                if($conn->query($sql)) echo "<div class='alert alert-success'>Registration Successful! <a href='login.php'>Login here</a>.</div>";
                else echo "<div class='alert alert-danger'>Error: Email likely already exists.</div>";
            }
        }
        ?>
        <form method="POST">
            <input type="text" name="user" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="pass" placeholder="Password" required>
            <select name="role">
                <option value="Staff">Staff</option>
                <option value="HOD">HOD</option>
            </select>
            <button type="submit" name="reg" class="btn-register">Register</button>
        </form>
        <div class="links">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>
</body>
</html>