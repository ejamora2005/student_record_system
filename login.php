<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$errors = [];
$username = '';
$flash = getFlash();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }

    if ($username === '' || $password === '') {
        $errors[] = 'Username and password are required.';
    }

    if ($errors === []) {
        try {
            $user = authenticateUser($username, $password);

            if ($user !== null) {
                loginUser($user);
                flash('success', 'Welcome back. You can manage student records now.');
                redirect('index.php');
            }

            $errors[] = 'Invalid username or password.';
        } catch (RuntimeException $exception) {
            $errors[] = $exception->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Student Record System</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">
    <main class="page-shell auth-layout">
        <section class="panel auth-card">
            <div class="auth-mark">U</div>
            <h1>Login</h1>
            <p class="lead">Sign in with your registered account.</p>

            <?php if ($flash !== null): ?>
                <div class="flash flash-<?= escape($flash['type']) ?>">
                    <?= escape($flash['message']) ?>
                </div>
            <?php endif; ?>

            <?php if ($errors !== []): ?>
                <div class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <p><?= escape($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="post" class="student-form">
                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">

                <label class="field">
                    <span>Username</span>
                    <input type="text" name="username" value="<?= escape($username) ?>" autocomplete="username" required>
                </label>

                <label class="field">
                    <span>Password</span>
                    <input type="password" name="password" autocomplete="current-password" required>
                </label>

                <button class="button button-primary button-full" type="submit">Login</button>
            </form>

            <div class="auth-links">
                <p>Need an account?</p>
                <a class="button button-secondary" href="register.php">Registration</a>
            </div>
        </section>
    </main>
</body>
</html>
