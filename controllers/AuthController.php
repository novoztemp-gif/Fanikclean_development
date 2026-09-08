<?php
require_once __DIR__ . '/../models/User.php';

class AuthController extends Controller {

    public function login() {
        $this->viewAuth('auth/login');
    }

    public function authenticate() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $selectedRole = (int)($_POST['role'] ?? 0);

        // Remember the chosen tab so it stays selected after a failed attempt
        if ($selectedRole) {
            $_SESSION['login_role'] = $selectedRole;
        }

        $userModel = new User();
        $user = $userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $_SESSION['error'] = "Invalid credentials";
            $this->redirect('/login');
            return;
        }

        if ($user['status'] !== 'Active') {
            $_SESSION['error'] = "This account has been " . ($user['status'] === 'Deleted' ? 'deleted' : 'suspended') . ". Contact an administrator.";
            $this->redirect('/login');
            return;
        }

        // Enforce the Admin/Manager separation: the chosen tab must match the account's role.
        if ($selectedRole && (int)$user['role_id'] !== $selectedRole) {
            $roleName = $selectedRole === 1 ? 'Admin' : 'Manager';
            $article  = $selectedRole === 1 ? 'an' : 'a';
            $_SESSION['error'] = "This account is not $article $roleName. Please pick the correct login type.";
            $this->redirect('/login');
            return;
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];

        // Load assigned site IDs for multi-site managers
        $_SESSION['assigned_site_ids'] = $userModel->getAssignedSiteIds($user['id']);

        $this->logAudit('Auth', 'Signed in');
        $this->redirect('/dashboard');
    }

    public function logout() {
        $this->logAudit('Auth', 'Signed out'); // log before the session is torn down
        session_destroy();
        $this->redirect('/login');
    }

    // Self-service password change, available to any logged-in user (Admin
    // or Manager) from their own topbar menu. Requires the current password --
    // unlike Credential Management (Admin resetting a Manager's password),
    // this never lets anyone change a password they don't already know.
    public function changePassword() {
        $this->checkAuth();
        $back = $_SERVER['HTTP_REFERER'] ?? '/dashboard';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $current = $_POST['current_password'] ?? '';
            $new = $_POST['new_password'] ?? '';
            $confirm = $_POST['new_password_confirm'] ?? '';

            $userModel = new User();
            $user = $userModel->getById($_SESSION['user_id']);

            if (!$user || !password_verify($current, $user['password_hash'])) {
                $_SESSION['error'] = "Current password is incorrect";
            } elseif (strlen($new) < 6) {
                $_SESSION['error'] = "New password must be at least 6 characters";
            } elseif ($new !== $confirm) {
                $_SESSION['error'] = "New passwords do not match";
            } else {
                $userModel->updatePassword($user['id'], password_hash($new, PASSWORD_BCRYPT));
                $this->logAudit('Auth', 'Changed own password');
                $_SESSION['toast'] = "Password updated successfully";
            }
        }
        $this->redirect($back);
    }
}
