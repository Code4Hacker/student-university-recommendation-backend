<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once './connection-class.php';

class TopCourses {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getTopCourses($student_id, $limit = 6) {
        try {
            $subjectQuery = "SELECT subject, grade FROM StudentSubjects WHERE student_id = :student_id";
            $subjectStmt = $this->db->prepare($subjectQuery);
            $subjectStmt->execute([':student_id' => $student_id]);
            $studentSubjects = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($studentSubjects)) {
                throw new Exception("Student subjects not found");
            }
            $courseQuery = "
                SELECT 
                    c.courseAbbr,
                    c.courseName,
                    c.collegeAbbr,
                    c.minimum_points,
                    c.grade_scale,
                    u.universityAbbr,
                    u.universityName,
                    GROUP_CONCAT(DISTINCT CONCAT(rc.combination_short, ':', rc.combination_long)) as combinations,
                    GROUP_CONCAT(DISTINCT CONCAT(sr.subject, ':', sr.grade)) as requirements
                FROM Courses c
                JOIN Colleges cl ON c.collegeAbbr = cl.collegeAbbr
                JOIN Universities u ON cl.universityAbbr = u.universityAbbr
                LEFT JOIN RequiredCombinations rc ON c.courseAbbr = rc.courseAbbr
                LEFT JOIN SpecificRequirements sr ON c.courseAbbr = sr.courseAbbr
                GROUP BY c.courseAbbr
                ORDER BY c.courseName";
            
            $courseStmt = $this->db->prepare($courseQuery);
            $courseStmt->execute();
            $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);

            $eligibleCourses = [];
            foreach ($courses as $course) {
                $eligibilityResult = $this->isStudentEligible($studentSubjects, $course);
                if ($eligibilityResult['eligible']) {
                    $combinations = [];
                    if ($course['combinations']) {
                        foreach (explode(',', $course['combinations']) as $comb) {
                            [$short, $long] = explode(':', $comb);
                            $combinations[] = ['short' => $short, 'long' => $long];
                        }
                    }

                    $requirements = [];
                    if ($course['requirements']) {  
                        foreach (explode(',', $course['requirements']) as $req) {
                            [$subject, $grade] = explode(':', $req);
                            $requirements[] = ['subject' => $subject, 'grade' => $grade];
                        }
                    }

                    $eligibleCourses[] = [
                        'universityAbbr' => $course['universityAbbr'],
                        'university' => $course['universityName'],
                        'collegeAbbr' => $course['collegeAbbr'],
                        'courseAbbr' => $course['courseAbbr'],
                        'course' => $course['courseName'],
                        'required_combinations' => $combinations,
                        'minimum_points' => $course['minimum_points'],
                        'specific_requirements' => $requirements,
                        'grade_scale' => $course['grade_scale'],
                        'match_score' => $eligibilityResult['match_score']
                    ];
                }
            }

            // Sort by match score (higher first) and get top 6
            usort($eligibleCourses, function($a, $b) {
                return $b['match_score'] <=> $a['match_score'];
            });

            $topCourses = array_slice($eligibleCourses, 0, $limit);

            return json_encode([
                "success" => true,
                "courses" => $topCourses,
                "total" => count($eligibleCourses)
            ]);
        } catch (Exception $e) {
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }

    private function isStudentEligible($studentSubjects, $course) {
        $gradePoints = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1];
        $studentPoints = 0;
        $studentSubjectList = [];
        $matchScore = 0;
        
        foreach ($studentSubjects as $subject) {
            $studentPoints += $gradePoints[$subject['grade']] ?? 0 ;
            $studentSubjectList[$subject['subject']] = $subject['grade'];
        }

        // Check minimum points requirement
        if ($studentPoints < $course['minimum_points']) {
            return ['eligible' => false, 'match_score' => 0];
        }

        $allRequirementsMet = true;
        $specificRequirementsMet = 0;
        
        if ($course['requirements']) {
            $requirements = explode(',', $course['requirements']);
            
            foreach ($requirements as $req) {
                [$subject, $minGrade] = explode(':', $req);
                $studentGrade = $studentSubjectList[$subject] ?? null;
                
                if ($studentGrade) {
                    $studentGradePoints = $gradePoints[$studentGrade] ?? 0;
                    $minGradePoints = $gradePoints[$minGrade] ?? 0;
                    
                    if ($studentGradePoints >= $minGradePoints) {
                        $specificRequirementsMet++;
                        $matchScore += ($studentGradePoints - $minGradePoints + 1);
                    } else {
                        $allRequirementsMet = false;
                    }
                } else {
                    $allRequirementsMet = false;
                }
            }
        }

        return [
            'eligible' => $allRequirementsMet,
            'match_score' => $matchScore
        ];
    }
}

$api = new TopCourses();

$method = $_SERVER['REQUEST_METHOD'];
$data = $_GET;

if ($method === 'GET') {
    if (!isset($data['student_id'])) {
        echo json_encode([
            "success" => false,
            "error" => "Student ID is required"
        ]);
    } else {
        $limit = isset($data['limit']) ? (int)$data['limit'] : 6;
        echo $api->getTopCourses($data['student_id'], $limit);
    }
} else {
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
}
?>