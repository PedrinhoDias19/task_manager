session_start();
require_once "config/Database.php";
require_once "models/User.php";
require_once "models/Project.php";
require_once "models/Notification.php";
require_once "helpers/JWTHandler.php";

$database = new Database();
$db = $database->connect();

$user = new User($db);
$project = new Project($db);
$notification = new Notification($db);
$jwtHandler = new JWTHandler();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'register') {
        $user->name = $_POST['name'];
        $user->email = $_POST['email'];
        $user->password = password_hash($_POST['password'], PASSWORD_BCRYPT);
        
        if ($user->register()) {
            echo json_encode(["message" => "User registered successfully!"]);
        } else {
            echo json_encode(["message" => "Registration failed."]);
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'login') {
        $user->email = $_POST['email'];
        $user_data = $user->login();
        
        if ($user_data && password_verify($_POST['password'], $user_data['password'])) {
            $token = $jwtHandler->generateToken($user_data['id'], $user_data['email']);
            echo json_encode(["token" => $token]);
        } else {
            echo json_encode(["message" => "Invalid credentials."]);
        }
    }
    
    if (isset($_POST['action']) && $_POST['action'] === 'create_project') {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        $token = str_replace("Bearer ", "", $authHeader);
        $decoded = $jwtHandler->validateToken($token);

        if ($decoded) {
            $project->title = $_POST['title'];
            $project->description = $_POST['description'];
            $project->user_id = $decoded->user_id;
            $project->status = 'pending';
            
            if ($project->create()) {
                $notification->sendNotification($decoded->user_id, "New project created successfully!");
                echo json_encode(["message" => "Project created successfully!"]);
            } else {
                echo json_encode(["message" => "Failed to create project."]);
            }
        } else {
            echo json_encode(["message" => "Unauthorized request."]);
        }
    }
}
