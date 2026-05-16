<?php
session_start();
include("db.php");

$admin_user = "admin";
$admin_pass = "admin123";

if(isset($_POST['admin_login'])){
    if($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass){
        $_SESSION['admin_logged_in'] = true;
    } else {
        $login_error = "Invalid credentials!";
    }
}
if(isset($_POST['admin_logout'])){
    unset($_SESSION['admin_logged_in']);
}

// Helper function to upload photo
function uploadPhoto($file, $existing = null){
    if($file['error'] == UPLOAD_ERR_NO_FILE) return $existing;
    $target_dir = "uploads/";
    if(!is_dir($target_dir)) mkdir($target_dir, 0777, true);
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . "_" . uniqid() . "." . $ext;
    $target_file = $target_dir . $filename;
    if(move_uploaded_file($file["tmp_name"], $target_file)){
        return $filename;
    }
    return $existing;
}

// ---- Election Management ----
if(isset($_POST['save_election'])){
    $id = (int)$_POST['election_id'];
    $title = mysqli_real_escape_string($conn, $_POST['title']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $desc = mysqli_real_escape_string($conn, $_POST['description']);
    $start = mysqli_real_escape_string($conn, $_POST['start_date']);
    $end = mysqli_real_escape_string($conn, $_POST['end_date']);
    $rules = mysqli_real_escape_string($conn, $_POST['voting_rules']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    if($id == 0){
        mysqli_query($conn, "INSERT INTO elections (title, type, description, start_date, end_date, voting_rules, status) VALUES ('$title', '$type', '$desc', '$start', '$end', '$rules', '$status')");
    } else {
        mysqli_query($conn, "UPDATE elections SET title='$title', type='$type', description='$desc', start_date='$start', end_date='$end', voting_rules='$rules', status='$status' WHERE id=$id");
    }
}
if(isset($_POST['delete_election'])){
    $id = (int)$_POST['delete_election'];
    mysqli_query($conn, "DELETE FROM elections WHERE id=$id");
}
if(isset($_POST['change_election_status'])){
    $id = (int)$_POST['election_id'];
    $new = mysqli_real_escape_string($conn, $_POST['new_status']);
    mysqli_query($conn, "UPDATE elections SET status='$new' WHERE id=$id");
}

// ---- Candidate Management with extra fields ----
if(isset($_POST['add_candidate'])){
    $election_id = (int)$_POST['election_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $party = mysqli_real_escape_string($conn, $_POST['party']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $manifesto = mysqli_real_escape_string($conn, $_POST['manifesto']);
    $photo = uploadPhoto($_FILES['photo']);
    mysqli_query($conn, "INSERT INTO candidates (election_id, name, position, party, photo, bio, manifesto, votes) VALUES ($election_id, '$name', '$position', '$party', '$photo', '$bio', '$manifesto', 0)");
}
if(isset($_POST['update_candidate'])){
    $id = (int)$_POST['candidate_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $position = mysqli_real_escape_string($conn, $_POST['position']);
    $party = mysqli_real_escape_string($conn, $_POST['party']);
    $bio = mysqli_real_escape_string($conn, $_POST['bio']);
    $manifesto = mysqli_real_escape_string($conn, $_POST['manifesto']);
    $existing_photo = mysqli_fetch_assoc(mysqli_query($conn, "SELECT photo FROM candidates WHERE id=$id"))['photo'];
    $photo = uploadPhoto($_FILES['photo'], $existing_photo);
    mysqli_query($conn, "UPDATE candidates SET name='$name', position='$position', party='$party', photo='$photo', bio='$bio', manifesto='$manifesto' WHERE id=$id");
}
if(isset($_POST['delete_candidate'])){
    $id = (int)$_POST['delete_candidate'];
    mysqli_query($conn, "DELETE FROM candidates WHERE id=$id");
}
if(isset($_POST['reset_election_votes'])){
    $eid = (int)$_POST['election_id'];
    mysqli_query($conn, "UPDATE candidates SET votes=0 WHERE election_id=$eid");
    mysqli_query($conn, "UPDATE users SET status=0, voted_for=NULL, voted_for_election=NULL WHERE voted_for_election=$eid");
}

// ---- Voter Management ----
if(isset($_POST['add_voter'])){
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $check = mysqli_query($conn, "SELECT id FROM users WHERE student_id='$student_id'");
    if(mysqli_num_rows($check)==0){
        mysqli_query($conn, "INSERT INTO users (name, email, phone, student_id, password) VALUES ('$name', '$email', '$phone', '$student_id', '$password')");
        $voter_msg = "Voter added.";
    } else $voter_error = "Student ID exists.";
}
if(isset($_POST['update_voter'])){
    $id = (int)$_POST['voter_id'];
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $student_id = mysqli_real_escape_string($conn, $_POST['student_id']);
    mysqli_query($conn, "UPDATE users SET name='$name', email='$email', phone='$phone', student_id='$student_id' WHERE id=$id");
}
if(isset($_POST['delete_voter'])){
    $id = (int)$_POST['delete_voter'];
    mysqli_query($conn, "DELETE FROM users WHERE id=$id");
}
if(isset($_POST['reset_single_vote'])){
    $vid = (int)$_POST['voter_id'];
    $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT voted_for, voted_for_election FROM users WHERE id=$vid"));
    if($user['voted_for']){
        $candidate_name = $user['voted_for'];
        $eid = $user['voted_for_election'];
        mysqli_query($conn, "UPDATE candidates SET votes = votes - 1 WHERE name='$candidate_name' AND election_id=$eid");
    }
    mysqli_query($conn, "UPDATE users SET status=0, voted_for=NULL, voted_for_election=NULL WHERE id=$vid");
}

// ---- Results Publish ----
if(isset($_POST['toggle_results'])){
    $val = (int)$_POST['toggle_results'];
    mysqli_query($conn, "UPDATE settings SET setting_value='$val' WHERE setting_key='results_published'");
}

// ---- Data Fetching ----
$elections = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM elections ORDER BY start_date DESC"), MYSQLI_ASSOC);
$selected_election_id = isset($_GET['election_id']) ? (int)$_GET['election_id'] : ($elections[0]['id'] ?? 0);
$candidates = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM candidates WHERE election_id=$selected_election_id ORDER BY votes DESC"), MYSQLI_ASSOC);
$users = mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC"), MYSQLI_ASSOC);
$total_users = count($users);
$voted_count = mysqli_num_rows(mysqli_query($conn, "SELECT id FROM users WHERE status=1"));
$total_votes_election = array_sum(array_column($candidates, 'votes'));
$results_published = mysqli_fetch_assoc(mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key='results_published'"))['setting_value'] == '1';

// Edit data
$edit_candidate = null;
if(isset($_GET['edit_candidate_id'])){
    $cid = (int)$_GET['edit_candidate_id'];
    $edit_candidate = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM candidates WHERE id=$cid"));
}
$edit_voter = null;
if(isset($_GET['edit_voter_id'])){
    $vid = (int)$_GET['edit_voter_id'];
    $edit_voter = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id=$vid"));
}
$edit_election = null;
if(isset($_GET['edit_election_id'])){
    $eid = (int)$_GET['edit_election_id'];
    if($eid > 0) $edit_election = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM elections WHERE id=$eid"));
    else $edit_election = ['id'=>0, 'title'=>'', 'type'=>'student', 'description'=>'', 'start_date'=>date('Y-m-d\TH:i'), 'end_date'=>date('Y-m-d\TH:i', strtotime('+7 days')), 'voting_rules'=>'', 'status'=>'upcoming'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Panel - Voting System</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background: #1a1a2e; }
        .admin-wrap { width: 1200px; margin: 40px auto; }
        .admin-card { background: #fff; border-radius: 10px; padding: 20px; margin-bottom: 20px; }
        .admin-card h3 { margin-top: 0; border-bottom: 2px solid #28a745; padding-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        .btn-sm { padding: 4px 8px; font-size: 12px; border-radius: 4px; cursor: pointer; border: none; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-primary { background: #007bff; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn-warning { background: #ffc107; color: black; }
        .btn-info { background: #17a2b8; color: white; }
        .stat-grid { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .stat-box { background: #f8f9fa; padding: 15px; text-align: center; flex: 1; border-radius: 8px; }
        .stat-box .num { font-size: 24px; font-weight: bold; color: #28a745; }
        input, select, textarea { margin: 5px; padding: 6px; width: calc(100% - 12px); }
        .edit-form { background: #f0f0f0; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
        .candidate-photo-preview { width: 50px; height: 50px; object-fit: cover; border-radius: 50%; }
    </style>
</head>
<body>
<?php if(!isset($_SESSION['admin_logged_in'])): ?>
<div class="admin-wrap" style="width:400px;">
    <div class="admin-card">
        <h2>Admin Login</h2>
        <?php if(isset($login_error)) echo "<p style='color:red;'>$login_error</p>"; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required><br>
            <input type="password" name="password" placeholder="Password" required><br>
            <button type="submit" name="admin_login">Login</button>
        </form>
        <p><a href="index.php">← Back to Voter Login</a></p>
    </div>
</div>
<?php else: ?>
<div class="admin-wrap">
    <div class="admin-card">
        <div style="display:flex; justify-content:space-between;">
            <h2>⚙️ Admin Panel</h2>
            <form method="POST"><button name="admin_logout" class="btn-sm btn-danger">Logout</button></form>
        </div>
    </div>

    <!-- Vote Monitoring Stats -->
    <div class="admin-card">
        <h3>📊 Vote Monitoring</h3>
        <div class="stat-grid">
            <div class="stat-box"><div class="num"><?php echo $total_users; ?></div><div>Total Voters</div></div>
            <div class="stat-box"><div class="num"><?php echo $voted_count; ?></div><div>Votes Cast</div></div>
            <div class="stat-box"><div class="num"><?php echo $total_users - $voted_count; ?></div><div>Remaining Voters</div></div>
            <div class="stat-box"><div class="num"><?php echo $total_votes_election; ?></div><div>Votes in Current Election</div></div>
        </div>
    </div>

    <!-- Election Management -->
    <div class="admin-card">
        <h3>🗳️ Election Management</h3>
        <div style="margin-bottom:10px;">
            <form method="GET">
                <select name="election_id" onchange="this.form.submit()">
                    <?php foreach($elections as $e): ?>
                        <option value="<?php echo $e['id']; ?>" <?php echo $selected_election_id==$e['id']?'selected':''; ?>><?php echo $e['title']; ?> (<?php echo $e['status']; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <a href="admin.php?edit_election_id=0" class="btn-sm btn-primary">+ New Election</a>
            </form>
        </div>
        <?php if($edit_election): ?>
        <div class="edit-form">
            <h4><?php echo $edit_election['id'] ? 'Edit Election' : 'Create Election'; ?></h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="election_id" value="<?php echo $edit_election['id']; ?>">
                <input type="text" name="title" value="<?php echo $edit_election['title']; ?>" placeholder="Title" required><br>
                <select name="type">
                    <option value="student" <?php echo $edit_election['type']=='student'?'selected':''; ?>>Student Election</option>
                    <option value="club" <?php echo $edit_election['type']=='club'?'selected':''; ?>>Club Election</option>
                    <option value="national" <?php echo $edit_election['type']=='national'?'selected':''; ?>>National Election</option>
                </select><br>
                <textarea name="description" placeholder="Description" rows="2"><?php echo $edit_election['description']; ?></textarea><br>
                <input type="datetime-local" name="start_date" value="<?php echo date('Y-m-d\TH:i', strtotime($edit_election['start_date'])); ?>" required><br>
                <input type="datetime-local" name="end_date" value="<?php echo date('Y-m-d\TH:i', strtotime($edit_election['end_date'])); ?>" required><br>
                <textarea name="voting_rules" placeholder="Voting Rules" rows="2"><?php echo $edit_election['voting_rules']; ?></textarea><br>
                <select name="status">
                    <option value="upcoming" <?php echo $edit_election['status']=='upcoming'?'selected':''; ?>>Upcoming</option>
                    <option value="active" <?php echo $edit_election['status']=='active'?'selected':''; ?>>Active</option>
                    <option value="ended" <?php echo $edit_election['status']=='ended'?'selected':''; ?>>Ended</option>
                </select><br>
                <button type="submit" name="save_election" class="btn-sm btn-success">Save Election</button>
                <a href="admin.php" class="btn-sm btn-danger">Cancel</a>
            </form>
        </div>
        <?php endif; ?>
        <?php if($selected_election_id): ?>
        <div style="margin-top:10px;">
            <form method="POST" style="display:inline;">
                <input type="hidden" name="election_id" value="<?php echo $selected_election_id; ?>">
                <input type="hidden" name="new_status" value="active">
                <button name="change_election_status" class="btn-sm btn-success">▶ Start Election</button>
            </form>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="election_id" value="<?php echo $selected_election_id; ?>">
                <input type="hidden" name="new_status" value="ended">
                <button name="change_election_status" class="btn-sm btn-warning">⏹ End Election</button>
            </form>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Reset all votes for this election?');">
                <input type="hidden" name="election_id" value="<?php echo $selected_election_id; ?>">
                <button name="reset_election_votes" class="btn-sm btn-danger">🔄 Reset Votes</button>
            </form>
            <a href="admin.php?edit_election_id=<?php echo $selected_election_id; ?>" class="btn-sm btn-info">✏️ Edit</a>
            <form method="POST" style="display:inline;" onsubmit="return confirm('Delete entire election?');">
                <input type="hidden" name="delete_election" value="<?php echo $selected_election_id; ?>">
                <button class="btn-sm btn-danger">🗑 Delete</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Candidate Management with extra fields -->
    <div class="admin-card">
        <h3>🗳️ Manage Candidates (Election ID: <?php echo $selected_election_id; ?>)</h3>
        <?php if($edit_candidate): ?>
        <div class="edit-form">
            <h4>Edit Candidate</h4>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="candidate_id" value="<?php echo $edit_candidate['id']; ?>">
                <input type="text" name="name" value="<?php echo $edit_candidate['name']; ?>" placeholder="Name" required><br>
                <input type="text" name="position" value="<?php echo $edit_candidate['position']; ?>" placeholder="Position" required><br>
                <input type="text" name="party" value="<?php echo $edit_candidate['party']; ?>" placeholder="Party"><br>
                <textarea name="bio" placeholder="Biography" rows="3"><?php echo $edit_candidate['bio']; ?></textarea><br>
                <textarea name="manifesto" placeholder="Manifesto" rows="3"><?php echo $edit_candidate['manifesto']; ?></textarea><br>
                <input type="file" name="photo" accept="image/*"><br>
                <?php if($edit_candidate['photo']): ?>
                    <img src="uploads/<?php echo $edit_candidate['photo']; ?>" width="80"><br>
                <?php endif; ?>
                <button type="submit" name="update_candidate" class="btn-sm btn-success">Update</button>
                <a href="admin.php?election_id=<?php echo $selected_election_id; ?>" class="btn-sm btn-danger">Cancel</a>
            </form>
        </div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data" style="margin-bottom:20px; padding:10px; background:#f9f9f9;">
            <input type="hidden" name="election_id" value="<?php echo $selected_election_id; ?>">
            <input type="text" name="name" placeholder="Candidate Name" required>
            <input type="text" name="position" placeholder="Position" required>
            <input type="text" name="party" placeholder="Party"><br>
            <textarea name="bio" placeholder="Biography" rows="2"></textarea>
            <textarea name="manifesto" placeholder="Manifesto" rows="2"></textarea><br>
            <input type="file" name="photo" accept="image/*"><br>
            <button type="submit" name="add_candidate" class="btn-sm btn-primary">+ Add Candidate</button>
        </form>
        <table>
            <tr><th>Photo</th><th>Name</th><th>Position</th><th>Party</th><th>Votes</th><th>Actions</th></tr>
            <?php foreach($candidates as $c): ?>
            <tr>
                <td><?php if($c['photo']) echo "<img src='uploads/{$c['photo']}' class='candidate-photo-preview'>"; else echo "—"; ?></td>
                <td><?php echo $c['name']; ?></td>
                <td><?php echo $c['position']; ?></td>
                <td><?php echo $c['party'] ?: 'Independent'; ?></td>
                <td><?php echo $c['votes']; ?></td>
                <td>
                    <a href="admin.php?edit_candidate_id=<?php echo $c['id']; ?>&election_id=<?php echo $selected_election_id; ?>" class="btn-sm btn-info">Edit</a>
                    <form method="POST" style="display:inline;"><input type="hidden" name="delete_candidate" value="<?php echo $c['id']; ?>"><button class="btn-sm btn-danger" onclick="return confirm('Delete candidate?')">Delete</button></form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Voter Management -->
    <div class="admin-card">
        <h3>👥 Manage Voters</h3>
        <?php if(isset($voter_msg)) echo "<p class='success'>$voter_msg</p>"; ?>
        <?php if(isset($voter_error)) echo "<p class='error'>$voter_error</p>"; ?>
        <?php if($edit_voter): ?>
        <div class="edit-form">
            <h4>Edit Voter</h4>
            <form method="POST">
                <input type="hidden" name="voter_id" value="<?php echo $edit_voter['id']; ?>">
                <input type="text" name="name" value="<?php echo $edit_voter['name']; ?>" required><br>
                <input type="email" name="email" value="<?php echo $edit_voter['email']; ?>" required><br>
                <input type="text" name="phone" value="<?php echo $edit_voter['phone']; ?>" required><br>
                <input type="text" name="student_id" value="<?php echo $edit_voter['student_id']; ?>" required><br>
                <button type="submit" name="update_voter" class="btn-sm btn-success">Update</button>
                <a href="admin.php" class="btn-sm btn-danger">Cancel</a>
            </form>
        </div>
        <?php endif; ?>
        <div class="edit-form">
            <h4>➕ Add New Voter</h4>
            <form method="POST">
                <input type="text" name="name" placeholder="Full Name" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="text" name="phone" placeholder="Phone" required>
                <input type="text" name="student_id" placeholder="Student ID" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="add_voter" class="btn-sm btn-primary">Add Voter</button>
            </form>
        </div>
        <table>
            <tr><th>ID</th><th>Name</th><th>Student ID</th><th>Email</th><th>Voted</th><th>Voted For</th><th>Actions</th></tr>
            <?php foreach($users as $u): ?>
            <tr>
                <td><?php echo $u['id']; ?></td>
                <td><?php echo $u['name']; ?></td>
                <td><?php echo $u['student_id']; ?></td>
                <td><?php echo $u['email']; ?></td>
                <td><?php echo $u['status'] ? '✅' : '❌'; ?></td>
                <td><?php echo $u['voted_for'] ?: '—'; ?></td>
                <td>
                    <a href="admin.php?edit_voter_id=<?php echo $u['id']; ?>" class="btn-sm btn-info">Edit</a>
                    <?php if($u['status']): ?>
                    <form method="POST" style="display:inline;"><input type="hidden" name="voter_id" value="<?php echo $u['id']; ?>"><button name="reset_single_vote" class="btn-sm btn-warning">Reset Vote</button></form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;"><input type="hidden" name="delete_voter" value="<?php echo $u['id']; ?>"><button class="btn-sm btn-danger" onclick="return confirm('Delete voter?')">Delete</button></form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Result Publishing & Charts -->
    <div class="admin-card">
        <h3>📢 Results Management</h3>
        <div>
            <form method="POST" style="display:inline;">
                <input type="hidden" name="toggle_results" value="<?php echo $results_published ? '0' : '1'; ?>">
                <button class="btn-sm <?php echo $results_published ? 'btn-warning' : 'btn-success'; ?>">
                    <?php echo $results_published ? '🔒 Unpublish Results' : '📢 Publish Results'; ?>
                </button>
            </form>
            <span style="margin-left:10px;">Current: <?php echo $results_published ? 'Published' : 'Hidden'; ?></span>
        </div>
        <?php if($candidates && $total_votes_election>0): ?>
        <div style="margin-top:20px;">
            <canvas id="adminPieChart" style="max-width:400px; max-height:400px;"></canvas>
            <canvas id="adminBarChart" style="max-width:500px; max-height:300px;"></canvas>
        </div>
        <script>
            const labels = <?php echo json_encode(array_column($candidates, 'name')); ?>;
            const votes = <?php echo json_encode(array_column($candidates, 'votes')); ?>;
            new Chart(document.getElementById('adminPieChart'), { type: 'pie', data: { labels: labels, datasets: [{ data: votes, backgroundColor: ['#28a745','#007bff','#ffc107','#dc3545','#17a2b8'] }] } });
            new Chart(document.getElementById('adminBarChart'), { type: 'bar', data: { labels: labels, datasets: [{ label: 'Votes', data: votes, backgroundColor: '#28a745' }] } });
        </script>
        <?php endif; ?>
    </div>
    <p style="text-align:center;"><a href="index.php">← Back to Voter Login</a></p>
</div>
<?php endif; ?>
</body>
</html>
