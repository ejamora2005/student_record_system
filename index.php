<?php
declare(strict_types=1);

require __DIR__ . '/config/auth.php';

const STUDENT_NUMBER_PATTERN = '/^BN-\d{7}-\d$/';
const MAX_PROFILE_IMAGE_SIZE = 2097152;

function studentPhotoDirectory(): string
{
    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'students';
}

function ensureStudentPhotoDirectory(): void
{
    $directory = studentPhotoDirectory();

    if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
        throw new RuntimeException('Unable to create the student upload folder.');
    }
}

function studentPhotoUrl(?string $filename): ?string
{
    if ($filename === null || $filename === '') {
        return null;
    }

    return 'uploads/students/' . rawurlencode($filename);
}

function buildStudentPhotoFilename(string $studentNumber, string $extension): string
{
    return strtoupper($studentNumber) . '.' . strtolower($extension);
}

function inspectUploadedPhoto(mixed $file): array
{
    if (!is_array($file) || !isset($file['error'])) {
        return [
            'has_upload' => false,
            'tmp_name' => null,
            'extension' => null,
            'error' => null,
        ];
    }

    $errorCode = (int) $file['error'];

    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return [
            'has_upload' => false,
            'tmp_name' => null,
            'extension' => null,
            'error' => null,
        ];
    }

    if ($errorCode !== UPLOAD_ERR_OK) {
        return [
            'has_upload' => false,
            'tmp_name' => null,
            'extension' => null,
            'error' => 'Profile picture upload failed. Please try again.',
        ];
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $fileSize = (int) ($file['size'] ?? 0);

    if ($tmpName === '' || !is_uploaded_file($tmpName)) {
        return [
            'has_upload' => false,
            'tmp_name' => null,
            'extension' => null,
            'error' => 'The uploaded file could not be verified.',
        ];
    }

    if ($fileSize > MAX_PROFILE_IMAGE_SIZE) {
        return [
            'has_upload' => false,
            'tmp_name' => null,
            'extension' => null,
            'error' => 'Profile picture must be 2MB or smaller.',
        ];
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    $fileInfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $fileInfo->file($tmpName);

    if (!isset($allowedMimeTypes[$mimeType])) {
        return [
            'has_upload' => false,
            'tmp_name' => null,
            'extension' => null,
            'error' => 'Only JPG, PNG, and WEBP images are allowed.',
        ];
    }

    return [
        'has_upload' => true,
        'tmp_name' => $tmpName,
        'extension' => $allowedMimeTypes[$mimeType],
        'error' => null,
    ];
}

function moveStudentPhoto(string $tmpName, string $targetFilename): bool
{
    ensureStudentPhotoDirectory();

    $destination = studentPhotoDirectory() . DIRECTORY_SEPARATOR . $targetFilename;

    if (is_file($destination) && !unlink($destination)) {
        return false;
    }

    return move_uploaded_file($tmpName, $destination);
}

function renameStudentPhoto(?string $currentFilename, string $targetFilename): bool
{
    if ($currentFilename === null || $currentFilename === '' || $currentFilename === $targetFilename) {
        return true;
    }

    $currentPath = studentPhotoDirectory() . DIRECTORY_SEPARATOR . $currentFilename;

    if (!is_file($currentPath)) {
        return true;
    }

    ensureStudentPhotoDirectory();
    $targetPath = studentPhotoDirectory() . DIRECTORY_SEPARATOR . $targetFilename;

    if (is_file($targetPath) && !unlink($targetPath)) {
        return false;
    }

    return rename($currentPath, $targetPath);
}

function deleteStudentPhoto(?string $filename): void
{
    if ($filename === null || $filename === '') {
        return;
    }

    $path = studentPhotoDirectory() . DIRECTORY_SEPARATOR . $filename;

    if (is_file($path)) {
        @unlink($path);
    }
}

function studentInitial(string $fullName): string
{
    $fullName = trim($fullName);

    if ($fullName === '') {
        return '?';
    }

    return strtoupper(substr($fullName, 0, 1));
}

function iconSvg(string $name): string
{
    return match ($name) {
        'plus' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>',
        'edit' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 1 1 3 3L7 19l-4 1 1-4 12.5-12.5Z"/></svg>',
        'trash' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/></svg>',
        'logout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>',
        'camera' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 8h3l2-3h6l2 3h3a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-8a2 2 0 0 1 2-2Z"/><circle cx="12" cy="13" r="4"/></svg>',
        'close' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="m18 6-12 12"/><path d="m6 6 12 12"/></svg>',
        'view' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>',
        default => '',
    };
}

requireLogin();

$yearLevels = [
    '1st Year',
    '2nd Year',
    '3rd Year',
    '4th Year',
];

$formData = [
    'student_number' => '',
    'full_name' => '',
    'email' => '',
    'course' => '',
    'year_level' => $yearLevels[0],
];

$errors = [];
$students = [];
$editId = null;
$viewId = null;
$viewStudent = null;
$connectionError = null;
$modalMode = null;
$existingPhoto = null;

try {
    $pdo = getDatabaseConnection();
} catch (RuntimeException $exception) {
    $pdo = null;
    $connectionError = $exception->getMessage();
}

if ($pdo instanceof PDO && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? null)) {
        flash('error', 'Invalid form submission. Please try again.');
        redirect('index.php');
    }

    $action = (string) ($_POST['action'] ?? 'create');

    if ($action === 'delete') {
        $studentId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($studentId === false || $studentId === null) {
            flash('error', 'Unable to delete that student record.');
            redirect('index.php');
        }

        $statement = $pdo->prepare('SELECT profile_image FROM students WHERE id = :id');
        $statement->execute(['id' => $studentId]);
        $studentToDelete = $statement->fetch();

        if ($studentToDelete === false) {
            flash('error', 'Student record not found.');
            redirect('index.php');
        }

        $statement = $pdo->prepare('DELETE FROM students WHERE id = :id');
        $statement->execute(['id' => $studentId]);
        deleteStudentPhoto($studentToDelete['profile_image'] ?? null);

        flash('success', 'Student record deleted successfully.');
        redirect('index.php');
    }

    $modalMode = $action === 'update' ? 'edit' : 'create';
    $currentStudent = null;

    $formData = [
        'student_number' => strtoupper(trim((string) ($_POST['student_number'] ?? ''))),
        'full_name' => trim((string) ($_POST['full_name'] ?? '')),
        'email' => trim((string) ($_POST['email'] ?? '')),
        'course' => trim((string) ($_POST['course'] ?? '')),
        'year_level' => trim((string) ($_POST['year_level'] ?? '')),
    ];

    if ($action === 'update') {
        $editId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

        if ($editId === false || $editId === null) {
            $errors[] = 'Invalid student selected for update.';
        } else {
            $statement = $pdo->prepare(
                'SELECT id, student_number, profile_image
                 FROM students
                 WHERE id = :id'
            );
            $statement->execute(['id' => $editId]);
            $currentStudent = $statement->fetch();

            if ($currentStudent === false) {
                $errors[] = 'Student record not found.';
            } else {
                $existingPhoto = $currentStudent['profile_image'] ?? null;
            }
        }
    }

    if ($formData['student_number'] === '' || preg_match(STUDENT_NUMBER_PATTERN, $formData['student_number']) !== 1) {
        $errors[] = 'Student number must use the BN-2310069-1 format.';
    }

    if ($formData['full_name'] === '') {
        $errors[] = 'Full name is required.';
    }

    if ($formData['email'] === '' || filter_var($formData['email'], FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'A valid email address is required.';
    }

    if ($formData['course'] === '') {
        $errors[] = 'Course is required.';
    }

    if (!in_array($formData['year_level'], $yearLevels, true)) {
        $errors[] = 'Please choose a valid year level.';
    }

    $photoUpload = inspectUploadedPhoto($_FILES['profile_image'] ?? null);

    if (is_string($photoUpload['error']) && $photoUpload['error'] !== '') {
        $errors[] = $photoUpload['error'];
    }

    $targetPhotoFilename = $existingPhoto;

    if (($photoUpload['has_upload'] ?? false) === true && is_string($photoUpload['extension'])) {
        $targetPhotoFilename = buildStudentPhotoFilename($formData['student_number'], $photoUpload['extension']);
    } elseif ($existingPhoto !== null && $existingPhoto !== '') {
        $currentExtension = strtolower((string) pathinfo($existingPhoto, PATHINFO_EXTENSION));

        if ($currentExtension !== '') {
            $targetPhotoFilename = buildStudentPhotoFilename($formData['student_number'], $currentExtension);
        }
    }

    if ($errors === []) {
        $newPhotoMoved = false;
        $renamedExistingPhoto = false;
        $oldPhotoToDelete = null;

        try {
            $pdo->beginTransaction();

            if ($action === 'update' && $editId !== null) {
                $statement = $pdo->prepare(
                    'UPDATE students
                     SET student_number = :student_number,
                         full_name = :full_name,
                         email = :email,
                         course = :course,
                         year_level = :year_level,
                         profile_image = :profile_image
                     WHERE id = :id'
                );

                $statement->execute([
                    'id' => $editId,
                    'student_number' => $formData['student_number'],
                    'full_name' => $formData['full_name'],
                    'email' => $formData['email'],
                    'course' => $formData['course'],
                    'year_level' => $formData['year_level'],
                    'profile_image' => $targetPhotoFilename,
                ]);
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO students (student_number, full_name, email, course, year_level, profile_image)
                     VALUES (:student_number, :full_name, :email, :course, :year_level, :profile_image)'
                );

                $statement->execute([
                    'student_number' => $formData['student_number'],
                    'full_name' => $formData['full_name'],
                    'email' => $formData['email'],
                    'course' => $formData['course'],
                    'year_level' => $formData['year_level'],
                    'profile_image' => $targetPhotoFilename,
                ]);
            }

            if (($photoUpload['has_upload'] ?? false) === true && is_string($photoUpload['tmp_name']) && $targetPhotoFilename !== null) {
                if (!moveStudentPhoto($photoUpload['tmp_name'], $targetPhotoFilename)) {
                    throw new RuntimeException('Profile picture could not be saved.');
                }

                $newPhotoMoved = true;

                if ($existingPhoto !== null && $existingPhoto !== '' && $existingPhoto !== $targetPhotoFilename) {
                    $oldPhotoToDelete = $existingPhoto;
                }
            } elseif ($action === 'update' && $existingPhoto !== null && $existingPhoto !== '' && $targetPhotoFilename !== null && $existingPhoto !== $targetPhotoFilename) {
                if (!renameStudentPhoto($existingPhoto, $targetPhotoFilename)) {
                    throw new RuntimeException('Profile picture filename could not be updated.');
                }

                $renamedExistingPhoto = true;
            }

            $pdo->commit();

            if (is_string($oldPhotoToDelete)) {
                deleteStudentPhoto($oldPhotoToDelete);
            }

            if ($action === 'update') {
                flash('success', 'Student record updated successfully.');
            } else {
                flash('success', 'Student record created successfully.');
            }

            redirect('index.php');
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($newPhotoMoved && $targetPhotoFilename !== null) {
                deleteStudentPhoto($targetPhotoFilename);
            }

            if (
                $renamedExistingPhoto
                && $existingPhoto !== null
                && $existingPhoto !== ''
                && $targetPhotoFilename !== null
                && $existingPhoto !== $targetPhotoFilename
            ) {
                renameStudentPhoto($targetPhotoFilename, $existingPhoto);
            }

            if ((string) $exception->getCode() === '23000') {
                $errors[] = 'Student number or email already exists.';
            } elseif ($exception instanceof RuntimeException) {
                $errors[] = $exception->getMessage();
            } else {
                $errors[] = 'Database write failed. Please try again.';
            }
        }
    }
}

if ($pdo instanceof PDO && $_SERVER['REQUEST_METHOD'] !== 'POST' && (string) ($_GET['modal'] ?? '') === 'create') {
    $modalMode = 'create';
}

if ($pdo instanceof PDO && $_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['view'])) {
    $viewId = filter_input(INPUT_GET, 'view', FILTER_VALIDATE_INT);

    if ($viewId === false || $viewId === null) {
        flash('error', 'Invalid student selected for viewing.');
        redirect('index.php');
    }

    $statement = $pdo->prepare(
        'SELECT id, student_number, full_name, email, course, year_level, profile_image, created_at, updated_at
         FROM students
         WHERE id = :id'
    );
    $statement->execute(['id' => $viewId]);
    $viewStudent = $statement->fetch();

    if ($viewStudent === false) {
        flash('error', 'Student record not found.');
        redirect('index.php');
    }

    $modalMode = 'view';
} elseif ($pdo instanceof PDO && $_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['edit'])) {
    $editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);

    if ($editId === false || $editId === null) {
        flash('error', 'Invalid student selected for editing.');
        redirect('index.php');
    }

    $statement = $pdo->prepare(
        'SELECT id, student_number, full_name, email, course, year_level, profile_image
         FROM students
         WHERE id = :id'
    );
    $statement->execute(['id' => $editId]);
    $student = $statement->fetch();

    if ($student === false) {
        flash('error', 'Student record not found.');
        redirect('index.php');
    }

    $formData = [
        'student_number' => $student['student_number'],
        'full_name' => $student['full_name'],
        'email' => $student['email'],
        'course' => $student['course'],
        'year_level' => $student['year_level'],
    ];
    $existingPhoto = $student['profile_image'] ?? null;
    $modalMode = 'edit';
}

if ($pdo instanceof PDO) {
    $statement = $pdo->query(
        'SELECT id, student_number, full_name, email, course, year_level, profile_image, created_at
         FROM students
         ORDER BY id DESC'
    );
    $students = $statement->fetchAll();
}

$flash = getFlash();
$currentUser = currentUser();
$currentUsername = $currentUser['username'] ?? 'user';
$profileInitial = strtoupper(substr($currentUsername, 0, 1));
$showModal = in_array($modalMode, ['create', 'edit', 'view'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Record System</title>
    <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
    <main class="page-shell">
        <header class="screen-head">
            <div class="title-block">
                <h1>Student Record System</h1>
            </div>

            <div class="toolbar">
                <?php if ($connectionError === null): ?>
                    <a class="button button-primary" href="index.php?modal=create">
                        <?= iconSvg('plus') ?>
                        <span>Create</span>
                    </a>
                <?php endif; ?>

                <section class="profile-card">
                    <div class="profile-avatar"><?= escape($profileInitial) ?></div>
                    <div class="profile-copy">
                        <span>Profile</span>
                        <strong><?= escape($currentUsername) ?></strong>
                    </div>
                </section>

                <a class="button button-secondary" href="logout.php">
                    <?= iconSvg('logout') ?>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <?php if ($flash !== null): ?>
            <div class="flash flash-<?= escape($flash['type']) ?>">
                <?= escape($flash['message']) ?>
            </div>
        <?php endif; ?>

        <?php if ($connectionError !== null): ?>
            <section class="panel warning-panel">
                <h2>Database Connection Needed</h2>
                <p><?= escape($connectionError) ?></p>
                <p>Import <code>database/setup.sql</code> and confirm the credentials inside <code>config/database.php</code>.</p>
            </section>
        <?php else: ?>
            <section class="panel">
                <div class="table-top">
                    <h2>Students</h2>
                    <span class="stat-pill"><?= count($students) ?> record<?= count($students) === 1 ? '' : 's' ?></span>
                </div>

                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Student No.</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Course</th>
                                <th>Year</th>
                                <th>Created</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($students === []): ?>
                                <tr>
                                    <td colspan="9" class="empty-state">No student records yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <?php $studentPhoto = studentPhotoUrl($student['profile_image'] ?? null); ?>
                                    <tr>
                                        <td><?= (int) $student['id'] ?></td>
                                        <td>
                                            <?php if ($studentPhoto !== null): ?>
                                                <img class="student-photo" src="<?= escape($studentPhoto) ?>" alt="<?= escape($student['full_name']) ?>">
                                            <?php else: ?>
                                                <div class="student-photo student-photo-fallback"><?= escape(studentInitial((string) $student['full_name'])) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= escape($student['student_number']) ?></td>
                                        <td><?= escape($student['full_name']) ?></td>
                                        <td><?= escape($student['email']) ?></td>
                                        <td><?= escape($student['course']) ?></td>
                                        <td><?= escape($student['year_level']) ?></td>
                                        <td><?= escape(date('M d, Y', strtotime((string) $student['created_at']))) ?></td>
                                        <td class="actions">
                                            <a class="button button-action button-view" href="index.php?view=<?= (int) $student['id'] ?>">
                                                <?= iconSvg('view') ?>
                                                <span>View</span>
                                            </a>
                                            <a class="button button-action button-update" href="index.php?edit=<?= (int) $student['id'] ?>">
                                                <?= iconSvg('edit') ?>
                                                <span>Update</span>
                                            </a>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this student record?');">
                                                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?= (int) $student['id'] ?>">
                                                <button class="button button-action button-delete" type="submit">
                                                    <?= iconSvg('trash') ?>
                                                    <span>Delete</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <?php if ($showModal): ?>
                <div class="modal-overlay" id="student-modal">
                    <section class="panel modal-card">
                        <div class="modal-header">
                            <div class="modal-title">
                                <?php if ($modalMode === 'view'): ?>
                                    <?= iconSvg('view') ?>
                                    <h2>View Student</h2>
                                <?php elseif ($modalMode === 'edit'): ?>
                                    <?= iconSvg('edit') ?>
                                    <h2>Update Student</h2>
                                <?php else: ?>
                                    <?= iconSvg('plus') ?>
                                    <h2>Create Student</h2>
                                <?php endif; ?>
                            </div>
                            <a class="icon-action icon-close" href="index.php" title="Close">
                                <?= iconSvg('close') ?>
                                <span class="sr-only">Close</span>
                            </a>
                        </div>

                        <?php if ($modalMode === 'view' && is_array($viewStudent)): ?>
                            <?php $viewPhoto = studentPhotoUrl($viewStudent['profile_image'] ?? null); ?>
                            <div class="view-layout">
                                <div class="view-hero">
                                    <?php if ($viewPhoto !== null): ?>
                                        <img class="student-photo student-photo-large view-photo" src="<?= escape($viewPhoto) ?>" alt="<?= escape($viewStudent['full_name']) ?>">
                                    <?php else: ?>
                                        <div class="student-photo student-photo-large student-photo-fallback view-photo">
                                            <?= escape(studentInitial((string) $viewStudent['full_name'])) ?>
                                        </div>
                                    <?php endif; ?>

                                    <div>
                                        <h3><?= escape($viewStudent['full_name']) ?></h3>
                                        <p class="modal-note"><?= escape($viewStudent['student_number']) ?></p>
                                    </div>
                                </div>

                                <div class="detail-grid">
                                    <article class="detail-card">
                                        <span>Email</span>
                                        <strong><?= escape($viewStudent['email']) ?></strong>
                                    </article>
                                    <article class="detail-card">
                                        <span>Course</span>
                                        <strong><?= escape($viewStudent['course']) ?></strong>
                                    </article>
                                    <article class="detail-card">
                                        <span>Year Level</span>
                                        <strong><?= escape($viewStudent['year_level']) ?></strong>
                                    </article>
                                    <article class="detail-card">
                                        <span>Created</span>
                                        <strong><?= escape(date('M d, Y h:i A', strtotime((string) $viewStudent['created_at']))) ?></strong>
                                    </article>
                                    <article class="detail-card">
                                        <span>Updated</span>
                                        <strong><?= escape(date('M d, Y h:i A', strtotime((string) $viewStudent['updated_at']))) ?></strong>
                                    </article>
                                    <article class="detail-card">
                                        <span>Photo File</span>
                                        <strong><?= escape((string) ($viewStudent['profile_image'] ?: 'No uploaded picture')) ?></strong>
                                    </article>
                                </div>

                                <div class="modal-actions">
                                    <a class="button button-muted" href="index.php">Close</a>
                                    <a class="button button-update" href="index.php?edit=<?= (int) $viewStudent['id'] ?>">
                                        <?= iconSvg('edit') ?>
                                        <span>Update</span>
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php if ($errors !== []): ?>
                                <div class="error-list">
                                    <?php foreach ($errors as $error): ?>
                                        <p><?= escape($error) ?></p>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <form method="post" class="student-form" enctype="multipart/form-data">
                                <input type="hidden" name="csrf_token" value="<?= escape(csrfToken()) ?>">
                                <input type="hidden" name="action" value="<?= $modalMode === 'edit' ? 'update' : 'create' ?>">

                                <?php if ($modalMode === 'edit' && $editId !== null): ?>
                                    <input type="hidden" name="id" value="<?= (int) $editId ?>">
                                <?php endif; ?>

                                <label class="field">
                                    <span>Student Number</span>
                                    <input type="text" name="student_number" value="<?= escape($formData['student_number']) ?>" placeholder="BN-2310069-1" pattern="BN-[0-9]{7}-[0-9]" title="Use the BN-2310069-1 format." required>
                                    <small class="field-note">Required format: <code>BN-2310069-1</code></small>
                                </label>

                                <label class="field">
                                    <span>Full Name</span>
                                    <input type="text" name="full_name" value="<?= escape($formData['full_name']) ?>" placeholder="Juan Dela Cruz" required>
                                </label>

                                <label class="field">
                                    <span>Email Address</span>
                                    <input type="email" name="email" value="<?= escape($formData['email']) ?>" placeholder="student@example.com" required>
                                </label>

                                <label class="field">
                                    <span>Course</span>
                                    <input type="text" name="course" value="<?= escape($formData['course']) ?>" placeholder="BS Information Technology" required>
                                </label>

                                <label class="field">
                                    <span>Year Level</span>
                                    <select name="year_level" required>
                                        <?php foreach ($yearLevels as $yearLevel): ?>
                                            <option value="<?= escape($yearLevel) ?>" <?= $formData['year_level'] === $yearLevel ? 'selected' : '' ?>>
                                                <?= escape($yearLevel) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>

                                <label class="field">
                                    <span class="field-inline">
                                        <?= iconSvg('camera') ?>
                                        <span>Profile Picture</span>
                                    </span>
                                    <input type="file" name="profile_image" accept=".jpg,.jpeg,.png,.webp">
                                    <small class="field-note">JPG, PNG, or WEBP up to 2MB. The saved filename will use the student number, like <code>BN-2310069-1.jpg</code>.</small>
                                </label>

                                <?php if ($existingPhoto !== null && $existingPhoto !== ''): ?>
                                    <?php $currentPhotoUrl = studentPhotoUrl($existingPhoto); ?>
                                    <?php if ($currentPhotoUrl !== null): ?>
                                        <div class="photo-preview">
                                            <img class="student-photo student-photo-large" src="<?= escape($currentPhotoUrl) ?>" alt="<?= escape($formData['full_name'] !== '' ? $formData['full_name'] : 'Student photo') ?>">
                                            <div class="photo-preview-copy">
                                                <strong>Current Photo</strong>
                                                <p><?= escape($existingPhoto) ?></p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <div class="modal-actions">
                                    <a class="button button-muted" href="index.php">Cancel</a>
                                    <button class="button button-primary" type="submit">
                                        <?= $modalMode === 'edit' ? iconSvg('edit') : iconSvg('plus') ?>
                                        <span><?= $modalMode === 'edit' ? 'Update' : 'Create' ?></span>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </section>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
    <?php if ($showModal): ?>
        <script>
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    window.location.href = 'index.php';
                }
            });

            const modalOverlay = document.getElementById('student-modal');
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function (event) {
                    if (event.target === modalOverlay) {
                        window.location.href = 'index.php';
                    }
                });
            }
        </script>
    <?php endif; ?>
</body>
</html>
