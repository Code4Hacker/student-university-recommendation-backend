<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once './connection-class.php'; 

class UserUpdater {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function updateUser($data) {
        try {
            // Validate required fields
            if (empty($data['student_id'])) {
                throw new Exception("Student ID is required");
            }
            
            if (empty($data['full_name'])) {
                throw new Exception("Full name is required");
            }
            
            if (empty($data['email'])) {
                throw new Exception("Email is required");
            }
            
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format");
            }

            // Check if email already exists for another user
            $checkEmailQuery = "SELECT student_id FROM Students WHERE email = :email AND student_id != :student_id";
            $checkEmailStmt = $this->db->prepare($checkEmailQuery);
            $checkEmailStmt->execute([
                ':email' => $data['email'],
                ':student_id' => $data['student_id']
            ]);
            
            if ($checkEmailStmt->fetchColumn()) {
                throw new Exception("Email already in use by another account");
            }

            // Update query
            $query = "UPDATE Students SET 
                     full_name = :full_name,
                     email = :email
                     WHERE student_id = :student_id";
            
            $stmt = $this->db->prepare($query);
            $success = $stmt->execute([
                ':full_name' => $data['full_name'],
                ':email' => $data['email'],
                ':student_id' => $data['student_id']
            ]);

            if (!$success) {
                throw new Exception("Failed to update user");
            }

            // Get updated user data
            $userQuery = "SELECT student_id, username, email, full_name, created_at FROM Students WHERE student_id = :student_id";
            $userStmt = $this->db->prepare($userQuery);
            $userStmt->execute([':student_id' => $data['student_id']]);
            $user = $userStmt->fetch(PDO::FETCH_ASSOC);

            return json_encode([
                "success" => true,
                "user" => $user,
                "message" => "User updated successfully"
            ]);
        } catch (Exception $e) {
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
}

$updater = new UserUpdater();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        echo json_encode([
            "success" => false,
            "error" => "Invalid request data"
        ]);
        exit;
    }
    
    echo $updater->updateUser($data);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
}
?>