<?php
session_start();
include("db.php");

// Check if vote form was submitted
if(!isset($_POST['vote'])){
    header("Location: dashboard.php");
    exit();
}

// Check if user is logged in
if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$candidate_id = (int)$_POST['candidate_id'];
$election_id = (int)$_POST['election_id'];

// 1. Check if user already voted
$user_check = mysqli_query($conn, "SELECT status FROM users WHERE id=$user_id");
$user = mysqli_fetch_assoc($user_check);
if($user['status'] == 1){
    echo "<script>alert('You have already voted!'); window.location='dashboard.php';</script>";
    exit();
}

// 2. Check if election is active
$election_query = mysqli_query($conn, "SELECT status, start_date, end_date FROM elections WHERE id=$election_id");
$election = mysqli_fetch_assoc($election_query);
$now = date('Y-m-d H:i:s');
if(!$election || $election['status'] != 'active' || $now < $election['start_date'] || $now > $election['end_date']){
    echo "<script>alert('Voting is not allowed for this election.'); window.location='dashboard.php';</script>";
    exit();
}

// 3. Get candidate details
$candidate_query = mysqli_query($conn, "SELECT name FROM candidates WHERE id=$candidate_id AND election_id=$election_id");
if(mysqli_num_rows($candidate_query) == 0){
    echo "<script>alert('Invalid candidate selected.'); window.location='dashboard.php';</script>";
    exit();
}
$candidate = mysqli_fetch_assoc($candidate_query);
$candidate_name = mysqli_real_escape_string($conn, $candidate['name']); // Escape for safety

// 4. Process the vote (without transaction - works on MyISAM & InnoDB)
$update_votes = mysqli_query($conn, "UPDATE candidates SET votes = votes + 1 WHERE id=$candidate_id");
$update_user = mysqli_query($conn, "UPDATE users SET status=1, voted_for='$candidate_name', voted_for_election=$election_id WHERE id=$user_id");

if($update_votes && $update_user){
    // Update session data
    $_SESSION['user_data']['status'] = 1;
    $_SESSION['user_data']['voted_for'] = $candidate_name;
    
    echo "<script>alert('Vote cast successfully!'); window.location='dashboard.php';</script>";
} else {
    echo "<script>alert('Error casting vote. Please try again. MySQL Error: " . mysqli_error($conn) . "'); window.location='dashboard.php';</script>";
}
?>