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
    $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        $errors[] = 'Your session expired. Please refresh the page and try again.';
    }

    if ($username === '' || preg_match('/^[A-Za-z0-9_]{3,30}$/', $username) !== 1) {
        $errors[] = 'Username must be 3 to 30 characters using letters, numbers, or underscores.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            registerUser($username, $password);
            flash('success', 'Registration complete. You can log in now.');
            redirect('login.php');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $errors[] = 'That username is already taken.';
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
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
    <title>Registration | Student Record System</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">
    <main class="page-shell auth-layout">
        <section class="panel auth-card">
            <div class="auth-mark">R</div>
            <h1>Registration</h1>
            <p class="lead">Create a user account to access the student record system.</p>

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
                    <input type="password" name="password" autocomplete="new-password" required>
                </label>

                <label class="field">
                    <span>Confirm Password</span>
                    <input type="password" name="confirm_password" autocomplete="new-password" required>
                </label>

                <button class="button button-primary button-full" type="submit">Register</button>
            </form>

            <div class="auth-links">
                <p>Already have an account?</p>
                <a class="button button-secondary" href="login.php">Login</a>
            </div>
        </section>
    </main>
</body>
</html>
