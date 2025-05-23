<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type");

require_once './connection-class.php';

class CollegeFetcher {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }

    public function getColleges($universityAbbr) {
        try {
            $query = "SELECT collegeAbbr, collegeName FROM Colleges WHERE universityAbbr = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$universityAbbr]);
            
            $colleges = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return json_encode([
                "success" => true,
                "colleges" => $colleges
            ]);
        } catch (Exception $e) {
            return json_encode([
                "success" => false,
                "error" => $e->getMessage()
            ]);
        }
    }
}

$fetcher = new CollegeFetcher();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $universityAbbr = $_GET['universityAbbr'] ?? '';
    echo $fetcher->getColleges($universityAbbr);
} else {
    echo json_encode([
        "success" => false,
        "error" => "Method not allowed"
    ]);
}
?>