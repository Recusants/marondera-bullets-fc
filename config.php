<?php
// Database configuration - update these for your hosting
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: 'bullets_fc';

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

// Insert default admin if not exists
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
?>