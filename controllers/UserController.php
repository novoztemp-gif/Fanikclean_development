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
            'pageTitle' => 'User Management',
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

        $this->view('users/profile', ['pageTitle' => 'User Profile', 'user' => $user]);
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
            $email = trim($_POST['email'] ?? '');
            $userModel = new User();

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $_SESSION['error'] = "Enter a valid email address";
                $this->redirect('/users');
                return;
            }
            if ($userModel->emailTakenByOther($email, $id)) {
                $_SESSION['error'] = "That email is already used by another account";
                $this->redirect('/users');
                return;
            }

            $data = [
                'full_name' => $_POST['full_name'],
                'email' => $email,
                'role_id' => $_POST['role_id'],
                'guardian_name' => $_POST['guardian_name'] ?? '',
                'guardian_phone' => $_POST['guardian_phone'] ?? '',
                'guardian_place' => $_POST['guardian_place'] ?? ''
            ];

            if ($userModel->update($id, $data)) {
                // Site scope (multi-site) is saved to user_site_assignments.
                $userModel->saveAssignments($id, $_POST['site_ids'] ?? []);
                $this->logAudit('Users', "Updated user #$id: " . $data['full_name']);
                $_SESSION['toast'] = "User updated successfully";
            }
            $this->redirect('/users');
        }
    }

    // Reversible: blocks login, hides from manager/assignment selectors. History untouched.
    public function suspend() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $userModel = new User();

            if ($id === (int)($_SESSION['user_id'] ?? 0)) {
                $_SESSION['error'] = "You can't suspend your own account.";
            } elseif ($this->wouldRemoveLastAdmin($userModel, $id)) {
                $_SESSION['error'] = "Can't suspend the last remaining Admin.";
            } elseif ($userModel->suspend($id)) {
                $this->logAudit('Users', "Suspended user #$id");
                $_SESSION['toast'] = "User suspended";
            }
            $this->redirect('/users');
        }
    }

    // Undoes a suspend.
    public function reactivate() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $userModel = new User();
            if ($userModel->reactivate($id)) {
                $this->logAudit('Users', "Reactivated user #$id");
                $_SESSION['toast'] = "User reactivated";
            }
            $this->redirect('/users');
        }
    }

    // Permanent. Only allowed once a user is already suspended. Never removes
    // the row or any related data -- see User::softDelete().
    public function delete() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $userModel = new User();

            if ($id === (int)($_SESSION['user_id'] ?? 0)) {
                $_SESSION['error'] = "You can't delete your own account.";
            } elseif ($userModel->softDelete($id)) {
                $this->logAudit('Users', "Deleted user #$id");
                $_SESSION['toast'] = "User deleted";
            } else {
                $_SESSION['error'] = "User must be suspended before it can be deleted.";
            }
            $this->redirect('/users');
        }
    }

    // Sets a user's login password directly. Whatever is entered here becomes
    // their password immediately -- there is no separate "temporary password"
    // step, no email flow, nothing else to configure.
    public function resetPassword() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = (int)($_POST['id'] ?? 0);
            $password = $_POST['password'] ?? '';
            $confirm = $_POST['password_confirm'] ?? '';

            $userModel = new User();
            $target = $userModel->getById($id);

            if (!$target) {
                $_SESSION['error'] = "User not found";
            } elseif ((int)$target['role_id'] === 1) {
                // Credential Management is scoped to Managers only. An Admin's
                // password -- including your own -- can't be reset from here.
                $_SESSION['error'] = "Admin passwords can't be changed here.";
            } elseif (strlen($password) < 6) {
                $_SESSION['error'] = "Password must be at least 6 characters";
            } elseif ($password !== $confirm) {
                $_SESSION['error'] = "Passwords do not match";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $userModel->updatePassword($id, $hash);
                $this->logAudit('Users', "Reset password for " . $target['full_name'] . " (#$id)");
                $_SESSION['toast'] = "Password updated for " . $target['full_name'];
            }
            $this->redirect('/users');
        }
    }

    // An Admin can only be suspended/deleted while at least one other Active Admin remains.
    private function wouldRemoveLastAdmin(User $userModel, $id) {
        $target = $userModel->getById($id);
        if (!$target || (int)$target['role_id'] !== 1 || $target['status'] !== 'Active') {
            return false;
        }
        return $userModel->countActiveAdmins() <= 1;
    }
}
