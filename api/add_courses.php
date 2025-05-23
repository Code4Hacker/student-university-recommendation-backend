<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Max-Age: 86400"); 


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


require_once './connection-class.php';

class CourseAdder {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function addCourse($data) {
        try {
            if (empty($data['courseAbbr'])) {
                throw new Exception("Course abbreviation is required");
            }

            
            if (!isset($data['user_role']) || $data['user_role'] !== 'ADMIN') {
                throw new Exception("Admin access required");
            }
            if (empty($data['courseData'])) {
                throw new Exception("Course data is required");
            }

            $requiredCourseFields = ['course', 'collegeAbbr', 'minimum_points', 'universityAbbr'];
            foreach ($requiredCourseFields as $field) {
                if (empty($data['courseData'][$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $this->db->beginTransaction();

            $checkCollege = $this->db->prepare("SELECT collegeAbbr FROM Colleges WHERE collegeAbbr = ?");
            $checkCollege->execute([$data['courseData']['collegeAbbr']]);
            if ($checkCollege->rowCount() === 0) {
                throw new Exception("College does not exist");
            }

            $checkUniversity = $this->db->prepare("SELECT universityAbbr FROM Universities WHERE universityAbbr = ?");
            $checkUniversity->execute([$data['courseData']['universityAbbr']]);
            if ($checkUniversity->rowCount() === 0) {
                throw new Exception("University does not exist");
            }

            $checkCourse = $this->db->prepare("SELECT courseAbbr FROM Courses WHERE courseAbbr = ?");
            $checkCourse->execute([$data['courseAbbr']]);
            if ($checkCourse->rowCount() > 0) {
                throw new Exception("Course abbreviation already exists");
            }

            $insertCourse = $this->db->prepare("
                INSERT INTO Courses (courseAbbr, courseName, collegeAbbr, minimum_points, grade_scale) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $insertSuccess = $insertCourse->execute([
                $data['courseAbbr'],
                $data['courseData']['course'],
                $data['courseData']['collegeAbbr'],
                $data['courseData']['minimum_points'],
                'A=5, B=4, C=3, D=2, E=1'
            ]);

            if (!$insertSuccess) {
                throw new Exception("Failed to insert course");
            }

            if (!empty($data['courseData']['specific_requirements'])) {
                $insertReqs = $this->db->prepare("
                    INSERT INTO SpecificRequirements (courseAbbr, subject, grade) 
                    VALUES (?, ?, ?)
                ");
                
                foreach ($data['courseData']['specific_requirements'] as $req) {
                    if (!empty($req['subject'])) {
                        $reqSuccess = $insertReqs->execute([
                            $data['courseAbbr'],
                            $req['subject'],
                            $req['grade']
                        ]);
                        
                        if (!$reqSuccess) {
                            throw new Exception("Failed to insert requirement: " . $req['subject']);
                        }
                    }
                }
            }

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Course added successfully",
                "courseAbbr" => $data['courseAbbr']
            ];

        } catch (PDOException $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "error" => "Database error: " . $e->getMessage(),
                "code" => $e->getCode()
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Invalid JSON input");
    }
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception("Method not allowed");
    }

    $adder = new CourseAdder();
    $result = $adder->addCourse($input);
    
    echo json_encode($result);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
?>