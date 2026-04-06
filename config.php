<?php
// Database configuration for Render with external MySQL
// You'll need to use a free MySQL hosting service like:
// - Aiven (free tier)
// - FreeMySQLDatabase.com
// - Db4Free.net

// Option 1: Using environment variables (set in Render dashboard)
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'bullets_fc';

// Option 2: For FreeMySQLDatabase.com (example - replace with your actual credentials)
// $db_host = 'sql.freedb.tech';
// $db_user = 'freedb_your_username';
// $db_pass = 'your_password';
// $db_name = 'freedb_bullets_fc';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['error' => "Connection failed: " . $conn->connect_error]));
}

// Create database if not exists
$conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
$conn->select_db($db_name);

// Create users table
$conn->query("
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create matches table
$conn->query("
CREATE TABLE IF NOT EXISTS matches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opponent VARCHAR(100) NOT NULL,
    location VARCHAR(100),
    match_date DATE NOT NULL,
    match_time VARCHAR(20),
    is_home BOOLEAN DEFAULT TRUE,
    score_home INT NULL,
    score_away INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Create league standings table
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
    points INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default admin if not exists
$admin_pass = password_hash('admin123', PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT IGNORE INTO users (username, password) VALUES (?, ?)");
$stmt->bind_param("ss", $username, $admin_pass);
$username = 'admin';
$stmt->execute();
$stmt->close();

// Check if matches table is empty, insert sample data
$check = $conn->query("SELECT COUNT(*) as count FROM matches");
$row = $check->fetch_assoc();
if ($row['count'] == 0) {
    $sample_matches = [
        ['Black Rhinos', 'Sakubva Stadium', '2026-04-12', '3:00 PM', 1, null, null],
        ['Dynamos FC', 'National Stadium', '2026-04-19', '3:30 PM', 0, null, null],
        ['Ngezi Platinum Stars', 'Sakubva Stadium', '2026-04-27', '3:00 PM', 1, null, null],
        ['Hwange FC', 'Sakubva Stadium', '2026-03-29', '3:00 PM', 1, 3, 1],
        ['Highlanders', 'Barbourfields Stadium', '2026-03-22', '3:00 PM', 0, 2, 0],
        ['CAPS United', 'National Stadium', '2026-03-15', '3:00 PM', 0, 1, 1],
        ['Manica Diamonds', 'Sakubva Stadium', '2026-03-08', '3:00 PM', 1, 2, 0]
    ];
    
    $insert_stmt = $conn->prepare("INSERT INTO matches (opponent, location, match_date, match_time, is_home, score_home, score_away) VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($sample_matches as $match) {
        $insert_stmt->bind_param("sssssii", $match[0], $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
        $insert_stmt->execute();
    }
    $insert_stmt->close();
}

// Check if league standings table is empty, insert sample data
$check2 = $conn->query("SELECT COUNT(*) as count FROM league_standings");
$row2 = $check2->fetch_assoc();
if ($row2['count'] == 0) {
    $sample_league = [
        [1, 'Dynamos FC', 26, 16, 6, 4, 45, 22, 54],
        [2, 'CAPS United', 26, 15, 6, 5, 41, 20, 51],
        [3, 'Marondera Bullets', 26, 15, 4, 7, 44, 24, 49],
        [4, 'Ngezi Platinum Stars', 26, 13, 7, 6, 38, 24, 46],
        [5, 'Highlanders', 26, 12, 6, 8, 33, 27, 42],
        [6, 'Black Rhinos', 26, 10, 8, 8, 30, 28, 38],
        [7, 'Manica Diamonds', 26, 9, 7, 10, 31, 33, 34],
        [8, 'Hwange FC', 26, 7, 5, 14, 22, 39, 26]
    ];
    
    $league_stmt = $conn->prepare("INSERT INTO league_standings (position, team_name, played, wins, draws, losses, goals_for, goals_against, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sample_league as $team) {
        $league_stmt->bind_param("isiiiiiii", $team[0], $team[1], $team[2], $team[3], $team[4], $team[5], $team[6], $team[7], $team[8]);
        $league_stmt->execute();
    }
    $league_stmt->close();
}

// Return connection for other files
function getDBConnection() {
    global $conn;
    return $conn;
}
?>