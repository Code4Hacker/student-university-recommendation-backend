<?php
require_once "./connection-class.php";

class StudentAPI
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function registerStudent($data)
    {
        try {
            $this->db->beginTransaction();

            if (
                empty($data['username']) || empty($data['email']) ||
                empty($data['full_name']) || empty($data['password']) ||
                empty($data['subjects']) || count($data['subjects']) !== 3
            ) {
                throw new Exception("All fields are required including three subjects");
            }

            $checkQuery = "SELECT COUNT(*) FROM Students WHERE username = :username OR email = :email";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email']
            ]);
            if ($checkStmt->fetchColumn() > 0) {
                throw new Exception("Username or email already exists");
            }

            $query = "INSERT INTO Students (username, email, full_name, password) 
                     VALUES (:username, :email, :full_name, :password)";
            $stmt = $this->db->prepare($query);
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            $stmt->execute([
                ':username' => $data['username'],
                ':email' => $data['email'],
                ':full_name' => $data['full_name'],
                ':password' => $hashedPassword
            ]);

            $student_id = $this->db->lastInsertId();

            $subjectQuery = "INSERT INTO StudentSubjects (student_id, subject, grade) 
                           VALUES (:student_id, :subject, :grade)";
            $subjectStmt = $this->db->prepare($subjectQuery);

            foreach ($data['subjects'] as $subject) {
                if (empty($subject['subject']) || empty($subject['grade'])) {
                    throw new Exception("Subject and grade are required for all three entries");
                }
                $subjectStmt->execute([
                    ':student_id' => $student_id,
                    ':subject' => $subject['subject'],
                    ':grade' => $subject['grade']
                ]);
            }

            $this->db->commit();
            return json_encode([
                "success" => true,
                "student_id" => $student_id,
                "message" => "Student registered successfully"
            ]);
        } catch (Exception $e) {
            $this->db->rollBack();
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
    public function signIn($data)
    {
        try {
            if (empty($data['username']) || empty($data['password'])) {
                throw new Exception("Username and password are required");
            }

            $query = "SELECT * FROM Students WHERE username = :username";
            $stmt = $this->db->prepare($query);
            $stmt->execute([':username' => $data['username']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($student && password_verify($data['password'], $student['password'])) {
                unset($student['password']);
                $querySubjects = "SELECT subject, grade FROM StudentSubjects WHERE student_id = :student_id";
                $stmtSubjects = $this->db->prepare($querySubjects);
                $stmtSubjects->execute([':student_id' => $student['student_id']]);
                $subjects = $stmtSubjects->fetchAll(PDO::FETCH_ASSOC);

                return json_encode([
                    "success" => true,
                    "message" => "Sign in successful",
                    "student" => $student,
                    "subjects" => $subjects
                ]);
            } else {
                return json_encode([
                    "success" => false,
                    "error" => "Invalid username or password"
                ]);
            }
        } catch (Exception $e) {
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
    public function getEligibleCourses($student_id, $filter = 'default', $page = 1, $per_page = 10) {
        try {
            $offset = ($page - 1) * $per_page;
    
            $subjectQuery = "SELECT subject, grade FROM StudentSubjects WHERE student_id = :student_id";
            $subjectStmt = $this->db->prepare($subjectQuery);
            $subjectStmt->execute([':student_id' => $student_id]);
            $studentSubjects = $subjectStmt->fetchAll(PDO::FETCH_ASSOC);
    
            $countQuery = "SELECT COUNT(*) as total FROM Courses";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute();
            $totalCourses = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
            $courseQuery = "SELECT 
            c.courseAbbr,
            c.id,
            c.courseName,
            c.collegeAbbr,
            c.minimum_points,
            c.grade_scale,
            c.deleted,
            u.universityAbbr,
            u.universityName,
            GROUP_CONCAT(DISTINCT CONCAT(rc.combination_short, ':', rc.combination_long)) as combinations,
            GROUP_CONCAT(DISTINCT CONCAT(sr.subject, ':', sr.grade)) as requirements
            FROM Courses c 
            JOIN Colleges cl ON c.collegeAbbr = cl.collegeAbbr
            JOIN Universities u ON cl.universityAbbr = u.universityAbbr
            LEFT JOIN RequiredCombinations rc ON c.courseAbbr = rc.courseAbbr
            LEFT JOIN SpecificRequirements sr ON c.courseAbbr = sr.courseAbbr
            WHERE c.deleted = 0
            GROUP BY c.courseAbbr ORDER BY c.id DESC
            LIMIT :limit OFFSET :offset";
            $courseStmt = $this->db->prepare($courseQuery);
            $courseStmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
            $courseStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $courseStmt->execute();
            $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
    
            $resultCourses = [];
            foreach ($courses as $course) {
                $eligibility = $this->isStudentEligible($studentSubjects, $course);
                
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
    
                $courseData = [
                    'universityAbbr' => $course['universityAbbr'],
                    'university' => $course['universityName'],
                    'collegeAbbr' => $course['collegeAbbr'],
                    'courseAbbr' => $course['courseAbbr'],
                    'course' => $course['courseName'],
                    'required_combinations' => $combinations,
                    'minimum_points' => $course['minimum_points'],
                    'specific_requirements' => $requirements,
                    'grade_scale' => $course['grade_scale'],
                    'eligible' => $eligibility['eligible'],
                    'match_score' => $eligibility['match_score']
                ];
    
                if ($filter === 'grades' && !$eligibility['eligible']) continue;
                
                $resultCourses[] = $courseData;
            }
    
            $total_pages = ceil($totalCourses / $per_page);
            return json_encode([
                "success" => true,
                "courses" => $resultCourses,
                "pagination" => [
                    "total" => (int)$totalCourses,
                    "per_page" => (int)$per_page,
                    "current_page" => (int)$page,
                    "last_page" => $total_pages
                ]
            ]);
        } catch (Exception $e) {
            return json_encode([
                "success" => false,
                "error" => "Failed to fetch courses: " . $e->getMessage()
            ]);
        }
    }
    
    private function isStudentEligible($studentSubjects, $course) {
        $gradePoints = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1];
        $studentPoints = 0;
        $studentSubjectList = [];
        $matchScore = 0;
        
        foreach ($studentSubjects as $subject) {
            $studentPoints += $gradePoints[$subject['grade']];
            $studentSubjectList[$subject['subject']] = $subject['grade'];
        }
    
        if ($studentPoints < $course['minimum_points']) {
            return ['eligible' => false, 'match_score' => 0];
        }
    
        $allRequirementsMet = true;
        
        if ($course['requirements']) {
            foreach (explode(',', $course['requirements']) as $req) {
                [$requiredSubject, $minGrade] = explode(':', $req);
                if (!isset($studentSubjectList[$requiredSubject])) {
                    $allRequirementsMet = false;
                    continue;
                }
                
                $studentGradeValue = $gradePoints[$studentSubjectList[$requiredSubject]];
                $minGradeValue = $gradePoints[$minGrade];
                
                if ($studentGradeValue >= $minGradeValue) {
                    $matchScore += ($studentGradeValue - $minGradeValue + 1);
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

    // private function isStudentEligible($studentSubjects, $course, $filter = 'default')
    // {
    //     $gradePoints = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1];
    //     $studentPoints = 0;
    //     $studentSubjectList = [];
    //     $matchScore = 0; 

    //     foreach ($studentSubjects as $subject) {
    //         $studentPoints += $gradePoints[$subject['grade']] ?? 0;
    //         $studentSubjectList[$subject['subject']] = $subject['grade'];
    //     }

    //     if ($studentPoints < $course['minimum_points']) {
    //         return ['eligible' => false, 'match_score' => 0];
    //     }

    //     $allRequirementsMet = true;
    //     $specificRequirementsMet = 0;
    //     $totalRequirements = 0;

    //     if ($course['requirements']) {
    //         $requirements = explode(',', $course['requirements']);
    //         $totalRequirements = count($requirements);

    //         foreach ($requirements as $req) {
    //             [$subject, $minGrade] = explode(':', $req);
    //             $studentGrade = $studentSubjectList[$subject] ?? null;

    //             if ($studentGrade) {
    //                 $studentGradePoints = $gradePoints[$studentGrade] ?? 0;
    //                 $minGradePoints = $gradePoints[$minGrade] ?? 0;

    //                 if ($studentGradePoints >= $minGradePoints) {
    //                     $specificRequirementsMet++;
    //                     $matchScore += ($studentGradePoints - $minGradePoints + 1); 
    //                 } else {
    //                     $allRequirementsMet = false;
    //                 }
    //             } else {
    //                 $allRequirementsMet = false;
    //             }
    //         }
    //     }

    //     $eligible = $allRequirementsMet;

    //     if ($filter === 'strict') {
    //         $eligible = $allRequirementsMet && ($specificRequirementsMet === $totalRequirements);
    //     } elseif ($filter === 'custom') {
    //         $hasBetterGrade = false;
    //         foreach ($requirements as $req) {
    //             [$subject, $minGrade] = explode(':', $req);
    //             $studentGrade = $studentSubjectList[$subject] ?? null;
    //             if ($studentGrade && ($gradePoints[$studentGrade] > $gradePoints[$minGrade])) {
    //                 $hasBetterGrade = true;
    //                 break;
    //             }
    //         }
    //         $eligible = $allRequirementsMet && $hasBetterGrade;
    //     }

    //     return [
    //         'eligible' => $eligible,
    //         'match_score' => $matchScore
    //     ];
    // }
    public function getCustomEligibleCourses($subjectsData, $page = 1, $per_page = 10) {
        try {
            $offset = ($page - 1) * $per_page;
            
            if (count($subjectsData) !== 3) {
                throw new Exception("Exactly 3 subjects are required");
            }
            
            foreach ($subjectsData as $subject) {
                if (empty($subject['subject']) || empty($subject['grade'])) {
                    throw new Exception("All subjects must have both subject name and grade");
                }
            }
    
            $countQuery = "SELECT COUNT(*) as total FROM Courses";
            $countStmt = $this->db->prepare($countQuery);
            $countStmt->execute();
            $totalCourses = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
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
                LIMIT :limit OFFSET :offset";
            
            $courseStmt = $this->db->prepare($courseQuery);
            $courseStmt->bindValue(':limit', (int)$per_page, PDO::PARAM_INT);
            $courseStmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $courseStmt->execute();
            $courses = $courseStmt->fetchAll(PDO::FETCH_ASSOC);
    
            $eligibleCourses = [];
            foreach ($courses as $course) {
                $eligibilityResult = $this->isStudentEligible($subjectsData, $course, 'custom');
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
    
            $total_pages = ceil($totalCourses / $per_page);
            
            return json_encode([
                "success" => true,
                "courses" => $eligibleCourses,
                "pagination" => [
                    "total" => (int)$totalCourses,
                    "per_page" => (int)$per_page,
                    "current_page" => (int)$page,
                    "last_page" => $total_pages
                ]
            ]);
        } catch (Exception $e) {
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
    // private function isStudentEligible($studentSubjects, $course) {
    //     $gradePoints = ['A' => 5, 'B' => 4, 'C' => 3, 'D' => 2, 'E' => 1];
    //     $studentPoints = 0;
    //     $studentSubjectList = [];

    //     foreach ($studentSubjects as $subject) {
    //         $studentPoints += $gradePoints[$subject['grade']] ?? 0;
    //         $studentSubjectList[$subject['subject']] = $subject['grade'];
    //     }

    //     if ($studentPoints < $course['minimum_points']) {
    //         return false;
    //     }

    //     if ($course['requirements']) {
    //         $requirements = explode(',', $course['requirements']);
    //         foreach ($requirements as $req) {
    //             [$subject, $minGrade] = explode(':', $req);
    //             if (!isset($studentSubjectList[$subject]) || 
    //                 ($gradePoints[$studentSubjectList[$subject]] ?? 0) < ($gradePoints[$minGrade] ?? 0)) {
    //                 return false;
    //             }
    //         }
    //     }

    //     return true;
    // }
}

$api = new StudentAPI();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode([
            "success" => false,
            "error" => "Invalid request data"
        ]);
        exit;
    }
} elseif ($method === 'GET') {
    $data = $_GET;
} else {
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
    exit;
}
// echo password_hash("123456", PASSWORD_DEFAULT);

switch ($action) {
    case 'register':
        if ($method !== 'POST') {
            echo json_encode([
                "success" => false,
                "error" => "Register requires POST method"
            ]);
            break;
        }
        echo $api->registerStudent($data);
        break;

    case 'signin':
        if ($method !== 'POST') {
            echo json_encode([
                "success" => false,
                "error" => "Signin requires POST method"
            ]);
            break;
        }
        echo $api->signIn($data);
        break;

    case 'get_courses':
        if (!isset($data['student_id'])) {
            echo json_encode([
                "success" => false,
                "error" => "Student ID is required"
            ]);
        } else {
            $page = isset($data['page']) ? (int) $data['page'] : 1;
            $per_page = isset($data['per_page']) ? (int) $data['per_page'] : 10;
            $filter = isset($data['filter']) ? $data['filter'] : 'default';
            echo $api->getEligibleCourses($data['student_id'], $filter, $page, $per_page);
        }

        break;
    case 'get_custom_courses':
        if ($method !== 'POST') {
            echo json_encode([
                "success" => false,
                "error" => "Custom courses requires POST method"
            ]);
            break;
        }
        if (!isset($data['subjects'])) {
            echo json_encode([
                "success" => false,
                "error" => "Subjects data is required"
            ]);
            break;
        }
        $page = isset($data['page']) ? (int) $data['page'] : 1;
        $per_page = isset($data['per_page']) ? (int) $data['per_page'] : 10;
        echo $api->getCustomEligibleCourses($data['subjects'], $page, $per_page);
        break;

    default:
        echo json_encode([
            "success" => false,
            "error" => "Invalid action"
        ]);
        break;
}
?>