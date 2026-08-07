<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';
require_role('manager');

$me = current_user();
$pageTitle = 'Assign Task';

$maxFiles = 10;
$maxFileSize = 10 * 1024 * 1024; // 10 MB per file
$allowed = [
    'jpg'  => ['image/jpeg'],
    'jpeg' => ['image/jpeg'],
    'png'  => ['image/png'],
    'gif'  => ['image/gif'],
    'webp' => ['image/webp'],
    'pdf'  => ['application/pdf'],
    'doc'  => ['application/msword'],
    'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    'xls'  => ['application/vnd.ms-excel'],
    'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
    'ppt'  => ['application/vnd.ms-powerpoint'],
    'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
    'txt'  => ['text/plain'],
    'csv'  => ['text/csv', 'application/csv', 'text/plain']
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $assigned_to = (int)($_POST['assigned_to'] ?? 0);
    $due_date    = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

    $check = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'employee'");
    $check->execute([$assigned_to]);

    $files = $_FILES['attachments'] ?? null;
    $fileCount = 0;

    if ($files && isset($files['name']) && is_array($files['name'])) {
        foreach ($files['name'] as $name) {
            if ($name !== '') $fileCount++;
        }
    }

    $errors = [];

    if ($title === '') {
        $errors[] = 'Please enter a task title.';
    }
    if (!$check->fetch()) {
        $errors[] = 'Please select a valid employee.';
    }
    if ($fileCount > $maxFiles) {
        $errors[] = "You can attach a maximum of {$maxFiles} files.";
    }

    // Validate selected files before creating the task.
    $validFiles = [];
    if ($fileCount > 0 && $files) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < count($files['name']); $i++) {
            if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $errorCode = (int)$files['error'][$i];
            $original = basename($files['name'][$i]);
            $size = (int)$files['size'][$i];
            $tmp = $files['tmp_name'][$i];
            $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

            if ($errorCode !== UPLOAD_ERR_OK) {
                $errors[] = htmlspecialchars($original) . ' could not be uploaded.';
                continue;
            }
            if (!isset($allowed[$ext])) {
                $errors[] = htmlspecialchars($original) . ' has an unsupported file type.';
                continue;
            }
            if ($size <= 0 || $size > $maxFileSize) {
                $errors[] = htmlspecialchars($original) . ' exceeds the 10 MB file limit.';
                continue;
            }

            $mime = $finfo->file($tmp);
            if (!in_array($mime, $allowed[$ext], true)) {
                $errors[] = htmlspecialchars($original) . ' failed file-type validation.';
                continue;
            }

            $validFiles[] = [
                'original_name' => $original,
                'tmp_name' => $tmp,
                'size' => $size,
                'mime' => $mime,
                'extension' => $ext
            ];
        }
    }

    if (!$errors) {
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->prepare("
                INSERT INTO tasks (title, description, assigned_to, assigned_by, due_date, status)
                VALUES (?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([
                $title,
                $description,
                $assigned_to,
                $_SESSION['user_id'],
                $due_date
            ]);

            $taskId = (int)$pdo->lastInsertId();

            if ($validFiles) {
                $uploadRoot = __DIR__ . '/../uploads/tasks';
                $taskDir = $uploadRoot . '/' . $taskId;

                if (!is_dir($taskDir) && !mkdir($taskDir, 0755, true)) {
                    throw new RuntimeException('Unable to create the task upload directory.');
                }

                $attachmentStmt = $pdo->prepare("
                    INSERT INTO task_attachments
                    (task_id, original_name, stored_name, file_path, mime_type, file_size)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");

                foreach ($validFiles as $file) {
                    $storedName = bin2hex(random_bytes(16)) . '.' . $file['extension'];
                    $destination = $taskDir . '/' . $storedName;
                    $relativePath = 'uploads/tasks/' . $taskId . '/' . $storedName;

                    if (!move_uploaded_file($file['tmp_name'], $destination)) {
                        throw new RuntimeException('Unable to save one of the uploaded files.');
                    }

                    $attachmentStmt->execute([
                        $taskId,
                        $file['original_name'],
                        $storedName,
                        $relativePath,
                        $file['mime'],
                        $file['size']
                    ]);
                }
            }

            $pdo->commit();
            header('Location: dashboard.php?task=created');
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            // Remove any task folder created during a failed transaction.
            if (!empty($taskId) && !empty($taskDir) && is_dir($taskDir)) {
                foreach (glob($taskDir . '/*') as $uploadedFile) {
                    if (is_file($uploadedFile)) @unlink($uploadedFile);
                }
                @rmdir($taskDir);
            }

            $error = 'The task could not be created. Please try again.';
        }
    } else {
        $error = implode(' ', $errors);
    }
}

$employees = $pdo->query("
    SELECT id, name
    FROM users
    WHERE role='employee'
    ORDER BY name
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Assign Task - TaskFlow</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/dashboard-v2.css">
</head>
<body>
<div class="wrapper">
<?php include "../includes/sidebar.php"; ?>
<div class="main-content">
<?php include "../includes/topbar.php"; ?>
<div class="content">

<div class="page-header">
    <div>
        <h1>Assign New Task</h1>
        <p>Create a task, assign it to an employee, attach files, and set a deadline.</p>
    </div>
    <div class="header-actions">
        <a href="dashboard.php" class="btn btn-outline-light">
            <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
</div>

<?php if (isset($error)): ?>
<div class="task-form-alert">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?= htmlspecialchars($error) ?></span>
</div>
<?php endif; ?>

<div class="task-form-layout">
    <div class="widget task-form-card">
        <div class="widget-header">
            <div>
                <h3><i class="fa-solid fa-plus"></i> Task Details</h3>
                <p class="form-helper">Provide the information your employee needs to complete the task.</p>
            </div>
        </div>

        <?php if (empty($employees)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-users"></i>
                <p>No employees are available. Add an employee before assigning a task.</p>
            </div>
        <?php else: ?>
        <form action="assign_task.php" method="POST" enctype="multipart/form-data" id="assignTaskForm">
            <div class="form-group">
                <label for="title">Task Title <span class="required-mark">*</span></label>
                <input id="title" type="text" name="title" class="form-control" placeholder="e.g. Prepare monthly sales report" required autofocus maxlength="200">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" class="form-control task-description" placeholder="Describe what needs to be done, include useful instructions or context..."></textarea>
            </div>

            <div class="form-group">
                <label for="attachments">Attachments <span class="field-hint">Optional · up to 10 files · 10 MB each</span></label>
                <div class="file-upload-box" id="fileUploadBox">
                    <input
                        id="attachments"
                        type="file"
                        name="attachments[]"
                        class="file-upload-input"
                        multiple
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv"
                    >
                    <div class="file-upload-content">
                        <div class="file-upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                        <div>
                            <strong>Choose files or drag and drop</strong>
                            <span>Images, PDF, Word, Excel, PowerPoint, TXT and CSV</span>
                        </div>
                        <button type="button" class="btn btn-light file-browse-btn" id="fileBrowseBtn">
                            <i class="fa-solid fa-paperclip"></i> Browse Files
                        </button>
                    </div>
                </div>
                <div id="fileList" class="selected-files" aria-live="polite"></div>
                <div class="upload-limit-message" id="uploadLimitMessage"></div>
            </div>

            <div class="row">
                <div class="col-6">
                    <div class="form-group">
                        <label for="assigned_to">Assign Employee <span class="required-mark">*</span></label>
                        <select id="assigned_to" name="assigned_to" class="form-control" required>
                            <option value="">Select an employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= (int)$emp['id'] ?>" <?= ((int)($_POST['assigned_to'] ?? 0) === (int)$emp['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($emp['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group">
                        <label for="due_date">Due Date</label>
                        <input id="due_date" type="date" name="due_date" class="form-control" value="<?= htmlspecialchars($_POST['due_date'] ?? '') ?>">
                    </div>
                </div>
            </div>

            <div class="task-form-footer">
                <a href="dashboard.php" class="btn btn-light">Cancel</a>
                <button type="submit" class="btn btn-primary">
                    <i class="fa-solid fa-paper-plane"></i> Assign Task
                </button>
            </div>
        </form>
        <?php endif; ?>
    </div>

    <div class="widget task-form-info">
        <div class="widget-header">
            <h3><i class="fa-solid fa-circle-info"></i> Before You Assign</h3>
        </div>

        <div class="form-info-item">
            <div class="form-info-icon"><i class="fa-solid fa-heading"></i></div>
            <div class="form-info-copy">
                <strong>Clear title</strong>
                <span>Use a short title that makes the expected work obvious.</span>
            </div>
        </div>

        <div class="form-info-item">
            <div class="form-info-icon"><i class="fa-solid fa-align-left"></i></div>
            <div class="form-info-copy">
                <strong>Useful instructions</strong>
                <span>Add important details, files, or expectations in the description.</span>
            </div>
        </div>

        <div class="form-info-item">
            <div class="form-info-icon"><i class="fa-solid fa-user-check"></i></div>
            <div class="form-info-copy">
                <strong>Right employee</strong>
                <span>Assign the task to the team member responsible for completing it.</span>
            </div>
        </div>

        <div class="form-info-item">
            <div class="form-info-icon"><i class="fa-solid fa-calendar-check"></i></div>
            <div class="form-info-copy">
                <strong>Set a deadline</strong>
                <span>A due date helps the team prioritize work and makes overdue tasks visible.</span>
            </div>
        </div>
    </div>
</div>

</div>
</div>
</div>

<script src="../assets/js/dashboard.js"></script>
<script>
(function () {
    const input = document.getElementById('attachments');
    const browse = document.getElementById('fileBrowseBtn');
    const box = document.getElementById('fileUploadBox');
    const list = document.getElementById('fileList');
    const message = document.getElementById('uploadLimitMessage');
    const form = document.getElementById('assignTaskForm');
    if (!input || !browse || !box || !list) return;

    const maxFiles = 10;
    const maxBytes = 10 * 1024 * 1024;
    let selectedFiles = [];

    browse.addEventListener('click', () => input.click());

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function iconFor(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (['jpg','jpeg','png','gif','webp'].includes(ext)) return 'fa-file-image';
        if (ext === 'pdf') return 'fa-file-pdf';
        if (['doc','docx'].includes(ext)) return 'fa-file-word';
        if (['xls','xlsx','csv'].includes(ext)) return 'fa-file-excel';
        if (['ppt','pptx'].includes(ext)) return 'fa-file-powerpoint';
        return 'fa-file-lines';
    }

    function render() {
        list.innerHTML = '';
        message.textContent = selectedFiles.length
            ? selectedFiles.length + ' of ' + maxFiles + ' files selected'
            : '';

        selectedFiles.forEach((file, index) => {
            const item = document.createElement('div');
            item.className = 'selected-file';
            item.innerHTML =
                '<div class="selected-file-icon"><i class="fa-solid ' + iconFor(file) + '"></i></div>' +
                '<div class="selected-file-details"><strong></strong><span>' + formatSize(file.size) + '</span></div>' +
                '<button type="button" class="selected-file-remove" aria-label="Remove file"><i class="fa-solid fa-xmark"></i></button>';
            item.querySelector('strong').textContent = file.name;
            item.querySelector('.selected-file-remove').addEventListener('click', () => {
                selectedFiles.splice(index, 1);
                syncInput();
                render();
            });
            list.appendChild(item);
        });
    }

    function addFiles(fileList) {
        const incoming = Array.from(fileList);
        let rejected = [];

        incoming.forEach(file => {
            if (selectedFiles.length >= maxFiles) {
                rejected.push(file.name + ' (maximum 10 files)');
                return;
            }
            if (file.size > maxBytes) {
                rejected.push(file.name + ' (over 10 MB)');
                return;
            }
            if (!selectedFiles.some(existing => existing.name === file.name && existing.size === file.size)) {
                selectedFiles.push(file);
            }
        });

        if (rejected.length) {
            message.textContent = rejected.join(', ');
            message.classList.add('is-error');
        } else {
            message.classList.remove('is-error');
        }

        syncInput();
        render();
    }

    function syncInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        input.files = dt.files;
    }

    input.addEventListener('change', e => addFiles(e.target.files));

    ['dragenter','dragover'].forEach(eventName => {
        box.addEventListener(eventName, e => {
            e.preventDefault();
            box.classList.add('is-dragging');
        });
    });

    ['dragleave','drop'].forEach(eventName => {
        box.addEventListener(eventName, e => {
            e.preventDefault();
            box.classList.remove('is-dragging');
        });
    });

    box.addEventListener('drop', e => addFiles(e.dataTransfer.files));

    form.addEventListener('submit', e => {
        if (selectedFiles.length > maxFiles) {
            e.preventDefault();
            message.textContent = 'Please select no more than 10 files.';
            message.classList.add('is-error');
        }
    });
})();
</script>
</body>
</html>
