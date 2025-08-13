<?php
// Photographer Portfolio Backend
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Database configuration
$config = [
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'dbname' => $_ENV['DB_NAME'] ?? 'photographer_portfolio',
    'username' => $_ENV['DB_USER'] ?? 'root',
    'password' => $_ENV['DB_PASS'] ?? '',
    'charset' => 'utf8mb4'
];

// Enable error reporting for development (disable in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

try {
    $pdo = new PDO(
        "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}",
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Router
$request_uri = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($request_uri, PHP_URL_PATH);

// Routes
switch ($path) {
    case '/api/photos':
        handlePhotos($pdo, $request_method);
        break;
    case '/api/contact':
        handleContact($pdo, $request_method);
        break;
    case '/api/upload':
        handleUpload($pdo, $request_method);
        break;
    case '/api/settings':
        handleSettings($pdo, $request_method);
        break;
    case '/api/auth':
        handleAuth($pdo, $request_method);
        break;
    default:
        http_response_code(404);
        echo json_encode(['error' => 'Endpoint not found']);
        break;
}

// Photos management
function handlePhotos($pdo, $method) {
    switch ($method) {
        case 'GET':
            getPhotos($pdo);
            break;
        case 'POST':
            createPhoto($pdo);
            break;
        case 'PUT':
            updatePhoto($pdo);
            break;
        case 'DELETE':
            deletePhoto($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function getPhotos($pdo) {
    try {
        $category = $_GET['category'] ?? null;
        $limit = intval($_GET['limit'] ?? 20);
        $offset = intval($_GET['offset'] ?? 0);

        $sql = "SELECT * FROM photos WHERE 1=1";
        $params = [];

        if ($category && $category !== 'all') {
            $sql .= " AND category = :category";
            $params['category'] = $category;
        }

        $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }

        $stmt->execute();
        $photos = $stmt->fetchAll();

        // Get total count
        $countSql = "SELECT COUNT(*) FROM photos WHERE 1=1";
        if ($category && $category !== 'all') {
            $countSql .= " AND category = :category";
        }
        
        $countStmt = $pdo->prepare($countSql);
        if ($category && $category !== 'all') {
            $countStmt->bindValue(':category', $category);
        }
        $countStmt->execute();
        $total = $countStmt->fetchColumn();

        echo json_encode([
            'photos' => $photos,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to fetch photos']);
    }
}

function createPhoto($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $required_fields = ['title', 'category', 'image_url'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }

        $stmt = $pdo->prepare("
            INSERT INTO photos (title, description, category, image_url, alt_text, created_at) 
            VALUES (:title, :description, :category, :image_url, :alt_text, NOW())
        ");
        
        $stmt->execute([
            'title' => sanitizeInput($data['title']),
            'description' => sanitizeInput($data['description'] ?? ''),
            'category' => sanitizeInput($data['category']),
            'image_url' => filter_var($data['image_url'], FILTER_VALIDATE_URL),
            'alt_text' => sanitizeInput($data['alt_text'] ?? $data['title'])
        ]);

        $photo_id = $pdo->lastInsertId();
        
        // Fetch the created photo
        $stmt = $pdo->prepare("SELECT * FROM photos WHERE id = :id");
        $stmt->execute(['id' => $photo_id]);
        $photo = $stmt->fetch();

        echo json_encode(['success' => true, 'photo' => $photo]);
    } catch (Exception $e) {
        error_log("Photo creation failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create photo']);
    }
}

function deletePhoto($pdo) {
    try {
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'Photo ID is required']);
            return;
        }

        // Get photo info before deletion for cleanup
        $stmt = $pdo->prepare("SELECT image_url FROM photos WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $photo = $stmt->fetch();

        if (!$photo) {
            http_response_code(404);
            echo json_encode(['error' => 'Photo not found']);
            return;
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM photos WHERE id = :id");
        $stmt->execute(['id' => $id]);

        // Optionally delete file from filesystem
        $image_path = str_replace('/uploads/', '../uploads/', $photo['image_url']);
        if (file_exists($image_path)) {
            unlink($image_path);
        }

        echo json_encode(['success' => true, 'message' => 'Photo deleted successfully']);
    } catch (Exception $e) {
        error_log("Photo deletion failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete photo']);
    }
}

// Contact form handler
function handleContact($pdo, $method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        $required_fields = ['name', 'email', 'message'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                http_response_code(400);
                echo json_encode(['error' => "Missing required field: $field"]);
                return;
            }
        }

        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid email format']);
            return;
        }

        // Store contact message
        $stmt = $pdo->prepare("
            INSERT INTO contact_messages (name, email, phone, service, message, created_at) 
            VALUES (:name, :email, :phone, :service, :message, NOW())
        ");
        
        $stmt->execute([
            'name' => sanitizeInput($data['name']),
            'email' => filter_var($data['email'], FILTER_VALIDATE_EMAIL),
            'phone' => sanitizeInput($data['phone'] ?? ''),
            'service' => sanitizeInput($data['service'] ?? ''),
            'message' => sanitizeInput($data['message'])
        ]);

        // Send email notification (optional)
        sendContactNotification($data);

        echo json_encode(['success' => true, 'message' => 'Message sent successfully']);
    } catch (Exception $e) {
        error_log("Contact form failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send message']);
    }
}

// File upload handler
function handleUpload($pdo, $method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    try {
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            echo json_encode(['error' => 'No file uploaded or upload error']);
            return;
        }

        $file = $_FILES['photo'];
        $title = $_POST['title'] ?? '';
        $category = $_POST['category'] ?? '';
        $description = $_POST['description'] ?? '';

        // Validate file type
        $allowed_types = ['image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid file type. Only JPEG, PNG, and WebP are allowed']);
            return;
        }

        // Validate file size (max 10MB)
        if ($file['size'] > 10 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['error' => 'File too large. Maximum size is 10MB']);
            return;
        }

        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('photo_') . '.' . $extension;
        $upload_dir = '../uploads/';
        
        // Create upload directory if it doesn't exist
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $upload_path = $upload_dir . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
            http_response_code(500);
            echo json_encode(['error' => 'Failed to save uploaded file']);
            return;
        }

        // Optimize image (optional)
        optimizeImage($upload_path, $file['type']);

        // Save to database
        $stmt = $pdo->prepare("
            INSERT INTO photos (title, description, category, image_url, alt_text, created_at) 
            VALUES (:title, :description, :category, :image_url, :alt_text, NOW())
        ");
        
        $stmt->execute([
            'title' => sanitizeInput($title),
            'description' => sanitizeInput($description),
            'category' => sanitizeInput($category),
            'image_url' => "/uploads/$filename",
            'alt_text' => sanitizeInput($title)
        ]);

        $photo_id = $pdo->lastInsertId();

        echo json_encode([
            'success' => true,
            'photo_id' => $photo_id,
            'image_url' => "/uploads/$filename",
            'message' => 'Photo uploaded successfully'
        ]);
    } catch (Exception $e) {
        error_log("Upload failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Upload failed']);
    }
}

// Settings handler
function handleSettings($pdo, $method) {
    switch ($method) {
        case 'GET':
            getSettings($pdo);
            break;
        case 'PUT':
            updateSettings($pdo);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            break;
    }
}

function getSettings($pdo) {
    try {
        $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings");
        $stmt->execute();
        $settings = $stmt->fetchAll();

        $result = [];
        foreach ($settings as $setting) {
            $result[$setting['setting_key']] = $setting['setting_value'];
        }

        echo json_encode($result);
    } catch (Exception $e) {
        error_log("Get settings failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get settings']);
    }
}

function updateSettings($pdo) {
    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            return;
        }

        foreach ($data as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO settings (setting_key, setting_value, updated_at) 
                VALUES (:key, :value, NOW()) 
                ON DUPLICATE KEY UPDATE 
                setting_value = :value, updated_at = NOW()
            ");
            $stmt->execute([
                'key' => $key,
                'value' => sanitizeInput($value)
            ]);
        }

        echo json_encode(['success' => true, 'message' => 'Settings updated successfully']);
    } catch (Exception $e) {
        error_log("Update settings failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update settings']);
    }
}

// Authentication handler
function handleAuth($pdo, $method) {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        return;
    }

    try {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data || !isset($data['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Password is required']);
            return;
        }

        // In a real application, use proper password hashing
        $admin_password = 'admin123'; // Change this!
        
        if ($data['password'] === $admin_password) {
            // Generate a simple token (in production, use JWT or similar)
            $token = base64_encode(random_bytes(32));
            
            echo json_encode([
                'success' => true,
                'token' => $token,
                'message' => 'Authentication successful'
            ]);
        } else {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid password']);
        }
    } catch (Exception $e) {
        error_log("Auth failed: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Authentication failed']);
    }
}

// Utility functions
function sanitizeInput($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function optimizeImage($file_path, $mime_type) {
    // Basic image optimization
    $max_width = 1920;
    $max_height = 1080;
    $quality = 85;

    switch ($mime_type) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file_path);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file_path);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file_path);
            break;
        default:
            return;
    }

    if (!$image) return;

    $width = imagesx($image);
    $height = imagesy($image);

    // Calculate new dimensions
    if ($width > $max_width || $height > $max_height) {
        $ratio = min($max_width / $width, $max_height / $height);
        $new_width = intval($width * $ratio);
        $new_height = intval($height * $ratio);

        $new_image = imagecreatetruecolor($new_width, $new_height);
        
        // Preserve transparency for PNG and WebP
        if ($mime_type === 'image/png' || $mime_type === 'image/webp') {
            imagealphablending($new_image, false);
            imagesavealpha($new_image, true);
        }

        imagecopyresampled($new_image, $image, 0, 0, 0, 0, $new_width, $new_height, $width, $height);

        // Save optimized image
        switch ($mime_type) {
            case 'image/jpeg':
                imagejpeg($new_image, $file_path, $quality);
                break;
            case 'image/png':
                imagepng($new_image, $file_path, 9);
                break;
            case 'image/webp':
                imagewebp($new_image, $file_path, $quality);
                break;
        }

        imagedestroy($new_image);
    }

    imagedestroy($image);
}

function sendContactNotification($data) {
    // Email notification to admin
    $to = 'admin@yoursite.com'; // Change this
    $subject = 'Nova mensagem de contato - ' . $data['name'];
    $message = "
        Nome: {$data['name']}
        Email: {$data['email']}
        Telefone: " . ($data['phone'] ?? 'Não informado') . "
        Serviço: " . ($data['service'] ?? 'Não especificado') . "
        
        Mensagem:
        {$data['message']}
    ";
    
    $headers = "From: noreply@yoursite.com\r\n";
    $headers .= "Reply-To: {$data['email']}\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

    // Uncomment to send email
    // mail($to, $subject, $message, $headers);
}
?>
