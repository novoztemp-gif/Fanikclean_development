<?php
require_once __DIR__ . '/../models/User.php';

class UserController extends Controller {
    public function __construct() {
        $this->requireRole([1]);
    }

    public function index() {
        $userModel = new User();
        $users = $userModel->getAll();
        
        $db = Database::connect();
        $sites = $db->query("SELECT id, name FROM sites WHERE is_active = TRUE ORDER BY name")->fetchAll();
        $roles = $db->query("SELECT id, name FROM roles ORDER BY id")->fetchAll();

        $this->view('users/index', [
            'users' => $users,
            'sites' => $sites,
            'roles' => $roles
        ]);
    }

    public function profile() {
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $this->redirect('/users');
            return;
        }

        $userModel = new User();
        $user = $userModel->getById($id);

        if (!$user) {
            $_SESSION['error'] = "User not found";
            $this->redirect('/users');
            return;
        }

        $this->view('users/profile', ['user' => $user]);
    }

    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $name = $_POST['full_name'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $roleId = $_POST['role_id'];
            
            $passwordHash = password_hash($password, PASSWORD_BCRYPT);
            
            $guardianData = [
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'guardian_place' => $_POST['guardian_place'] ?? ''
            ];
            
            $userModel = new User();
            // Check if user exists
            if ($userModel->findByEmail($email)) {
                $_SESSION['error'] = "Email already registered";
            } else {
                $newId = $userModel->create($name, $email, $passwordHash, $roleId, $guardianData);
                if ($newId) {
                    // Persist site scope (multi-site) chosen in the create modal.
                    $userModel->saveAssignments($newId, $_POST['site_ids'] ?? []);
                    $this->logAudit('Users', "Created user: $name ($email)");
                    $_SESSION['toast'] = "User created successfully";
                }
            }
            $this->redirect('/users');
        }
    }

    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'];
            $data = [
                'full_name' => $_POST['full_name'],
                'role_id' => $_POST['role_id'],
                'status' => $_POST['status'],
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'guardian_place' => $_POST['guardian_place'] ?? ''
            ];

            $userModel = new User();
            if ($userModel->update($id, $data)) {
                // Site scope (multi-site) is saved to user_site_assignments.
                $userModel->saveAssignments($id, $_POST['site_ids'] ?? []);
                $this->logAudit('Users', "Updated user #$id: " . $data['full_name']);
                $_SESSION['toast'] = "User updated successfully";
            }
            $this->redirect('/users');
        }
    }
}
