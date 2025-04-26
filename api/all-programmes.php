<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = '';
$dbName = 'UniversityCourses';

try {
    $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get pagination parameters
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $perPage = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 6;
    $offset = ($page - 1) * $perPage;

    // Count total records
    $countStmt = $pdo->query("SELECT COUNT(*) FROM Courses");
    $total = $countStmt->fetchColumn();

    // Get paginated data
    $stmt = $pdo->prepare("
        SELECT 
            u.universityAbbr, u.universityName as university,
            c.collegeAbbr, c.collegeName as college,
            co.courseAbbr, co.courseName as course, co.minimum_points, co.grade_scale
        FROM Courses co
        JOIN Colleges c ON co.collegeAbbr = c.collegeAbbr
        JOIN Universities u ON c.universityAbbr = u.universityAbbr
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $programmes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($programmes as &$programme) {
        $comboStmt = $pdo->prepare("
            SELECT combination_short as short, combination_long as `long`
            FROM RequiredCombinations
            WHERE courseAbbr = :courseAbbr
        ");
        $comboStmt->execute([':courseAbbr' => $programme['courseAbbr']]);
        $programme['required_combinations'] = $comboStmt->fetchAll(PDO::FETCH_ASSOC);

        $reqStmt = $pdo->prepare("
            SELECT subject, grade
            FROM SpecificRequirements
            WHERE courseAbbr = :courseAbbr
        ");
        $reqStmt->execute([':courseAbbr' => $programme['courseAbbr']]);
        $programme['specific_requirements'] = $reqStmt->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        'data' => $programmes,
        'pagination' => [
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => ceil($total / $perPage)
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>