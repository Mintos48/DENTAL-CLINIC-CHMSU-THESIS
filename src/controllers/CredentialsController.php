<?php
/**
 * Credentials Controller - Manage dentist and clinic credentials
 */
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/constants.php';
require_once dirname(__DIR__) . '/config/session.php';

class CredentialsController {
    private $db;
    private $uploadDir;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->uploadDir = dirname(dirname(__DIR__)) . '/uploads/credentials/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }
    }

    /**
     * Get credentials for current dentist and branch
     */
    public function getCredentials() {
        try {
            $dentist_id = getSessionUserId();
            $branch_id = getSessionBranchId();

            // Get dentist credentials
            $dentistSql = "SELECT * FROM dentist_credentials WHERE dentist_id = ?";
            $stmt = $this->db->prepare($dentistSql);
            $stmt->bind_param('i', $dentist_id);
            $stmt->execute();
            $dentist_credentials = $stmt->get_result()->fetch_assoc();

            // Get clinic credentials
            $clinicSql = "SELECT * FROM clinic_credentials WHERE branch_id = ?";
            $stmt2 = $this->db->prepare($clinicSql);
            $stmt2->bind_param('i', $branch_id);
            $stmt2->execute();
            $clinic_credentials = $stmt2->get_result()->fetch_assoc();

            return [
                'success' => true,
                'dentist_credentials' => $dentist_credentials,
                'clinic_credentials' => $clinic_credentials
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Failed to load credentials: ' . $e->getMessage()];
        }
    }

    /**
     * Save dentist credentials with file upload
     */
    public function saveDentistCredentials() {
        try {
            $dentist_id = getSessionUserId();
            $role = getSessionRole();

            if ($role !== ROLE_DENTIST) {
                return ['success' => false, 'message' => 'Only dentists can update personal credentials'];
            }

            // Get POST data with defaults
            $license_number = isset($_POST['license_number']) ? $_POST['license_number'] : '';
            $specialization = isset($_POST['specialization']) ? $_POST['specialization'] : '';
            $experience_years = isset($_POST['experience_years']) ? intval($_POST['experience_years']) : 0;
            $education = isset($_POST['education']) ? $_POST['education'] : '';
            $professional_bio = isset($_POST['professional_bio']) ? $_POST['professional_bio'] : '';

            // Handle file upload
            $license_file = null;
            if (isset($_FILES['license_file']) && $_FILES['license_file']['error'] === UPLOAD_ERR_OK) {
                $license_file = $this->handleFileUpload($_FILES['license_file'], 'license_');
                if (!$license_file) {
                    return ['success' => false, 'message' => 'Failed to upload license file'];
                }
            }

            // Check if credentials exist
            $checkSql = "SELECT id FROM dentist_credentials WHERE dentist_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bind_param('i', $dentist_id);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->fetch_assoc();

            if ($exists) {
                // Update existing credentials
                if ($license_file) {
                    $sql = "UPDATE dentist_credentials SET 
                            license_number = ?, specialization = ?, experience_years = ?, 
                            education = ?, professional_bio = ?, license_file = ? 
                            WHERE dentist_id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('ssisssi',
                        $license_number,
                        $specialization,
                        $experience_years,
                        $education,
                        $professional_bio,
                        $license_file,
                        $dentist_id
                    );
                } else {
                    $sql = "UPDATE dentist_credentials SET 
                            license_number = ?, specialization = ?, experience_years = ?, 
                            education = ?, professional_bio = ? 
                            WHERE dentist_id = ?";
                    $stmt = $this->db->prepare($sql);
                    $stmt->bind_param('ssissi',
                        $license_number,
                        $specialization,
                        $experience_years,
                        $education,
                        $professional_bio,
                        $dentist_id
                    );
                }
            } else {
                // Insert new credentials
                $sql = "INSERT INTO dentist_credentials (dentist_id, license_number, specialization, 
                        experience_years, education, professional_bio, license_file) 
                        VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ississs',
                    $dentist_id,
                    $license_number,
                    $specialization,
                    $experience_years,
                    $education,
                    $professional_bio,
                    $license_file
                );
            }

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Personal credentials saved successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to save credentials'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Save clinic credentials with multiple file uploads
     */
    public function saveClinicCredentials() {
        try {
            $branch_id = getSessionBranchId();
            $role = getSessionRole();

            if (!in_array($role, [ROLE_DENTIST, ROLE_ADMIN])) {
                return ['success' => false, 'message' => 'Unauthorized'];
            }

            // Get POST data with defaults
            $clinic_license = isset($_POST['clinic_license']) ? $_POST['clinic_license'] : '';
            $business_permit = isset($_POST['business_permit']) ? $_POST['business_permit'] : '';
            $accreditations = isset($_POST['accreditations']) ? $_POST['accreditations'] : '';
            $established_year = isset($_POST['established_year']) ? intval($_POST['established_year']) : null;
            $services_offered = isset($_POST['services_offered']) ? $_POST['services_offered'] : '';

            // Handle multiple file uploads
            $clinic_photos = [];
            if (isset($_FILES['clinic_photos']) && is_array($_FILES['clinic_photos']['name'])) {
                for ($i = 0; $i < count($_FILES['clinic_photos']['name']); $i++) {
                    if ($_FILES['clinic_photos']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['clinic_photos']['name'][$i],
                            'type' => $_FILES['clinic_photos']['type'][$i],
                            'tmp_name' => $_FILES['clinic_photos']['tmp_name'][$i],
                            'error' => $_FILES['clinic_photos']['error'][$i],
                            'size' => $_FILES['clinic_photos']['size'][$i]
                        ];
                        $uploaded = $this->handleFileUpload($file, 'photo_');
                        if ($uploaded) {
                            $clinic_photos[] = $uploaded;
                        }
                    }
                }
            }

            $certifications = [];
            if (isset($_FILES['clinic_certifications']) && is_array($_FILES['clinic_certifications']['name'])) {
                for ($i = 0; $i < count($_FILES['clinic_certifications']['name']); $i++) {
                    if ($_FILES['clinic_certifications']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['clinic_certifications']['name'][$i],
                            'type' => $_FILES['clinic_certifications']['type'][$i],
                            'tmp_name' => $_FILES['clinic_certifications']['tmp_name'][$i],
                            'error' => $_FILES['clinic_certifications']['error'][$i],
                            'size' => $_FILES['clinic_certifications']['size'][$i]
                        ];
                        $uploaded = $this->handleFileUpload($file, 'cert_');
                        if ($uploaded) {
                            $certifications[] = $uploaded;
                        }
                    }
                }
            }

            // Check if credentials exist
            $checkSql = "SELECT id, clinic_photos, certifications FROM clinic_credentials WHERE branch_id = ?";
            $checkStmt = $this->db->prepare($checkSql);
            $checkStmt->bind_param('i', $branch_id);
            $checkStmt->execute();
            $exists = $checkStmt->get_result()->fetch_assoc();

            // Merge with existing files
            if ($exists) {
                if ($exists['clinic_photos']) {
                    $existing_photos = json_decode($exists['clinic_photos'], true);
                    if (is_array($existing_photos)) {
                        $clinic_photos = array_merge($existing_photos, $clinic_photos);
                    }
                }
                if ($exists['certifications']) {
                    $existing_certs = json_decode($exists['certifications'], true);
                    if (is_array($existing_certs)) {
                        $certifications = array_merge($existing_certs, $certifications);
                    }
                }
            }

            $photos_json = json_encode($clinic_photos);
            $certs_json = json_encode($certifications);

            if ($exists) {
                // Update existing credentials
                $sql = "UPDATE clinic_credentials SET 
                        clinic_license = ?, business_permit = ?, accreditations = ?, 
                        established_year = ?, services_offered = ?, clinic_photos = ?, certifications = ? 
                        WHERE branch_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('ssssissi',
                    $clinic_license,
                    $business_permit,
                    $accreditations,
                    $established_year,
                    $services_offered,
                    $photos_json,
                    $certs_json,
                    $branch_id
                );
            } else {
                // Insert new credentials
                $sql = "INSERT INTO clinic_credentials (branch_id, clinic_license, business_permit, 
                        accreditations, established_year, services_offered, clinic_photos, certifications) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('isssisss',
                    $branch_id,
                    $clinic_license,
                    $business_permit,
                    $accreditations,
                    $established_year,
                    $services_offered,
                    $photos_json,
                    $certs_json
                );
            }

            if ($stmt->execute()) {
                return ['success' => true, 'message' => 'Clinic credentials saved successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to save credentials'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Handle individual file upload
     */
    private function handleFileUpload($file, $prefix = '') {
        try {
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'application/pdf'];
            
            if (!in_array($file['type'], $allowed_types)) {
                return null;
            }

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = $prefix . uniqid() . '.' . $extension;
            $filepath = $this->uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $filepath)) {
                // Return relative path for URL
                return '../../uploads/credentials/' . $filename;
            }

            return null;
        } catch (Exception $e) {
            error_log('File upload error: ' . $e->getMessage());
            return null;
        }
    }
}

// Handle API requests
if (isset($_GET['action'])) {
    $controller = new CredentialsController();
    $action = $_GET['action'];
    
    header('Content-Type: application/json');
    
    switch ($action) {
        case 'getCredentials':
            echo json_encode($controller->getCredentials());
            break;
        case 'saveDentistCredentials':
            echo json_encode($controller->saveDentistCredentials());
            break;
        case 'saveClinicCredentials':
            echo json_encode($controller->saveClinicCredentials());
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
}
