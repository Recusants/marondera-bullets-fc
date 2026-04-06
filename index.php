<?php
require_once 'config.php';
session_start();

// Get database connection
$conn = getDBConnection();

// Calculate hero stats from database
$today = date('Y-m-d');
$all_matches = $conn->query("SELECT * FROM matches WHERE match_date < '$today' AND score_home IS NOT NULL AND score_away IS NOT NULL");
$wins = 0; 
$draws = 0; 
$losses = 0;

while($m = $all_matches->fetch_assoc()) {
    if(($m['is_home'] && $m['score_home'] > $m['score_away']) || (!$m['is_home'] && $m['score_home'] < $m['score_away'])) {
        $wins++;
    } elseif($m['score_home'] == $m['score_away']) {
        $draws++;
    } else {
        $losses++;
    }
}

// Fetch upcoming matches (future dates)
$upcoming = $conn->query("SELECT * FROM matches WHERE match_date >= '$today' ORDER BY match_date ASC LIMIT 3");

// Fetch past matches (past dates with scores)
$past = $conn->query("SELECT * FROM matches WHERE match_date < '$today' AND score_home IS NOT NULL ORDER BY match_date DESC LIMIT 6");

// Fetch league standings
$league = $conn->query("SELECT * FROM league_standings ORDER BY position");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marondera Bullets FC | Official Website</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --bullets-green: #1C5A2A;
            --bullets-gold: #E7B42C;
            --bullets-dark: #0C2A13;
            --bullets-cream: #FEF5E3;
            --bg-primary: #FEF9EF;
            --bg-secondary: #FFFFFF;
            --text-primary: #1E2A1B;
            --text-secondary: #4a5e46;
            --card-bg: #FFFFFF;
            --shadow-md: 0 20px 30px -12px rgba(0, 0, 0, 0.12);
        }

        body.dark {
            --bg-primary: #121212;
            --bg-secondary: #1E1E1E;
            --text-primary: #E0E0E0;
            --text-secondary: #B0B0B0;
            --card-bg: #2D2D2D;
            --bullets-cream: #2A2A2A;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            transition: all 0.3s ease;
        }

        .container {
            max-width: 1300px;
            margin: 0 auto;
            padding: 0 28px;
        }

        /* Header Styles */
        header {
            background: var(--bg-secondary);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 14px;
        }

        .logo-badge {
            width: 48px;
            height: 48px;
            background: var(--bullets-green);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 24px;
            color: var(--bullets-gold);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--bullets-gold);
        }

        .club-text h1 {
            font-size: 1.3rem;
            font-weight: 800;
            color: var(--bullets-green);
            line-height: 1.2;
        }

        body.dark .club-text h1 {
            color: var(--bullets-gold);
        }

        .club-text span {
            font-size: 0.65rem;
            font-weight: 600;
            color: var(--bullets-gold);
            letter-spacing: 1px;
        }

        .nav-links {
            display: flex;
            gap: 32px;
            list-style: none;
            align-items: center;
        }

        .nav-links a {
            text-decoration: none;
            font-weight: 600;
            color: var(--text-primary);
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-links a:hover {
            color: var(--bullets-gold);
        }

        .theme-toggle {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            color: var(--bullets-green);
            padding: 8px;
            border-radius: 50%;
            transition: 0.2s;
        }

        .theme-toggle:hover {
            background: var(--bullets-cream);
        }

        .mobile-menu {
            display: none;
            font-size: 1.8rem;
            cursor: pointer;
            background: none;
            border: none;
            color: var(--bullets-green);
        }

        /* Hero Section */
        .hero {
            position: relative;
            min-height: 85vh;
            display: flex;
            align-items: center;
            overflow: hidden;
            background: linear-gradient(135deg, var(--bullets-dark) 0%, var(--bullets-green) 100%);
        }

        .hero .container {
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .hero-grid {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 32px;
        }

        .hero-text h2 {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 20px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .hero-text p {
            font-size: 1.1rem;
            max-width: 650px;
            margin: 0 auto 28px;
            color: rgba(255, 255, 255, 0.9);
        }

        .quick-stats {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 50px;
            background: rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(8px);
            padding: 24px 48px;
            border-radius: 60px;
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--bullets-gold);
        }

        .stat-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            color: white;
            letter-spacing: 1px;
        }

        /* Section Styles */
        section {
            padding: 80px 0;
        }

        .section-title {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 48px;
            position: relative;
            display: inline-block;
            color: var(--text-primary);
        }

        .section-title:after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 0;
            width: 70px;
            height: 4px;
            background: var(--bullets-gold);
            border-radius: 4px;
        }

        /* Match Cards */
        .matches-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 30px;
        }

        .match-card {
            background: var(--card-bg);
            border-radius: 28px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .match-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.2);
        }

        .match-header {
            padding: 14px 20px;
            display: flex;
            justify-content: space-between;
            font-weight: 700;
        }

        .match-upcoming .match-header {
            background: var(--bullets-green);
            color: white;
        }

        .match-past .match-header {
            background: var(--bullets-gold);
            color: #1c3a1a;
        }

        .match-body {
            padding: 24px;
        }

        .team-vs {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .team-box {
            flex: 1;
            text-align: center;
            padding: 12px;
            border-radius: 20px;
            font-weight: 800;
            font-size: 1rem;
        }

        .team-home {
            background: rgba(28, 90, 42, 0.15);
            color: var(--bullets-green);
            border: 2px solid var(--bullets-green);
        }

        .team-away {
            background: rgba(231, 180, 44, 0.15);
            color: #b8860b;
            border: 2px solid var(--bullets-gold);
        }

        .team-vs-icon {
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--bullets-gold);
        }

        .match-location {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.85rem;
            margin-top: 12px;
        }

        /* League Table */
        .table-wrapper {
            overflow-x: auto;
            border-radius: 28px;
            box-shadow: var(--shadow-md);
            background: var(--card-bg);
        }

        .league-table {
            width: 100%;
            border-collapse: collapse;
        }

        .league-table th {
            background: var(--bullets-green);
            color: white;
            padding: 16px;
            text-align: left;
            font-weight: 700;
        }

        .league-table td {
            padding: 14px;
            border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        }

        .highlight-row {
            background: rgba(231, 180, 44, 0.2);
            font-weight: 800;
        }

        /* Contact Section */
        .contact-section {
            background: var(--bullets-green);
            color: white;
            text-align: center;
        }

        .social-links-row {
            display: flex;
            justify-content: center;
            gap: 32px;
            margin: 40px 0;
            flex-wrap: wrap;
        }

        .social-icon {
            background: white;
            color: var(--bullets-green);
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            transition: all 0.2s;
            text-decoration: none;
        }

        .social-icon:hover {
            background: var(--bullets-gold);
            transform: translateY(-5px);
        }

        /* Footer */
        footer {
            background: var(--bullets-dark);
            color: #E9E2CF;
            padding: 40px 0;
            text-align: center;
        }

        /* Responsive */
        @media (max-width: 880px) {
            .mobile-menu {
                display: block;
            }
            
            .nav-links {
                position: absolute;
                top: 75px;
                left: 0;
                width: 100%;
                background: var(--bg-secondary);
                flex-direction: column;
                gap: 20px;
                padding: 28px;
                display: none;
                z-index: 999;
                box-shadow: 0 20px 30px rgba(0, 0, 0, 0.1);
            }
            
            .nav-links.active {
                display: flex;
            }
            
            .hero-text h2 {
                font-size: 2rem;
            }
            
            .quick-stats {
                gap: 20px;
                padding: 16px 24px;
            }
            
            .team-vs {
                flex-direction: column;
            }
            
            .team-box {
                width: 100%;
            }
            
            .section-title {
                font-size: 1.5rem;
            }
        }

        @media (min-width: 881px) {
            .nav-links {
                display: flex !important;
            }
        }
    </style>
</head>
<body>
<header>
    <div class="container">
        <div class="navbar">
            <div class="logo-area">
                <div class="logo-badge">MB</div>
                <div class="club-text">
                    <h1>MARONDERA BULLETS</h1>
                    <span>FOOTBALL CLUB • EST. 2024</span>
                </div>
            </div>
            <ul class="nav-links" id="navLinks">
                <li><a href="#home"><i class="fas fa-home"></i> Home</a></li>
                <li><a href="#upcoming"><i class="fas fa-calendar-alt"></i> Upcoming</a></li>
                <li><a href="#past"><i class="fas fa-history"></i> Past</a></li>
                <li><a href="#league"><i class="fas fa-table"></i> League</a></li>
                <li><a href="#contact"><i class="fas fa-envelope"></i> Follow</a></li>
            </ul>
            <button class="theme-toggle" id="themeToggle">
                <i class="fas fa-moon"></i>
            </button>
            <button class="mobile-menu" id="mobileMenuBtn">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<main>
    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="container hero-grid">
            <div class="hero-text">
                <h2>Bullets Precision.<br>One Shot, One Glory.</h2>
                <p>Representing Marondera with speed, power, and bulletproof spirit. Join the #BulletsNation.</p>
                <div class="quick-stats">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $wins; ?></div>
                        <div class="stat-label">Wins</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $draws; ?></div>
                        <div class="stat-label">Draws</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $losses; ?></div>
                        <div class="stat-label">Losses</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Upcoming Matches -->
    <section id="upcoming">
        <div class="container">
            <h3 class="section-title"><i class="fas fa-calendar-week"></i> Upcoming Fixtures</h3>
            <div class="matches-grid">
                <?php if($upcoming->num_rows > 0): ?>
                    <?php while($match = $upcoming->fetch_assoc()): ?>
                    <div class="match-card match-upcoming">
                        <div class="match-header">
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('M j, Y', strtotime($match['match_date'])); ?></span>
                            <span><?php echo $match['match_time']; ?></span>
                        </div>
                        <div class="match-body">
                            <div class="team-vs">
                                <div class="team-box team-home">
                                    <i class="fas fa-shield-alt"></i> Marondera Bullets
                                </div>
                                <div class="team-vs-icon">VS</div>
                                <div class="team-box team-away">
                                    <i class="fas fa-futbol"></i> <?php echo htmlspecialchars($match['opponent']); ?>
                                </div>
                            </div>
                            <div class="match-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['location']); ?> • <?php echo $match['is_home'] ? 'Home' : 'Away'; ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1;">No upcoming matches scheduled.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Past Matches -->
    <section id="past" style="background: var(--bg-secondary);">
        <div class="container">
            <h3 class="section-title"><i class="fas fa-history"></i> Past Matches</h3>
            <div class="matches-grid">
                <?php if($past->num_rows > 0): ?>
                    <?php while($match = $past->fetch_assoc()): 
                        $is_win = ($match['is_home'] && $match['score_home'] > $match['score_away']) || (!$match['is_home'] && $match['score_home'] < $match['score_away']);
                        $is_draw = ($match['score_home'] == $match['score_away']);
                        $result = $is_win ? 'WIN' : ($is_draw ? 'DRAW' : 'LOSS');
                        $resultColor = $is_win ? '#28a745' : ($is_draw ? '#ffc107' : '#dc3545');
                    ?>
                    <div class="match-card match-past">
                        <div class="match-header">
                            <span><i class="fas fa-check-circle"></i> <?php echo date('M j, Y', strtotime($match['match_date'])); ?></span>
                            <span style="background: <?php echo $resultColor; ?>; padding: 4px 12px; border-radius: 20px; color: white;"><?php echo $result; ?></span>
                        </div>
                        <div class="match-body">
                            <div class="team-vs">
                                <div class="team-box team-home">Bullets</div>
                                <div class="team-vs-icon"><?php echo $match['score_home']; ?> - <?php echo $match['score_away']; ?></div>
                                <div class="team-box team-away"><?php echo htmlspecialchars($match['opponent']); ?></div>
                            </div>
                            <div class="match-location">
                                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['location']); ?>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="text-align: center; grid-column: 1/-1;">No past matches recorded.</p>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- League Standings -->
    <section id="league">
        <div class="container">
            <h3 class="section-title"><i class="fas fa-table"></i> League Standings</h3>
            <div class="table-wrapper">
                <table class="league-table">
                    <thead>
                        <tr><th>Pos</th><th>Team</th><th>Pld</th><th>W</th><th>D</th><th>L</th><th>GF</th><th>GA</th><th>GD</th><th>Pts</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($team = $league->fetch_assoc()): ?>
                        <tr class="<?php echo $team['team_name'] == 'Marondera Bullets' ? 'highlight-row' : ''; ?>">
                            <td><?php echo $team['position']; ?></td>
                            <td><?php echo $team['team_name'] == 'Marondera Bullets' ? '<i class="fas fa-bullseye"></i> ' : ''; ?><?php echo htmlspecialchars($team['team_name']); ?></td>
                            <td><?php echo $team['played']; ?></td>
                            <td><?php echo $team['wins']; ?></td>
                            <td><?php echo $team['draws']; ?></td>
                            <td><?php echo $team['losses']; ?></td>
                            <td><?php echo $team['goals_for']; ?></td>
                            <td><?php echo $team['goals_against']; ?></td>
                            <td><?php echo $team['goals_for'] - $team['goals_against']; ?></td>
                            <td><?php echo $team['points']; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    <!-- Contact & Social Links -->
    <section id="contact" class="contact-section">
        <div class="container">
            <h3 style="font-size: 2rem; margin-bottom: 16px;"><i class="fas fa-users"></i> Follow The Bullets</h3>
            <p style="margin-bottom: 24px;">Join our community for live match updates, behind-the-scenes, and exclusive content.</p>
            <div class="social-links-row">
                <a href="https://whatsapp.com/channel/0029VaBulletsFC" class="social-icon" target="_blank"><i class="fab fa-whatsapp"></i></a>
                <a href="https://youtube.com/@maronderabulletsfc" class="social-icon" target="_blank"><i class="fab fa-youtube"></i></a>
                <a href="https://facebook.com/maronderabullets" class="social-icon" target="_blank"><i class="fab fa-facebook-f"></i></a>
                <a href="https://twitter.com/maronderabullets" class="social-icon" target="_blank"><i class="fab fa-twitter"></i></a>
                <a href="https://instagram.com/maronderabullets" class="social-icon" target="_blank"><i class="fab fa-instagram"></i></a>
            </div>
            <div>
                <p><i class="fas fa-envelope"></i> info@maronderabullets.co.zw | <i class="fas fa-phone-alt"></i> +263 77 234 5678</p>
                <p><i class="fas fa-map-marker-alt"></i> Sakubva Stadium, Marondera, Zimbabwe</p>
            </div>
        </div>
    </section>
</main>

<footer>
    <div class="container">
        <p>© 2026 Marondera Bullets Football Club — Built with ⚡ from Zimbabwe.</p>
        <p style="margin-top: 12px; font-size: 0.85rem;">#BulletsNation #OneShotOneGlory</p>
    </div>
</footer>

<script>
    // Theme Toggle
    const themeToggle = document.getElementById('themeToggle');
    themeToggle.addEventListener('click', () => {
        document.body.classList.toggle('dark');
        localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
    });
    
    if (localStorage.getItem('theme') === 'dark') {
        document.body.classList.add('dark');
    }

    // Mobile Menu Toggle
    const mobileBtn = document.getElementById('mobileMenuBtn');
    const navLinks = document.getElementById('navLinks');
    
    mobileBtn.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });
    
    document.querySelectorAll('.nav-links a').forEach(link => {
        link.addEventListener('click', () => {
            navLinks.classList.remove('active');
        });
    });
    
    // Smooth Scroll
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({ behavior: 'smooth' });
                navLinks.classList.remove('active');
            }
        });
    });
</script>
</body>
</html>