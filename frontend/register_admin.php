<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once LEGACY_BASE_PATH . '/config/db.php';

$errors = [];
$values = [
    'username' => '',
    'email' => '',
];

try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $adminExists = (int) $stmt->fetchColumn() > 0;
} catch (PDOException $e) {
    $adminExists = false;
    $errors[] = 'Database error. Please try again.';
}

if ($adminExists) {
    $_SESSION['login_error'] = 'Admin account already exists. Please log in.';
    header('Location: ' . LEGACY_BASE_URL . '/login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $values['username'] = trim($_POST['username'] ?? '');
    $values['email'] = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if ($values['username'] === '') {
        $errors[] = 'Username is required.';
    }

    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }

    if ($confirm === '') {
        $errors[] = 'Please confirm the password.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if ($values['email'] !== '' && !filter_var($values['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email format is invalid.';
    }

    if (!$errors) {
        $emailParam = $values['email'] === '' ? null : $values['email'];

        $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1');
        $stmt->execute([
            ':username' => $values['username'],
            ':email' => $emailParam,
        ]);

        if ($stmt->fetch()) {
            $errors[] = 'Username or email already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role) VALUES (:username, :email, :password_hash, :role)');
            $insert->execute([
                ':username' => $values['username'],
                ':email' => $emailParam,
                ':password_hash' => $hash,
                ':role' => 'admin',
            ]);

            $_SESSION['logout_success'] = 'Admin account created. Please log in.';
            header('Location: ' . LEGACY_BASE_URL . '/login');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= LEGACY_BASE_URL ?>/public/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2>Create Admin Account</h2>
            <p class="text-muted">Set up the first administrator.</p>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= LEGACY_BASE_URL ?>/register-admin" method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username" value="<?= htmlspecialchars($values['username']) ?>" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email (optional)</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($values['email']) ?>">
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100">Create Admin</button>
        </form>
        <div class="mt-3 text-center">
            <a href="<?= LEGACY_BASE_URL ?>/login">Back to Login</a>
        </div>
    </div>
</body>
</html>