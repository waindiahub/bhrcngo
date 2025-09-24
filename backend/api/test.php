<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Complaint.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Activity.php';
require_once __DIR__ . '/../models/Gallery.php';
require_once __DIR__ . '/../models/Member.php';
require_once __DIR__ . '/../models/Donation.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$pathParts = explode('/', trim($path, '/'));

// Remove 'api' and 'test' from path parts
array_shift($pathParts); // Remove 'api'
array_shift($pathParts); // Remove 'test'

$endpoint = $pathParts[0] ?? '';

try {
    switch ($endpoint) {
        case 'database':
            testDatabaseConnection();
            break;
            
        case 'tables':
            testTableCreation();
            break;
            
        case 'sample-data':
            if ($method === 'POST') {
                insertSampleData();
            } else {
                throw new Exception('Method not allowed');
            }
            break;
            
        case 'config':
            testConfiguration();
            break;
            
        case 'directories':
            testDirectories();
            break;
            
        case 'permissions':
            testPermissions();
            break;
            
        case 'models':
            testModels();
            break;
            
        case 'validation':
            testValidation();
            break;
            
        default:
            throw new Exception('Test endpoint not found');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}

function testDatabaseConnection() {
    try {
        $database = new Database();
        $connection = $database->getConnection();
        
        if ($connection) {
            // Test a simple query
            $stmt = $connection->prepare("SELECT 1 as test");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result && $result['test'] == 1) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Database connection successful',
                    'database' => DB_NAME,
                    'host' => DB_HOST,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception('Database query test failed');
            }
        } else {
            throw new Exception('Failed to establish database connection');
        }
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function testTableCreation() {
    try {
        $database = new Database();
        $connection = $database->getConnection();
        
        $tables = [
            'users', 'complaints', 'events', 'activities', 
            'gallery', 'members', 'donations', 'newsletters'
        ];
        
        $existingTables = [];
        $missingTables = [];
        
        foreach ($tables as $table) {
            $stmt = $connection->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->rowCount() > 0) {
                $existingTables[] = $table;
            } else {
                $missingTables[] = $table;
            }
        }
        
        echo json_encode([
            'success' => count($missingTables) === 0,
            'message' => count($missingTables) === 0 ? 'All tables exist' : 'Some tables are missing',
            'existing_tables' => $existingTables,
            'missing_tables' => $missingTables,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Table test failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function insertSampleData() {
    try {
        $database = new Database();
        $connection = $database->getConnection();
        
        // Sample user
        $userModel = new User($database);
        $userData = [
            'name' => 'Test Admin',
            'email' => 'admin@bhrc.test',
            'phone' => '+919876543210',
            'password' => password_hash('admin123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'status' => 'active'
        ];
        
        // Check if user already exists
        $existingUser = $userModel->findByEmail($userData['email']);
        if (!$existingUser) {
            $userId = $userModel->create($userData);
        } else {
            $userId = $existingUser['id'];
        }
        
        // Sample complaint
        $complaintModel = new Complaint($database);
        $complaintData = [
            'complainant_name' => 'John Doe',
            'complainant_email' => 'john@example.com',
            'complainant_phone' => '+919876543211',
            'category' => 'human_rights_violation',
            'description' => 'Sample complaint for testing purposes',
            'incident_date' => '2024-01-15',
            'location' => 'Test Location',
            'status' => 'pending'
        ];
        
        $complaintId = $complaintModel->create($complaintData);
        
        // Sample event
        $eventModel = new Event($database);
        $eventData = [
            'title' => 'Sample Workshop',
            'description' => 'A sample workshop for testing',
            'type' => 'workshop',
            'date' => '2024-12-31',
            'time' => '10:00:00',
            'location' => 'Test Venue',
            'capacity' => 50,
            'status' => 'upcoming',
            'created_by' => $userId
        ];
        
        $eventId = $eventModel->create($eventData);
        
        // Sample member
        $memberModel = new Member($database);
        $memberData = [
            'name' => 'Jane Smith',
            'email' => 'jane@example.com',
            'phone' => '+919876543212',
            'aadhar' => '123456789012',
            'pan' => 'ABCDE1234F',
            'dob' => '1990-01-01',
            'address' => 'Test Address',
            'city' => 'Test City',
            'state' => 'Test State',
            'pincode' => '110001',
            'occupation' => 'Test Occupation',
            'membership_type' => 'regular',
            'status' => 'active'
        ];
        
        $memberId = $memberModel->createMembership($memberData);
        
        // Sample donation
        $donationModel = new Donation($database);
        $donationData = [
            'donor_name' => 'Robert Johnson',
            'donor_email' => 'robert@example.com',
            'donor_phone' => '+919876543213',
            'amount' => 1000.00,
            'purpose' => 'legal_aid',
            'payment_method' => 'online',
            'status' => 'completed'
        ];
        
        $donationId = $donationModel->create($donationData);
        
        echo json_encode([
            'success' => true,
            'message' => 'Sample data inserted successfully',
            'inserted_records' => [
                'user_id' => $userId,
                'complaint_id' => $complaintId,
                'event_id' => $eventId,
                'member_id' => $memberId,
                'donation_id' => $donationId
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Sample data insertion failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function testConfiguration() {
    try {
        $config = [
            'environment' => ENVIRONMENT,
            'debug' => DEBUG_MODE,
            'database' => [
                'host' => DB_HOST,
                'name' => DB_NAME,
                'charset' => DB_CHARSET
            ],
            'api' => [
                'base_url' => API_BASE_URL,
                'version' => API_VERSION
            ],
            'security' => [
                'jwt_secret_set' => !empty(JWT_SECRET),
                'encryption_key_set' => !empty(ENCRYPTION_KEY),
                'session_secure' => SESSION_SECURE
            ],
            'upload' => [
                'max_size' => MAX_UPLOAD_SIZE,
                'allowed_types' => ALLOWED_FILE_TYPES
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Configuration loaded successfully',
            'config' => $config,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Configuration test failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function testDirectories() {
    try {
        $directories = [
            UPLOAD_DIR,
            UPLOAD_DIR . '/complaints',
            UPLOAD_DIR . '/events',
            UPLOAD_DIR . '/gallery',
            UPLOAD_DIR . '/members',
            UPLOAD_DIR . '/temp'
        ];
        
        $results = [];
        $allExist = true;
        
        foreach ($directories as $dir) {
            $exists = is_dir($dir);
            $writable = $exists ? is_writable($dir) : false;
            
            $results[] = [
                'directory' => $dir,
                'exists' => $exists,
                'writable' => $writable,
                'status' => ($exists && $writable) ? 'OK' : 'ERROR'
            ];
            
            if (!$exists || !$writable) {
                $allExist = false;
            }
        }
        
        echo json_encode([
            'success' => $allExist,
            'message' => $allExist ? 'All directories exist and are writable' : 'Some directories have issues',
            'directories' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Directory test failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function testPermissions() {
    try {
        $files = [
            '../config/config.php',
            '../config/Database.php',
            '../models/User.php',
            '../models/Complaint.php',
            '../auth/Auth.php',
            'index.php'
        ];
        
        $results = [];
        $allReadable = true;
        
        foreach ($files as $file) {
            $exists = file_exists($file);
            $readable = $exists ? is_readable($file) : false;
            
            $results[] = [
                'file' => $file,
                'exists' => $exists,
                'readable' => $readable,
                'status' => ($exists && $readable) ? 'OK' : 'ERROR'
            ];
            
            if (!$exists || !$readable) {
                $allReadable = false;
            }
        }
        
        echo json_encode([
            'success' => $allReadable,
            'message' => $allReadable ? 'All files have correct permissions' : 'Some files have permission issues',
            'files' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Permission test failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function testModels() {
    try {
        $database = new Database();
        
        $models = [
            'User' => new User($database),
            'Complaint' => new Complaint($database),
            'Event' => new Event($database),
            'Activity' => new Activity($database),
            'Gallery' => new Gallery($database),
            'Member' => new Member($database),
            'Donation' => new Donation($database)
        ];
        
        $results = [];
        $allWorking = true;
        
        foreach ($models as $name => $model) {
            try {
                // Test basic functionality
                $testResult = method_exists($model, 'getAll') ? 'OK' : 'Missing getAll method';
                $results[] = [
                    'model' => $name,
                    'status' => $testResult,
                    'methods' => get_class_methods($model)
                ];
            } catch (Exception $e) {
                $results[] = [
                    'model' => $name,
                    'status' => 'ERROR: ' . $e->getMessage()
                ];
                $allWorking = false;
            }
        }
        
        echo json_encode([
            'success' => $allWorking,
            'message' => $allWorking ? 'All models loaded successfully' : 'Some models have issues',
            'models' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Model test failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function testValidation() {
    try {
        $validationTests = [
            'email' => [
                'valid' => ['test@example.com', 'user.name@domain.co.in'],
                'invalid' => ['invalid-email', 'test@', '@domain.com']
            ],
            'phone' => [
                'valid' => ['9876543210', '+919876543210', '09876543210'],
                'invalid' => ['123', '12345678901234', 'abcdefghij']
            ],
            'aadhar' => [
                'valid' => ['123456789012', '987654321098'],
                'invalid' => ['12345', '1234567890123', 'abcd56789012']
            ],
            'pan' => [
                'valid' => ['ABCDE1234F', 'XYZPQ5678R'],
                'invalid' => ['ABC123', 'ABCDE12345', '1234567890']
            ]
        ];
        
        $results = [];
        
        foreach ($validationTests as $type => $tests) {
            $results[$type] = [
                'valid_passed' => 0,
                'valid_failed' => 0,
                'invalid_passed' => 0,
                'invalid_failed' => 0
            ];
            
            // Test valid inputs
            foreach ($tests['valid'] as $input) {
                $isValid = validateInput($input, $type);
                if ($isValid) {
                    $results[$type]['valid_passed']++;
                } else {
                    $results[$type]['valid_failed']++;
                }
            }
            
            // Test invalid inputs
            foreach ($tests['invalid'] as $input) {
                $isValid = validateInput($input, $type);
                if (!$isValid) {
                    $results[$type]['invalid_passed']++;
                } else {
                    $results[$type]['invalid_failed']++;
                }
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Validation tests completed',
            'results' => $results,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Validation test failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

function validateInput($input, $type) {
    switch ($type) {
        case 'email':
            return filter_var($input, FILTER_VALIDATE_EMAIL) !== false;
            
        case 'phone':
            $cleaned = preg_replace('/[^0-9]/', '', $input);
            return strlen($cleaned) === 10 && preg_match('/^[6-9]/', $cleaned);
            
        case 'aadhar':
            $cleaned = preg_replace('/[^0-9]/', '', $input);
            return strlen($cleaned) === 12 && ctype_digit($cleaned);
            
        case 'pan':
            return preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/', strtoupper($input));
            
        default:
            return false;
    }
}
?>