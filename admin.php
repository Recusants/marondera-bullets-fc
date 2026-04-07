<?php
require_once 'config.php';
session_start();

// Get database connection from config.php
$conn = getDBConnection();

// Handle login
if (isset($_POST['login'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $result = $conn->query("SELECT * FROM users WHERE username='$username'");
    if ($result && $result->num_rows) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin'] = true;
            header('Location: admin.php');
            exit;
        }
    }
    header('Location: admin.php?error=1');
    exit;
}

if (isset($_GET['logout'])) { 
    session_destroy(); 
    header('Location: admin.php'); 
    exit; 
}

// Handle AJAX requests
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    if (!isset($_SESSION['admin'])) {
        echo json_encode(['success'=>false, 'message'=>'Not authenticated']);
        exit;
    }
    
    if (isset($_POST['add_match'])) {
        $stmt = $conn->prepare("INSERT INTO matches (opponent, location, match_date, match_time, is_home, score_home, score_away) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssii", $_POST['opponent'], $_POST['location'], $_POST['match_date'], $_POST['match_time'], isset($_POST['is_home'])?1:0, $_POST['score_home'] ?: NULL, $_POST['score_away'] ?: NULL);
        $stmt->execute();
        echo json_encode(['success'=>true, 'message'=>'Match added successfully!']);
        exit;
    }
    if (isset($_POST['delete_match'])) {
        $conn->query("DELETE FROM matches WHERE id=".intval($_POST['id']));
        echo json_encode(['success'=>true, 'message'=>'Match deleted!']);
        exit;
    }
    if (isset($_POST['update_match'])) {
        $stmt = $conn->prepare("UPDATE matches SET score_home=?, score_away=? WHERE id=?");
        $stmt->bind_param("iii", $_POST['score_home'], $_POST['score_away'], $_POST['id']);
        $stmt->execute();
        echo json_encode(['success'=>true, 'message'=>'Scores updated!']);
        exit;
    }
    if (isset($_POST['update_league'])) {
        $points = ($_POST['wins'] * 3) + $_POST['draws'];
        $stmt = $conn->prepare("UPDATE league_standings SET played=?, wins=?, draws=?, losses=?, goals_for=?, goals_against=?, points=? WHERE id=?");
        $stmt->bind_param("iiiiiiii", $_POST['played'], $_POST['wins'], $_POST['draws'], $_POST['losses'], $_POST['goals_for'], $_POST['goals_against'], $points, $_POST['id']);
        $stmt->execute();
        echo json_encode(['success'=>true, 'message'=>'League standings updated!']);
        exit;
    }
}

// If not AJAX, serve HTML
$matches = $conn->query("SELECT * FROM matches ORDER BY match_date DESC");
$league = $conn->query("SELECT * FROM league_standings ORDER BY position");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin - Marondera Bullets</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #f0f2f5; padding: 40px 20px; }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: #1C5A2A; color: white; padding: 20px; border-radius: 16px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .login-box { max-width: 400px; margin: 100px auto; background: white; padding: 40px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        input, select { padding: 12px 16px; border: 1px solid #ddd; border-radius: 12px; font-size: 14px; width: 100%; margin-bottom: 12px; }
        button { background: #E7B42C; color: #1C5A2A; padding: 12px 24px; border: none; border-radius: 30px; font-weight: 700; cursor: pointer; }
        .card { background: white; border-radius: 24px; padding: 24px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; }
        .league-table-editor { width: 100%; border-collapse: collapse; background: white; border-radius: 16px; overflow: hidden; }
        .league-table-editor th, .league-table-editor td { padding: 10px 8px; text-align: center; border: 1px solid #ddd; }
        .league-table-editor input { width: 60px; padding: 6px; text-align: center; margin: 0; display: inline-block; }
        .match-row { display: flex; gap: 10px; margin-bottom: 15px; padding: 15px; background: #f9f9f9; border-radius: 16px; flex-wrap: wrap; align-items: center; }
        .match-row input { margin: 0; width: auto; min-width: 70px; }
        .error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
        @media (max-width: 768px) { .grid-2 { grid-template-columns: 1fr; } .match-row { flex-direction: column; } .match-row input { width: 100%; } }
    </style>
</head>
<body>
<div class="container">
    <?php if (!isset($_SESSION['admin'])): ?>
    <div class="login-box">
        <h2 style="margin-bottom: 24px; color: #1C5A2A;"><i class="fas fa-lock"></i> Admin Login</h2>
        <?php if(isset($_GET['error'])): ?><div class="error-msg">Invalid credentials</div><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit" name="login"><i class="fas fa-sign-in-alt"></i> Login</button>
        </form>
        <p style="margin-top: 16px; font-size: 12px; color: #666;">Username: admin | Password: admin123</p>
    </div>
    <?php else: 
        $matches = $conn->query("SELECT * FROM matches ORDER BY match_date DESC");
        $league = $conn->query("SELECT * FROM league_standings ORDER BY position");
    ?>
    <div class="header">
        <h1><i class="fas fa-futbol"></i> Marondera Bullets - Admin</h1>
        <a href="?logout=1" style="color:white; background:rgba(255,255,255,0.2); padding:10px 20px; border-radius:30px; text-decoration:none;"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>

    <div class="grid-2">
        <div class="card">
            <h3><i class="fas fa-plus-circle"></i> Add New Match</h3>
            <form id="addMatchForm">
                <input type="text" name="opponent" placeholder="Opponent Team" required>
                <input type="text" name="location" placeholder="Stadium/Location" required>
                <input type="date" name="match_date" required>
                <input type="text" name="match_time" placeholder="Time (e.g., 3:00 PM)" required>
                <label style="display:flex; align-items:center; gap:8px;"><input type="checkbox" name="is_home" value="1"> Home Match</label>
                <input type="number" name="score_home" placeholder="Home Score (optional)">
                <input type="number" name="score_away" placeholder="Away Score (optional)">
                <button type="submit"><i class="fas fa-save"></i> Add Match</button>
            </form>
        </div>

        <div class="card">
            <h3><i class="fas fa-table"></i> League Standings Editor</h3>
            <table class="league-table-editor">
                <thead><tr><th>Team</th><th>Pld</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>Action</th></tr></thead>
                <tbody>
                <?php while($t = $league->fetch_assoc()): ?>
                <tr data-id="<?php echo $t['id']; ?>">
                    <td><strong><?php echo htmlspecialchars($t['team_name']); ?></strong></td>
                    <td><input type="number" class="played" value="<?php echo $t['played']; ?>"></td>
                    <td><input type="number" class="wins" value="<?php echo $t['wins']; ?>"></td>
                    <td><input type="number" class="draws" value="<?php echo $t['draws']; ?>"></td>
                    <td><input type="number" class="losses" value="<?php echo $t['losses']; ?>"></td>
                    <td><input type="number" class="gf" value="<?php echo $t['goals_for']; ?>"></td>
                    <td><input type="number" class="ga" value="<?php echo $t['goals_against']; ?>"></td>
                    <td><button class="update-league-btn" data-id="<?php echo $t['id']; ?>">Update</button></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <h3><i class="fas fa-list"></i> Manage Matches</h3>
        <div id="matchesList">
            <?php while($m = $matches->fetch_assoc()): 
                $today = date('Y-m-d'); 
                $is_past = $m['match_date'] < $today; 
            ?>
            <div class="match-row" data-id="<?php echo $m['id']; ?>">
                <strong><?php echo htmlspecialchars($m['opponent']); ?></strong>
                <span><?php echo $m['match_date']; ?> <?php echo $m['match_time']; ?></span>
                <span><?php echo $m['is_home'] ? '🏠 Home' : '✈️ Away'; ?></span>
                <?php if($is_past): ?>
                    <input type="number" class="score-home" placeholder="H" value="<?php echo $m['score_home']; ?>" style="width:70px">
                    <input type="number" class="score-away" placeholder="A" value="<?php echo $m['score_away']; ?>" style="width:70px">
                    <button class="update-score-btn" data-id="<?php echo $m['id']; ?>">Update Score</button>
                <?php else: ?>
                    <span style="color:#E7B42C;">📅 Upcoming (no score yet)</span>
                <?php endif; ?>
                <button class="delete-match-btn" data-id="<?php echo $m['id']; ?>" style="background:#dc3545; color:white; padding:6px 12px;">Delete</button>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
async function apiCall(action, data) {
    const formData = new URLSearchParams({...data, [action]: '1', ajax: '1'});
    const res = await fetch('admin.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    });
    const json = await res.json();
    if (json.success) {
        await Swal.fire({ icon: 'success', title: 'Success!', text: json.message, timer: 1500, showConfirmButton: false });
        location.reload();
    } else {
        Swal.fire({ icon: 'error', title: 'Error', text: json.message || 'Something went wrong' });
    }
}

<?php if(isset($_SESSION['admin'])): ?>
document.getElementById('addMatchForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    const data = {};
    formData.forEach((v, k) => data[k] = v);
    await apiCall('add_match', data);
});

document.querySelectorAll('.update-league-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const row = btn.closest('tr');
        await apiCall('update_league', {
            id: btn.dataset.id,
            played: row.querySelector('.played').value,
            wins: row.querySelector('.wins').value,
            draws: row.querySelector('.draws').value,
            losses: row.querySelector('.losses').value,
            goals_for: row.querySelector('.gf').value,
            goals_against: row.querySelector('.ga').value
        });
    });
});

document.querySelectorAll('.update-score-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const row = btn.closest('.match-row');
        await apiCall('update_match', {
            id: btn.dataset.id,
            score_home: row.querySelector('.score-home').value,
            score_away: row.querySelector('.score-away').value
        });
    });
});

document.querySelectorAll('.delete-match-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const result = await Swal.fire({ title: 'Delete match?', text: 'This cannot be undone', icon: 'warning', showCancelButton: true });
        if (result.isConfirmed) {
            await apiCall('delete_match', { id: btn.dataset.id });
        }
    });
});
<?php endif; ?>
</script>
</body>
</html>