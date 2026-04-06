<?php
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'bullets_fc';

$conn = new mysqli($db_host, $db_user, $db_pass);
$conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
$conn->select_db($db_name);

// Create tables
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
)");

$conn->query("
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opponent VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    match_date DATE NOT NULL,
    match_time VARCHAR(20),
    is_home BOOLEAN DEFAULT TRUE,
    score_home INT,
    score_away INT
)");

$conn->query("
CREATE TABLE IF NOT EXISTS league_standings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    position INT NOT NULL,
    team_name VARCHAR(100) NOT NULL,
    played INT DEFAULT 0,
    wins INT DEFAULT 0,
    draws INT DEFAULT 0,
    losses INT DEFAULT 0,
    goals_for INT DEFAULT 0,
    goals_against INT DEFAULT 0,
    points INT DEFAULT 0
)");

// Insert default admin
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$conn->query("INSERT IGNORE INTO users (username, password) VALUES ('admin', '$admin_pass')");

// Insert sample data if empty
$check = $conn->query("SELECT COUNT(*) as c FROM matches")->fetch_assoc();
if ($check['c'] == 0) {
    $matches_data = [
        ['Black Rhinos', 'Sakubva Stadium', '2026-04-12', '3:00 PM', 1, null, null],
        ['Dynamos FC', 'National Stadium', '2026-04-19', '3:30 PM', 0, null, null],
        ['Ngezi Platinum', 'Sakubva Stadium', '2026-04-27', '3:00 PM', 1, null, null],
        ['Hwange FC', 'Sakubva Stadium', '2026-03-29', '3:00 PM', 1, 3, 1],
        ['Highlanders', 'Barbourfields', '2026-03-22', '3:00 PM', 0, 2, 0],
        ['CAPS United', 'National Stadium', '2026-03-15', '3:00 PM', 0, 1, 1],
        ['Manica Diamonds', 'Sakubva Stadium', '2026-03-08', '3:00 PM', 1, 2, 0]
    ];
    foreach ($matches_data as $m) {
        $stmt = $conn->prepare("INSERT INTO matches (opponent, location, match_date, match_time, is_home, score_home, score_away) VALUES (?,?,?,?,?,?,?)");
        $stmt->bind_param("sssssii", $m[0], $m[1], $m[2], $m[3], $m[4], $m[5], $m[6]);
        $stmt->execute();
    }
}

$check2 = $conn->query("SELECT COUNT(*) as c FROM league_standings")->fetch_assoc();
if ($check2['c'] == 0) {
    $league_data = [
        [1, 'Dynamos FC', 26, 16, 6, 4, 45, 22, 54],
        [2, 'CAPS United', 26, 15, 6, 5, 41, 20, 51],
        [3, 'Marondera Bullets', 26, 15, 4, 7, 44, 24, 49],
        [4, 'Ngezi Platinum', 26, 13, 7, 6, 38, 24, 46],
        [5, 'Highlanders', 26, 12, 6, 8, 33, 27, 42],
        [6, 'Black Rhinos', 26, 10, 8, 8, 30, 28, 38],
        [7, 'Manica Diamonds', 26, 9, 7, 10, 31, 33, 34],
        [8, 'Hwange FC', 26, 7, 5, 14, 22, 39, 26]
    ];
    foreach ($league_data as $d) {
        $stmt = $conn->prepare("INSERT INTO league_standings (position, team_name, played, wins, draws, losses, goals_for, goals_against, points) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->bind_param("isiiiiiii", $d[0], $d[1], $d[2], $d[3], $d[4], $d[5], $d[6], $d[7], $d[8]);
        $stmt->execute();
    }
}

// Calculate hero stats
$today = date('Y-m-d');
$all_matches = $conn->query("SELECT * FROM matches WHERE match_date < '$today' AND score_home IS NOT NULL AND score_away IS NOT NULL");
$wins = 0; $draws = 0; $losses = 0;
while($m = $all_matches->fetch_assoc()) {
    if(($m['is_home'] && $m['score_home'] > $m['score_away']) || (!$m['is_home'] && $m['score_home'] < $m['score_away'])) $wins++;
    elseif($m['score_home'] == $m['score_away']) $draws++;
    else $losses++;
}

$upcoming = $conn->query("SELECT * FROM matches WHERE match_date >= '$today' ORDER BY match_date ASC LIMIT 3");
$past = $conn->query("SELECT * FROM matches WHERE match_date < '$today' ORDER BY match_date DESC LIMIT 6");
$league = $conn->query("SELECT * FROM league_standings ORDER BY position");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marondera Bullets FC</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --bullets-green: #1C5A2A;
            --bullets-gold: #E7B42C;
            --bullets-dark: #0C2A13;
            --bg-primary: #FEF9EF;
            --bg-secondary: #FFFFFF;
            --text-primary: #1E2A1B;
            --card-bg: #FFFFFF;
        }
        body.dark {
            --bg-primary: #121212;
            --bg-secondary: #1E1E1E;
            --text-primary: #E0E0E0;
            --card-bg: #2D2D2D;
        }
        body { font-family: 'Inter', sans-serif; background: var(--bg-primary); color: var(--text-primary); transition: all 0.3s; }
        .container { max-width: 1300px; margin: 0 auto; padding: 0 28px; }
        .theme-toggle { background: none; border: none; font-size: 1.3rem; cursor: pointer; color: var(--bullets-green); padding: 8px; border-radius: 50%; }
        header { background: var(--bg-secondary); box-shadow: 0 2px 12px rgba(0,0,0,0.05); position: sticky; top: 0; z-index: 1000; }
        .navbar { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; }
        .logo-area { display: flex; align-items: center; gap: 14px; }
        .logo-badge { width: 48px; height: 48px; background: var(--bullets-green); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 24px; color: var(--bullets-gold); border: 2px solid var(--bullets-gold); }
        .club-text h1 { font-size: 1.3rem; font-weight: 800; color: var(--bullets-green); }
        .club-text span { font-size: 0.65rem; font-weight: 600; color: var(--bullets-gold); }
        .nav-links { display: flex; gap: 32px; list-style: none; align-items: center; }
        .nav-links a { text-decoration: none; font-weight: 600; color: var(--text-primary); transition: 0.2s; display: flex; align-items: center; gap: 6px; }
        .nav-links a:hover { color: var(--bullets-gold); }
        .mobile-menu { display: none; font-size: 1.8rem; cursor: pointer; background: none; border: none; color: var(--bullets-green); }
        .hero { position: relative; min-height: 80vh; display: flex; align-items: center; overflow: hidden; background: linear-gradient(115deg, #0C2A13 0%, #1C5A2A 100%); }
        .hero .container { position: relative; z-index: 2; width: 100%; }
        .hero-grid { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 32px; }
        .hero-text h2 { font-size: 3rem; font-weight: 800; margin-bottom: 20px; color: white; }
        .hero-text p { font-size: 1.1rem; max-width: 650px; margin: 0 auto 28px; color: rgba(255,255,255,0.9); }
        .quick-stats { display: flex; flex-wrap: wrap; justify-content: center; gap: 50px; background: rgba(0,0,0,0.3); backdrop-filter: blur(8px); padding: 24px 48px; border-radius: 60px; }
        .stat-item { text-align: center; }
        .stat-number { font-size: 2.5rem; font-weight: 800; color: var(--bullets-gold); }
        .stat-label { font-size: 0.85rem; text-transform: uppercase; color: white; letter-spacing: 1px; }
        .section-title { font-size: 2rem; font-weight: 800; margin-bottom: 48px; position: relative; display: inline-block; color: var(--text-primary); }
        .section-title:after { content: ''; position: absolute; bottom: -12px; left: 0; width: 70px; height: 4px; background: var(--bullets-gold); border-radius: 4px; }
        .matches-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; }
        .match-card { background: var(--card-bg); border-radius: 28px; overflow: hidden; box-shadow: 0 20px 30px -12px rgba(0,0,0,0.12); transition: 0.2s; }
        .match-header { padding: 14px 20px; display: flex; justify-content: space-between; font-weight: 700; }
        .match-upcoming .match-header { background: var(--bullets-green); color: white; }
        .match-past .match-header { background: var(--bullets-gold); color: #1c3a1a; }
        .match-body { padding: 24px; }
        .team-vs { display: flex; align-items: center; justify-content: space-between; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
        .team-box { flex: 1; text-align: center; padding: 12px; border-radius: 20px; font-weight: 800; font-size: 1.1rem; }
        .team-home { background: rgba(28, 90, 42, 0.15); color: var(--bullets-green); border: 2px solid var(--bullets-green); }
        .team-away { background: rgba(231, 180, 44, 0.15); color: #b8860b; border: 2px solid var(--bullets-gold); }
        .team-vs-icon { font-size: 1.2rem; font-weight: 800; color: var(--bullets-gold); }
        .match-location { text-align: center; color: var(--text-secondary); font-size: 0.85rem; }
        .table-wrapper { overflow-x: auto; border-radius: 28px; box-shadow: 0 20px 30px -12px rgba(0,0,0,0.12); background: var(--card-bg); }
        .league-table { width: 100%; border-collapse: collapse; }
        .league-table th { background: var(--bullets-green); color: white; padding: 16px; text-align: left; }
        .league-table td { padding: 14px; border-bottom: 1px solid rgba(0,0,0,0.05); }
        .highlight-row { background: rgba(231, 180, 44, 0.2); font-weight: 800; }
        .contact-section { background: var(--bullets-green); color: white; text-align: center; padding: 80px 0; }
        .social-links-row { display: flex; justify-content: center; gap: 32px; margin: 40px 0; }
        .social-icon { background: white; color: var(--bullets-green); width: 64px; height: 64px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 2rem; transition: 0.2s; text-decoration: none; }
        footer { background: var(--bullets-dark); color: #E9E2CF; padding: 40px 0; text-align: center; }
        @media (max-width: 880px) {
            .mobile-menu { display: block; }
            .nav-links { position: absolute; top: 75px; left: 0; width: 100%; background: var(--bg-secondary); flex-direction: column; gap: 20px; padding: 28px; display: none; z-index: 999; }
            .nav-links.active { display: flex; }
            .hero-text h2 { font-size: 2rem; }
            .team-vs { flex-direction: column; }
            .team-box { width: 100%; }
        }
        @media (min-width: 881px) { .nav-links { display: flex !important; } }
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="navbar">
            <div class="logo-area"><div class="logo-badge">MB</div><div class="club-text"><h1>MARONDERA BULLETS</h1><span>FOOTBALL CLUB • EST. 2024</span></div></div>
            <ul class="nav-links" id="navLinks"><li><a href="#home"><i class="fas fa-home"></i> Home</a></li><li><a href="#upcoming"><i class="fas fa-calendar-alt"></i> Upcoming</a></li><li><a href="#past"><i class="fas fa-history"></i> Past</a></li><li><a href="#league"><i class="fas fa-table"></i> League</a></li><li><a href="#contact"><i class="fas fa-envelope"></i> Follow</a></li></ul>
            <button class="theme-toggle" id="themeToggle"><i class="fas fa-moon"></i></button>
            <button class="mobile-menu" id="mobileMenuBtn"><i class="fas fa-bars"></i></button>
        </div>
    </div>
</header>
<main>
    <section id="home" class="hero"><div class="container hero-grid"><div class="hero-text"><h2>Bullets Precision.<br>One Shot, One Glory.</h2><p>Representing Marondera with speed, power, and bulletproof spirit.</p><div class="quick-stats"><div class="stat-item"><div class="stat-number"><?php echo $wins; ?></div><div class="stat-label">Wins</div></div><div class="stat-item"><div class="stat-number"><?php echo $draws; ?></div><div class="stat-label">Draws</div></div><div class="stat-item"><div class="stat-number"><?php echo $losses; ?></div><div class="stat-label">Losses</div></div></div></div></div></section>

    <section id="upcoming" style="padding: 80px 0;"><div class="container"><h3 class="section-title"><i class="fas fa-calendar-week"></i> Upcoming Fixtures</h3><div class="matches-grid"><?php while($m = $upcoming->fetch_assoc()): ?><div class="match-card match-upcoming"><div class="match-header"><span><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($m['match_date'])); ?></span><span><?php echo $m['match_time']; ?></span></div><div class="match-body"><div class="team-vs"><div class="team-box team-home"><i class="fas fa-shield-alt"></i> Marondera Bullets</div><div class="team-vs-icon">VS</div><div class="team-box team-away"><i class="fas fa-futbol"></i> <?php echo htmlspecialchars($m['opponent']); ?></div></div><div class="match-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($m['location']); ?> • <?php echo $m['is_home'] ? 'Home' : 'Away'; ?></div></div></div><?php endwhile; ?></div></div></section>

    <section id="past" style="background: var(--bg-secondary); padding: 80px 0;"><div class="container"><h3 class="section-title"><i class="fas fa-history"></i> Past Matches</h3><div class="matches-grid"><?php while($m = $past->fetch_assoc()): $is_win = ($m['is_home'] && $m['score_home'] > $m['score_away']) || (!$m['is_home'] && $m['score_home'] < $m['score_away']); $is_draw = ($m['score_home'] == $m['score_away']); $result = $is_win ? 'WIN' : ($is_draw ? 'DRAW' : 'LOSS'); ?><div class="match-card match-past"><div class="match-header"><span><i class="fas fa-check-circle"></i> <?php echo date('M j, Y', strtotime($m['match_date'])); ?></span><span style="background: <?php echo $is_win ? '#28a745' : ($is_draw ? '#ffc107' : '#dc3545'); ?>; padding: 4px 12px; border-radius: 20px; color: white;"><?php echo $result; ?></span></div><div class="match-body"><div class="team-vs"><div class="team-box team-home">Bullets</div><div class="team-vs-icon"><?php echo $m['score_home']; ?> - <?php echo $m['score_away']; ?></div><div class="team-box team-away"><?php echo htmlspecialchars($m['opponent']); ?></div></div><div class="match-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($m['location']); ?></div></div></div><?php endwhile; ?></div></div></section>

    <section id="league" style="padding: 80px 0;"><div class="container"><h3 class="section-title"><i class="fas fa-table"></i> League Standings</h3><div class="table-wrapper"><table class="league-table"><thead>运转<th>Pos</th><th>Team</th><th>Pld</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th></tr></thead><tbody><?php while($t = $league->fetch_assoc()): ?><tr class="<?php echo $t['team_name'] == 'Marondera Bullets' ? 'highlight-row' : ''; ?>"><td><?php echo $t['position']; ?></td><td><?php echo $t['team_name'] == 'Marondera Bullets' ? '<i class="fas fa-bullseye"></i> ' : ''; ?><?php echo htmlspecialchars($t['team_name']); ?></td><td><?php echo $t['played']; ?></td><td><?php echo $t['wins']; ?></td><td><?php echo $t['draws']; ?></td><td><?php echo $t['losses']; ?></td><td><?php echo $t['goals_for']; ?></td><td><?php echo $t['goals_against']; ?></td><td><?php echo $t['goals_for'] - $t['goals_against']; ?></td><td><?php echo $t['points']; ?></td></tr><?php endwhile; ?></tbody></table></div></div></section>

    <section id="contact" class="contact-section"><div class="container"><h3 style="font-size: 2rem;"><i class="fas fa-users"></i> Follow The Bullets</h3><div class="social-links-row"><a href="#" class="social-icon"><i class="fab fa-whatsapp"></i></a><a href="#" class="social-icon"><i class="fab fa-youtube"></i></a><a href="#" class="social-icon"><i class="fab fa-facebook-f"></i></a></div><p><i class="fas fa-envelope"></i> info@maronderabullets.co.zw | <i class="fas fa-phone-alt"></i> +263 77 234 5678</p></div></section>
</main>
<footer><div class="container"><p>© 2026 Marondera Bullets FC — Built with ⚡ from Zimbabwe.</p></div></footer>
<script>
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => { document.body.classList.toggle('dark'); localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light'); });
    if (localStorage.getItem('theme') === 'dark') document.body.classList.add('dark');
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    mobileBtn.addEventListener('click', () => navLinks.classList.toggle('active'));
    document.querySelectorAll('.nav-links a').forEach(link => link.addEventListener('click', () => navLinks.classList.remove('active')));
    document.querySelectorAll('a[href^="#"]').forEach(anchor => anchor.addEventListener('click', function(e) {
        const target = document.querySelector(this.getAttribute('href'));
        if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); navLinks.classList.remove('active'); }
    }));
</script>
</body>
</html>