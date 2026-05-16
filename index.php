<?php
session_start();
include("db.php");

if(isset($_POST['login'])){
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $password = $_POST['password'];

    $query = mysqli_query($conn, "SELECT * FROM users WHERE student_id='$student_id'");
    if(mysqli_num_rows($query) > 0){
        $user = mysqli_fetch_assoc($query);
        if(password_verify($password, $user['password'])){
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_data'] = $user;
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid Password!";
        }
    } else {
        $error = "User not found!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Voting System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>🔐 Welcome Back</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <form method="POST">
            <input type="text" name="student_id" placeholder="Student ID / National ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login">Login →</button>
        </form>
        <p style="text-align:center; margin-top:20px;">New voter? <a href="register.php">Create account</a> &nbsp;|&nbsp; <a href="admin.php">Admin</a></p>
    </div>
</body>
</html>