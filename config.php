<?php
// Database configuration for Render with freesqldatabase.com
// Get these values from your freesqldatabase.com account

// IMPORTANT: Replace these with YOUR actual credentials from freesqldatabase.com
$db_host = getenv('DB_HOST') ?: 'sql.freesqldatabase.com';
$db_user = getenv('DB_USER') ?: '';
$db_pass = getenv('DB_PASS') ?: '';
$db_name = getenv('DB_NAME') ?: '';

// If environment variables are not set, show error
if (empty($db_user) || empty($db_pass) || empty($db_name)) {
    die('Please set DB_HOST, DB_USER, DB_PASS, DB_NAME environment variables in Render');
}

// Create connection with error handling
try {
    $conn = new mysqli($db_host, $db_user, $db_pass);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Create database if not exists
    if (!$conn->select_db($db_name)) {
        $conn->query("CREATE DATABASE IF NOT EXISTS $db_name");
        $conn->select_db($db_name);
    }
    
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
    $admin_username = 'admin';
    $stmt->bind_param("ss", $admin_username, $admin_pass);
    $stmt->execute();
    $stmt->close();
    
    // Check if matches table is empty, insert sample data
    $check = $conn->query("SELECT COUNT(*) as count FROM matches");
    if ($check) {
        $row = $check->fetch_assoc();
        if ($row['count'] == 0) {
            $sample_matches = [
                ['Black Rhinos', 'Sakubva Stadium', '2026-04-20', '3:00 PM', 1, null, null],
                ['Dynamos FC', 'National Stadium', '2026-04-27', '3:30 PM', 0, null, null],
                ['Ngezi Platinum Stars', 'Sakubva Stadium', '2026-05-04', '3:00 PM', 1, null, null],
                ['Hwange FC', 'Sakubva Stadium', '2026-03-30', '3:00 PM', 1, 3, 1],
                ['Highlanders', 'Barbourfields Stadium', '2026-03-23', '3:00 PM', 0, 2, 0],
                ['CAPS United', 'National Stadium', '2026-03-16', '3:00 PM', 0, 1, 1],
                ['Manica Diamonds', 'Sakubva Stadium', '2026-03-09', '3:00 PM', 1, 2, 0]
            ];
            
            $insert_stmt = $conn->prepare("INSERT INTO matches (opponent, location, match_date, match_time, is_home, score_home, score_away) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($sample_matches as $match) {
                $insert_stmt->bind_param("sssssii", $match[0], $match[1], $match[2], $match[3], $match[4], $match[5], $match[6]);
                $insert_stmt->execute();
            }
            $insert_stmt->close();
        }
    }
    
    // Check if league standings table is empty, insert sample data
    $check2 = $conn->query("SELECT COUNT(*) as count FROM league_standings");
    if ($check2) {
        $row2 = $check2->fetch_assoc();
        if ($row2['count'] == 0) {
            $sample_league = [
                [1, 'Dynamos FC', 24, 15, 5, 4, 42, 21, 50],
                [2, 'CAPS United', 24, 14, 6, 4, 38, 19, 48],
                [3, 'Marondera Bullets', 24, 14, 4, 6, 41, 23, 46],
                [4, 'Ngezi Platinum Stars', 24, 12, 7, 5, 35, 22, 43],
                [5, 'Highlanders', 24, 11, 6, 7, 30, 25, 39],
                [6, 'Black Rhinos', 24, 9, 8, 7, 27, 26, 35],
                [7, 'Manica Diamonds', 24, 8, 7, 9, 28, 30, 31],
                [8, 'Hwange FC', 24, 6, 5, 13, 19, 36, 23]
            ];
            
            $league_stmt = $conn->prepare("INSERT INTO league_standings (position, team_name, played, wins, draws, losses, goals_for, goals_against, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            foreach ($sample_league as $team) {
                $league_stmt->bind_param("isiiiiiii", $team[0], $team[1], $team[2], $team[3], $team[4], $team[5], $team[6], $team[7], $team[8]);
                $league_stmt->execute();
            }
            $league_stmt->close();
        }
    }
    
} catch (Exception $e) {
    die("Database Error: " . $e->getMessage() . " - Please check your credentials in Render environment variables");
}

// Return connection for other files
function getDBConnection() {
    global $conn;
    return $conn;
}
?>