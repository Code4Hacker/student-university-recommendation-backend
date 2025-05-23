<?php

require_once './connection-class.php';

class CourseUpdater
{
    private $db;

    public function __construct()
    {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function updateCourse($data)
    {
        try {
            $this->db->beginTransaction();

            if (empty($data['courseAbbr']) || empty($data['courseData'])) {
                throw new Exception("Course abbreviation and data are required");
            }

            $courseAbbr = $data['courseAbbr'];
            $courseData = $data['courseData'];

            $updateQuery = "UPDATE Courses SET 
                          courseName = :courseName,
                          minimum_points = :minimumPoints
                          WHERE courseAbbr = :courseAbbr";

            $stmt = $this->db->prepare($updateQuery);
            $stmt->execute([
                ':courseName' => $courseData['course'],
                ':minimumPoints' => $courseData['minimum_points'],
                ':courseAbbr' => $courseAbbr
            ]);

            // Delete existing requirements for this course
            $deleteQuery = "DELETE FROM SpecificRequirements WHERE courseAbbr = :courseAbbr";
            $deleteStmt = $this->db->prepare($deleteQuery);
            $deleteStmt->execute([':courseAbbr' => $courseAbbr]);

            // Insert new requirements if they exist
            if (!empty($courseData['specific_requirements'])) {
                $insertQuery = "INSERT INTO SpecificRequirements 
                              (courseAbbr, subject, grade) 
                              VALUES (:courseAbbr, :subject, :grade)";
                $insertStmt = $this->db->prepare($insertQuery);

                foreach ($courseData['specific_requirements'] as $requirement) {
                    // Validate requirement fields
                    if (empty($requirement['subject']) || empty($requirement['grade'])) {
                        continue; // Skip invalid requirements
                    }

                    $insertStmt->execute([
                        ':courseAbbr' => $courseAbbr,
                        ':subject' => $requirement['subject'],
                        ':grade' => strtoupper($requirement['grade']) // Ensure uppercase grade
                    ]);
                }
            }

            $this->db->commit();

            // Return updated course data
            return json_encode([
                "success" => true,
                "message" => "Course updated successfully",
                "course" => [
                    "courseAbbr" => $courseAbbr,
                    "courseName" => $courseData['course'],
                    "minimum_points" => $courseData['minimum_points'],
                    "specific_requirements" => $courseData['specific_requirements'] ?? []
                ]
            ]);

        } catch (Exception $e) {
            $this->db->rollBack();
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
    public function deleteCourse($courseAbbr, $role)
    {
        try {
            // Verify admin role first
            if ($role !== 'ADMIN') {
                
                throw new Exception("Admin access required");
            }

            $this->db->beginTransaction();

            // Check if course exists
            $checkQuery = "SELECT courseAbbr FROM Courses WHERE courseAbbr = :courseAbbr AND deleted = 0";
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([':courseAbbr' => $courseAbbr]);

            if ($checkStmt->rowCount() === 0) {
                throw new Exception("Course not found or already deleted");
            }

            // Soft delete the course
            $updateQuery = "UPDATE Courses SET deleted = 1 WHERE courseAbbr = :courseAbbr";
            $updateStmt = $this->db->prepare($updateQuery);
            $updateStmt->execute([':courseAbbr' => $courseAbbr]);

            $this->db->commit();

            return [
                "success" => true,
                "message" => "Course deleted successfully"
            ];

        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return [
                "success" => false,
                "error" => $e->getMessage()
            ];
        }
    }

}
session_start();

// Handle the request
$updater = new CourseUpdater();

$method = $_SERVER['REQUEST_METHOD'];
switch ($method) {
    case "POST":
        $data = json_decode(file_get_contents('php://input'), true);

        if (!$data) {
            echo json_encode([
                "success" => false,
                "error" => "Invalid request data"
            ]);
            exit;
        }

        echo $updater->updateCourse($data);
        break;
    case "DELETE":
        $input = json_decode(file_get_contents('php://input'), true);
        $courseAbbr = $input['courseAbbr'] ?? $_GET['courseAbbr'] ?? null;
        $role = $input['user_role'] ?? $_GET['user_role'] ?? null;

        if (!$courseAbbr) {
            echo json_encode([
                "success" => false,
                "error" => "Course abbreviation is required"
            ]);
            exit;
        }

        echo json_encode($updater->deleteCourse($courseAbbr, $role));
        break;
    default:
        echo json_encode([
            "success" => false,
            "error" => "Method not allowed"
        ]);
        break;

}

?>