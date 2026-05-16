<?php
session_start();
include("db.php");

if(!isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$uid = $_SESSION['user_id'];
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $uid");
$user = mysqli_fetch_assoc($user_query);
if(!$user){
    session_destroy();
    header("Location: index.php");
    exit();
}
$_SESSION['user_data'] = $user;

$now = date('Y-m-d H:i:s');

// Fetch all elections
$elections_result = mysqli_query($conn, "SELECT * FROM elections ORDER BY start_date DESC");
$elections = mysqli_fetch_all($elections_result, MYSQLI_ASSOC);

$active_election = null;
$upcoming_election = null;
$finished_election = null;

foreach($elections as $e){
    if($e['status'] == 'active' && $now >= $e['start_date'] && $now <= $e['end_date']){
        $active_election = $e;
    } elseif($e['status'] == 'upcoming' || strtotime($e['start_date']) > strtotime($now)){
        if(!$upcoming_election) $upcoming_election = $e;
    } else {
        $finished_election = $e;
    }
}

$display_election = $active_election ?: ($upcoming_election ?: $finished_election);
$election_id = $display_election ? $display_election['id'] : 0;

// Fetch candidates for the displayed election
$candidates = [];
if($election_id > 0){
    $candidates_result = mysqli_query($conn, "SELECT * FROM candidates WHERE election_id = $election_id ORDER BY votes DESC");
    $candidates = mysqli_fetch_all($candidates_result, MYSQLI_ASSOC);
}

// Check if results are published
$settings_result = mysqli_query($conn, "SELECT setting_value FROM settings WHERE setting_key = 'results_published'");
$settings_row = mysqli_fetch_assoc($settings_result);
$results_published = ($settings_row && $settings_row['setting_value'] == '1');

$total_votes = array_sum(array_column($candidates, 'votes'));
$max_votes = $total_votes > 0 ? max(array_column($candidates, 'votes')) : 0;
$winner = null;
foreach($candidates as $c){
    if($c['votes'] == $max_votes && $max_votes > 0){
        $winner = $c;
        break;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Voter Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .candidate-card img { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; }
        .result-bar-wrap { background: #eee; border-radius: 20px; height: 14px; margin: 5px 0; overflow: hidden; }
        .result-bar { height: 14px; background: #28a745; width: 0; }
        .winner-badge { background: gold; padding: 2px 8px; border-radius: 20px; font-size: 12px; }
        .election-status { padding: 8px 12px; border-radius: 20px; display: inline-block; }
        .status-active { background: #d4edda; color: #155724; }
        .status-upcoming { background: #fff3cd; color: #856404; }
        .status-ended { background: #f8d7da; color: #721c24; }
        .alert-box { background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin-bottom: 20px; border-radius: 12px; }
    </style>
</head>
<body>
<div class="dashboard-container">
    <a href="logout.php" style="float: right;">🚪 Logout</a>
    <h2>🗳️ Voter Dashboard</h2>
    <p>Welcome, <strong><?php echo htmlspecialchars($user['name']); ?></strong></p>

    <!-- Profile Section -->
    <div class="section-box">
        <h3>👤 My Profile</h3>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
        <p><strong>Student ID:</strong> <?php echo htmlspecialchars($user['student_id']); ?></p>
        <p><strong>Voting Status:</strong> 
            <?php if($user['status'] == 1): ?>
                <span style="color:green;">✓ Voted for <?php echo htmlspecialchars($user['voted_for']); ?></span>
            <?php else: ?>
                <span style="color:red;">✗ Not voted yet</span>
            <?php endif; ?>
        </p>
    </div>

    <!-- Election Status -->
    <div class="section-box">
        <h3>📅 Election Status</h3>
        <?php if($display_election): ?>
            <p><strong><?php echo htmlspecialchars($display_election['title']); ?></strong> (<?php echo $display_election['type']; ?>)</p>
            <p>📅 <?php echo date('M d, Y H:i', strtotime($display_election['start_date'])); ?> → <?php echo date('M d, Y H:i', strtotime($display_election['end_date'])); ?></p>
            <p class="election-status status-<?php echo $display_election['status']; ?>">
                <?php 
                if($active_election) echo "🟢 ONGOING";
                elseif($upcoming_election && $display_election['id'] == $upcoming_election['id']) echo "⏳ UPCOMING";
                else echo "🔴 FINISHED";
                ?>
            </p>
            <p><strong>Rules:</strong> <?php echo nl2br(htmlspecialchars($display_election['voting_rules'])); ?></p>
        <?php else: ?>
            <div class="alert-box">
                ⚠️ No election has been created yet. Please contact the administrator.
            </div>
        <?php endif; ?>
    </div>

    <!-- Voting Section -->
    <?php if($active_election && $user['status'] == 0): ?>
        <?php if(count($candidates) > 0): ?>
        <div class="section-box">
            <h3>✅ Cast Your Vote</h3>
            <p style="margin-bottom: 15px;">Select one candidate and click submit. You can only vote once.</p>
            <form action="vote.php" method="POST">
                <input type="hidden" name="election_id" value="<?php echo $active_election['id']; ?>">
                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                <?php foreach($candidates as $c): ?>
                    <div class="candidate-card">
                        <?php if($c['photo']): ?>
                            <img src="uploads/<?php echo $c['photo']; ?>">
                        <?php else: ?>
                            <div style="width:100px;height:100px;background:#ddd;border-radius:50%;line-height:100px;">No Photo</div>
                        <?php endif; ?>
                        <h4><?php echo htmlspecialchars($c['name']); ?></h4>
                        <p><strong>Party:</strong> <?php echo htmlspecialchars($c['party'] ?: 'Independent'); ?></p>
                        <p><em><?php echo htmlspecialchars($c['position']); ?></em></p>
                        <label style="display: block; margin-top: 10px; cursor: pointer;">
                            <input type="radio" name="candidate_id" value="<?php echo $c['id']; ?>" required> Select this candidate
                        </label>
                    </div>
                <?php endforeach; ?>
                </div>
                <br>
                <button type="submit" name="vote">🗳️ Submit Your Vote</button>
            </form>
        </div>
        <?php else: ?>
        <div class="section-box">
            <h3>⚠️ No Candidates</h3>
            <p>No candidates have been added for this election. Voting cannot proceed until candidates are added by the admin.</p>
        </div>
        <?php endif; ?>
    <?php elseif($user['status'] == 1): ?>
    <div class="section-box" style="border-color:green;">
        <h3>✅ Vote Recorded</h3>
        <p>You voted for <strong><?php echo htmlspecialchars($user['voted_for']); ?></strong> in the <?php echo htmlspecialchars($display_election['title'] ?? 'current'); ?> election.</p>
    </div>
    <?php elseif(!$active_election && $user['status'] == 0): ?>
    <div class="section-box">
        <h3>⏳ Voting Not Open</h3>
        <p>No active election at the moment. Please check back later or contact the administrator.</p>
    </div>
    <?php endif; ?>

    <!-- Candidate Information -->
    <div class="section-box">
        <h3>📋 Candidate Information</h3>
        <?php if(count($candidates) > 0): ?>
            <?php foreach($candidates as $c): ?>
                <div style="border-bottom:1px solid #ddd; padding:15px 0; display:flex; gap:20px;">
                    <div>
                        <?php if($c['photo']): ?>
                            <img src="uploads/<?php echo $c['photo']; ?>" style="width:120px;height:120px;object-fit:cover;border-radius:10px;">
                        <?php else: ?>
                            <div style="width:120px;height:120px;background:#ddd;border-radius:10px;text-align:center;line-height:120px;">No Image</div>
                        <?php endif; ?>
                    </div>
                    <div style="flex:1">
                        <h3><?php echo htmlspecialchars($c['name']); ?></h3>
                        <p><strong>Party:</strong> <?php echo htmlspecialchars($c['party'] ?: 'Independent'); ?></p>
                        <p><strong>Position:</strong> <?php echo htmlspecialchars($c['position']); ?></p>
                        <p><strong>Biography:</strong> <?php echo nl2br(htmlspecialchars($c['bio'])); ?></p>
                        <p><strong>Manifesto:</strong> <?php echo nl2br(htmlspecialchars($c['manifesto'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No candidates have been added for this election yet.</p>
        <?php endif; ?>
    </div>

    <!-- Results Section -->
    <?php if($results_published && count($candidates) > 0): ?>
    <div class="section-box">
        <h3>🏆 Election Results</h3>
        <p><strong>Total Votes:</strong> <?php echo $total_votes; ?></p>
        <?php if($winner): ?>
            <p><strong>🏅 Winner:</strong> <?php echo htmlspecialchars($winner['name']); ?> (<?php echo $winner['votes']; ?> votes)</p>
        <?php endif; ?>
        <div style="display:flex; flex-wrap:wrap; gap:20px;">
            <div style="flex:1; min-width:250px;"><canvas id="resultPieChart"></canvas></div>
            <div style="flex:1; min-width:250px;"><canvas id="resultBarChart"></canvas></div>
        </div>
        <?php foreach($candidates as $c): 
            $pct = $total_votes > 0 ? round(($c['votes']/$total_votes)*100,1) : 0;
        ?>
            <div>
                <div style="display:flex; justify-content:space-between;">
                    <span><?php echo htmlspecialchars($c['name']); ?> (<?php echo $c['votes']; ?> votes)</span>
                    <span><?php echo $pct; ?>%</span>
                </div>
                <div class="result-bar-wrap"><div class="result-bar" style="width:<?php echo $pct; ?>%;"></div></div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php elseif(!$results_published && count($candidates) > 0): ?>
    <div class="section-box">
        <p>🔒 Results have not been published yet. Please check later.</p>
    </div>
    <?php endif; ?>
</div>

<script>
    <?php if($results_published && count($candidates) > 0 && $total_votes > 0): ?>
    const labels = <?php echo json_encode(array_column($candidates, 'name')); ?>;
    const votesData = <?php echo json_encode(array_column($candidates, 'votes')); ?>;
    new Chart(document.getElementById('resultPieChart'), { type: 'pie', data: { labels: labels, datasets: [{ data: votesData, backgroundColor: ['#28a745','#007bff','#ffc107','#dc3545','#17a2b8'] }] } });
    new Chart(document.getElementById('resultBarChart'), { type: 'bar', data: { labels: labels, datasets: [{ label: 'Votes', data: votesData, backgroundColor: '#28a745' }] } });
    <?php endif; ?>
</script>
</body>
</html>