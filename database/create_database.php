<?php
// Database configuration
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'UniversityCourses';

// Connect to MySQL
try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Read JSON file
    $jsonData = file_get_contents('programmes.json');
    $programmes = json_decode($jsonData, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error decoding JSON: " . json_last_error_msg());
    }
    
    // Create tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Universities (
            universityAbbr VARCHAR(10) PRIMARY KEY,
            universityName VARCHAR(255) NOT NULL
        );
        
        CREATE TABLE IF NOT EXISTS Colleges (
            collegeAbbr VARCHAR(10) PRIMARY KEY,
            collegeName VARCHAR(255) NOT NULL,
            universityAbbr VARCHAR(10),
            FOREIGN KEY (universityAbbr) REFERENCES Universities(universityAbbr)
        );
        
        CREATE TABLE IF NOT EXISTS Courses (
            courseAbbr VARCHAR(100) PRIMARY KEY,
            courseName VARCHAR(255) NOT NULL,
            collegeAbbr VARCHAR(10),
            minimum_points INT,
            grade_scale VARCHAR(255),
            FOREIGN KEY (collegeAbbr) REFERENCES Colleges(collegeAbbr)
        );
        
        CREATE TABLE IF NOT EXISTS RequiredCombinations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            courseAbbr VARCHAR(100),
            combination_short VARCHAR(10),
            combination_long VARCHAR(255),
            FOREIGN KEY (courseAbbr) REFERENCES Courses(courseAbbr)
        );
        
        CREATE TABLE IF NOT EXISTS SpecificRequirements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            courseAbbr VARCHAR(100),
            subject VARCHAR(255),
            grade VARCHAR(2),
            FOREIGN KEY (courseAbbr) REFERENCES Courses(courseAbbr)
        );
    ");
    
    // Prepare statements for faster inserts
    $universityStmt = $pdo->prepare("INSERT IGNORE INTO Universities (universityAbbr, universityName) VALUES (?, ?)");
    $collegeStmt = $pdo->prepare("INSERT IGNORE INTO Colleges (collegeAbbr, collegeName, universityAbbr) VALUES (?, ?, ?)");
    $courseStmt = $pdo->prepare("INSERT IGNORE INTO Courses (courseAbbr, courseName, collegeAbbr, minimum_points, grade_scale) VALUES (?, ?, ?, ?, ?)");
    $comboStmt = $pdo->prepare("INSERT INTO RequiredCombinations (courseAbbr, combination_short, combination_long) VALUES (?, ?, ?)");
    $reqStmt = $pdo->prepare("INSERT INTO SpecificRequirements (courseAbbr, subject, grade) VALUES (?, ?, ?)");
    
    // Process each programme
    foreach ($programmes as $programme) {
        // Insert university
        $universityStmt->execute([$programme['universityAbbr'], $programme['university']]);
        
        // Insert college
        $collegeStmt->execute([$programme['collegeAbbr'], $programme['college'], $programme['universityAbbr']]);
        
        // Insert course
        $courseStmt->execute([
            $programme['courseAbbr'],
            $programme['course'],
            $programme['collegeAbbr'],
            $programme['minimum_points'],
            $programme['grade_scale']
        ]);
        
        // Insert required combinations
        foreach ($programme['required_combinations'] as $combo) {
            $comboStmt->execute([
                $programme['courseAbbr'],
                $combo['short'],
                $combo['long']
            ]);
        }
        
        // Insert specific requirements
        foreach ($programme['specific_requirements'] as $req) {
            $reqStmt->execute([
                $programme['courseAbbr'],
                $req['subject'],
                $req['grade']
            ]);
        }
    }
    
    echo "Database successfully populated!\n";
    
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>