<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once './connection-class.php';

class SummaryProvider {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getSummary($subjectsData) {
        try {
            // Validate subjects data
            if (!is_array($subjectsData)) {
                throw new Exception("Invalid subjects data format");
            }

            // Get total courses count
            $totalCoursesQuery = "SELECT COUNT(*) as total_courses FROM Courses";
            $totalCoursesStmt = $this->db->prepare($totalCoursesQuery);
            $totalCoursesStmt->execute();
            $totalCourses = $totalCoursesStmt->fetch(PDO::FETCH_ASSOC)['total_courses'];

            // Get total universities count
            $totalUnivQuery = "SELECT COUNT(*) as total_universities FROM Universities";
            $totalUnivStmt = $this->db->prepare($totalUnivQuery);
            $totalUnivStmt->execute();
            $totalUniversities = $totalUnivStmt->fetch(PDO::FETCH_ASSOC)['total_universities'];

            // Calculate eligible courses
            $eligibleCourses = 0;
            if (!empty($subjectsData)) {
                $gradePoints = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1];
                $studentSubjects = [];
                $studentPoints = 0;

                foreach ($subjectsData as $subject) {
                    if (!isset($subject['subject']) || !isset($subject['grade'])) {
                        continue;
                    }
                    $studentSubjects[$subject['subject']] = $subject['grade'];
                    $studentPoints += $gradePoints[$subject['grade']] ?? 0;
                }

                // Get all courses with requirements
                $coursesQuery = "
                    SELECT 
                        c.courseAbbr,
                        c.minimum_points,
                        GROUP_CONCAT(CONCAT(sr.subject, ':', sr.grade)) as requirements
                    FROM Courses c
                    LEFT JOIN SpecificRequirements sr ON c.courseAbbr = sr.courseAbbr
                    GROUP BY c.courseAbbr";
                
                $coursesStmt = $this->db->prepare($coursesQuery);
                $coursesStmt->execute();
                $courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($courses as $course) {
                    // Check minimum points requirement
                    if ($studentPoints < $course['minimum_points']) {
                        continue;
                    }

                    $allRequirementsMet = true;
                    
                    if ($course['requirements']) {
                        $requirements = explode(',', $course['requirements']);
                        
                        foreach ($requirements as $req) {
                            [$subject, $minGrade] = explode(':', $req);
                            $studentGrade = $studentSubjects[$subject] ?? null;
                            
                            if ($studentGrade) {
                                $studentGradePoints = $gradePoints[$studentGrade] ?? 0;
                                $minGradePoints = $gradePoints[$minGrade] ?? 0;
                                
                                if ($studentGradePoints < $minGradePoints) {
                                    $allRequirementsMet = false;
                                    break;
                                }
                            } else {
                                $allRequirementsMet = false;
                                break;
                            }
                        }
                    }

                    if ($allRequirementsMet) {
                        $eligibleCourses++;
                    }
                }
            }

            return json_encode([
                "success" => true,
                "summary" => [
                    "eligible" => $eligibleCourses,
                    "available_university" => $totalUniversities,
                    "available_courses" => $totalCourses
                ]
            ]);
        } catch (Exception $e) {
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
}

$summaryProvider = new SummaryProvider();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['subjects'])) {
        echo json_encode([
            "success" => false,
            "error" => "Subjects data is required"
        ]);
        exit;
    }
    
    echo $summaryProvider->getSummary($data['subjects']);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
}
?>