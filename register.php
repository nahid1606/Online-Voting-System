<?php
include("db.php");
session_start();

if(isset($_POST['register'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $check = mysqli_query($conn, "SELECT * FROM users WHERE student_id='$student_id' OR email='$email'");
    if(mysqli_num_rows($check) > 0){
        $error = "Student ID or Email already registered!";
    } else {
        $query = "INSERT INTO users (name, email, phone, student_id, password) VALUES ('$name', '$email', '$phone', '$student_id', '$hashed_password')";
        if(mysqli_query($conn, $query)){
            $success = "Registration successful! Please login.";
        } else {
            $error = "Registration failed. Try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - Voting System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>📝 Create Account</h2>
        <?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
        <?php if(isset($success)) echo "<div class='success'>$success</div>"; ?>
        <form method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            <input type="text" name="student_id" placeholder="Student ID / National ID" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="register">Register →</button>
        </form>
        <p style="text-align:center; margin-top:20px;">Already have an account? <a href="index.php">Login</a></p>
    </div>
</body>
</html>