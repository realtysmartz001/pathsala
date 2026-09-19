<?php
session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

include '../includes/db_connect.php';

$message = "";
$messageType = "";

// ✅ Role definitions — same list used everywhere else
$jobRoles = [
    'tele_sales'       => 'Tele Sales',
    'sales_consultant' => 'Sales Consultant',
    'team_leader'      => 'Team Leader',
    'others'           => 'Others',
];

if (!isset($_GET['id'])) {
    header("Location: ../tests/manage_questions.php");
    exit();
}

$id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    header("Location: ../tests/manage_questions.php");
    exit();
}

$question = $result->fetch_assoc();
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $job_role = trim($_POST['job_role']);
    $set_name = trim($_POST['set_name']);
    $set_id = trim($_POST['set_id']);
    $question_text = trim($_POST['question_text']);
    $option_a = trim($_POST['option_a']);
    $option_b = trim($_POST['option_b']);
    $option_c = trim($_POST['option_c']);
    $option_d = trim($_POST['option_d']);
    $correct_option = $_POST['correct_option'];
    $explanation = trim($_POST['explanation'] ?? '');
    $explanation = ($explanation === '') ? null : $explanation;

    if (!array_key_exists($job_role, $jobRoles)) {
        $message = "Please select a valid Job Role.";
        $messageType = "error";
    } else {
        $stmt = $conn->prepare("UPDATE questions SET 
            job_role=?, set_name=?, set_id=?, question_text=?, 
            option_a=?, option_b=?, option_c=?, option_d=?, correct_option=?, explanation=? 
            WHERE id=?");
        $stmt->bind_param(
            "ssssssssssi",
            $job_role,
            $set_name,
            $set_id,
            $question_text,
            $option_a,
            $option_b,
            $option_c,
            $option_d,
            $correct_option,
            $explanation,
            $id
        );

        if ($stmt->execute()) {
            $message = "Question updated successfully!";
            $messageType = "success";
            $question = [
                'job_role' => $job_role,
                'set_name' => $set_name,
                'set_id' => $set_id,
                'question_text' => $question_text,
                'option_a' => $option_a,
                'option_b' => $option_b,
                'option_c' => $option_c,
                'option_d' => $option_d,
                'correct_option' => $correct_option,
                'explanation' => $explanation
            ];
        } else {
            $message = "Update failed: " . $stmt->error;
            $messageType = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Question</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* ══ ROOT ══ */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            color: #333;
            min-height: 100vh;
            padding: 30px 16px;
        }

        /* ══ CARD ══ */
        .card {
            background: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 16px;
            max-width: 680px;
            margin: 0 auto;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        /* ══ CARD HEADER ══ */
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            padding: 20px 28px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .header-icon {
            width: 42px;
            height: 42px;
            background: rgba(220, 53, 69, 0.1);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            font-size: 20px;
        }

        .card-header h2 {
            font-size: 18px;
            font-weight: 700;
            color: #1a1a1a;
        }

        .card-header p {
            font-size: 12px;
            color: #888;
            margin-top: 2px;
        }

        /* ID Badge */
        .id-badge {
            background: rgba(220, 53, 69, 0.08);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
            border-radius: 20px;
            padding: 4px 14px;
            font-size: 13px;
            font-weight: 600;
        }

        /* ══ CARD BODY ══ */
        .card-body {
            padding: 28px;
        }

        /* ══ ALERT ══ */
        .alert {
            padding: 12px 16px;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 24px;
        }

        .alert-success {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
        }

        .alert-error {
            background: #fff5f5;
            border: 1px solid #fecaca;
            color: #dc2626;
        }

        /* ══ SECTION TITLE ══ */
        .section-title {
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #999;
            margin: 24px 0 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .section-title i {
            color: #dc3545;
        }

        .section-title::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #ebebeb;
        }

        /* ══ FORM ══ */
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin-bottom: 16px;
        }

        .form-group label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
        }

        .form-group label span {
            color: #dc3545;
            margin-left: 2px;
        }

        /* ══ INPUTS ══ */
        .form-control {
            background: #f8f9fa;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            color: #222;
            font-size: 14px;
            padding: 10px 14px;
            width: 100%;
            transition: border-color 0.2s, background 0.2s;
            font-family: 'Segoe UI', sans-serif;
            outline: none;
        }

        .form-control:focus {
            border-color: #dc3545;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.08);
        }

        .form-control::placeholder {
            color: #bbb;
        }

        select.form-control {
            cursor: pointer;
            color-scheme: light;
        }

        select.form-control option {
            color: #1a1a1a;
            background: #ffffff;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }

        /* ══ OPTIONS GRID ══ */
        .options-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
        }

        .option-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .option-label {
            font-size: 13px;
            font-weight: 600;
            color: #555;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .option-badge {
            width: 22px;
            height: 22px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            font-weight: 700;
        }

        .badge-a {
            background: rgba(99, 102, 241, 0.12);
            color: #6366f1;
        }

        .badge-b {
            background: rgba(34, 197, 94, 0.12);
            color: #16a34a;
        }

        .badge-c {
            background: rgba(251, 146, 60, 0.12);
            color: #ea580c;
        }

        .badge-d {
            background: rgba(236, 72, 153, 0.12);
            color: #db2777;
        }

        /* ══ CORRECT OPTION CARDS ══ */
        .correct-select-wrapper {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-top: 4px;
        }

        .correct-option-btn {
            display: none;
        }

        .correct-option-btn+label {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 12px 8px;
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            background: #f8f9fa;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 13px;
            font-weight: 600;
            color: #777;
        }

        .correct-option-btn+label:hover {
            border-color: #ccc;
            background: #f0f0f0;
        }

        .correct-option-btn:checked+label {
            border-color: #dc3545;
            background: rgba(220, 53, 69, 0.06);
            color: #dc3545;
        }

        .correct-option-btn+label .option-badge {
            width: 28px;
            height: 28px;
            font-size: 14px;
        }

        /* ══ SUBMIT BUTTON ══ */
        .btn-submit {
            width: 100%;
            padding: 13px;
            background: #dc3545;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }

        .btn-submit:hover {
            background: #b02a37;
        }

        .btn-submit:active {
            transform: scale(0.99);
        }

        /* ══ BACK LINK ══ */
        .back-link {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            color: #888;
            text-decoration: none;
            font-size: 13px;
            margin-top: 20px;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: #dc3545;
        }

        /* ══ RESPONSIVE ══ */
        @media (max-width: 540px) {
            .form-row {
                grid-template-columns: 1fr;
            }

            .options-grid {
                grid-template-columns: 1fr;
            }

            .correct-select-wrapper {
                grid-template-columns: repeat(2, 1fr);
            }

            .card-body {
                padding: 20px 16px;
            }

            .card-header {
                padding: 16px 20px;
            }
        }
    </style>
</head>

<body>

    <div class="card">

        <!-- Header -->
        <div class="card-header">
            <div class="card-header-left">
                <div class="header-icon">
                    <i class="bi bi-pencil-square"></i>
                </div>
                <div>
                    <h2>Edit Question</h2>
                    <p>Update question details below</p>
                </div>
            </div>
            <div class="id-badge">
                <i class="bi bi-hash"></i> ID: <?php echo $id; ?>
            </div>
        </div>

        <!-- Body -->
        <div class="card-body">

            <!-- Alert -->
            <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>">
                    <i class="bi bi-<?php echo $messageType === 'success'
                        ? 'check-circle-fill' : 'x-circle-fill'; ?>"></i>
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <!-- Set Info -->
                <div class="section-title">
                    <i class="bi bi-collection"></i> Set Information
                </div>

                <div class="form-group">
                    <label>Job Role <span>*</span></label>
                    <select name="job_role" class="form-control" required>
                        <option value="">-- Select Job Role --</option>
                        <?php foreach ($jobRoles as $key => $label): ?>
                            <option value="<?php echo htmlspecialchars($key); ?>"
                                <?php echo (($question['job_role'] ?? '') === $key) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Set Name <span>*</span></label>
                        <input type="text" name="set_name" class="form-control" placeholder="e.g. General Knowledge"
                            value="<?php echo htmlspecialchars($question['set_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Set ID <span>*</span></label>
                        <input type="text" name="set_id" class="form-control" placeholder="e.g. ts001"
                            value="<?php echo htmlspecialchars($question['set_id']); ?>" required>
                    </div>
                </div>

                <!-- Question -->
                <div class="section-title">
                    <i class="bi bi-question-circle"></i> Question
                </div>

                <div class="form-group">
                    <label>Question Text <span>*</span></label>
                    <textarea name="question_text" class="form-control" placeholder="Enter the question..."
                        required><?php echo htmlspecialchars($question['question_text']); ?></textarea>
                </div>

                <!-- Options -->
                <div class="section-title">
                    <i class="bi bi-list-ul"></i> Answer Options
                </div>

                <div class="options-grid">
                    <?php
                    $optionData = [
                        'a' => ['badge-a', 'A'],
                        'b' => ['badge-b', 'B'],
                        'c' => ['badge-c', 'C'],
                        'd' => ['badge-d', 'D'],
                    ];
                    foreach ($optionData as $key => [$cls, $label]):
                        ?>
                        <div class="option-group">
                            <div class="option-label">
                                <span class="option-badge <?php echo $cls; ?>"><?php echo $label; ?></span>
                                Option <?php echo $label; ?>
                                <span style="color:#dc3545">*</span>
                            </div>
                            <input type="text" name="option_<?php echo $key; ?>" class="form-control"
                                placeholder="Option <?php echo $label; ?>"
                                value="<?php echo htmlspecialchars($question['option_' . $key]); ?>" required>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Correct Answer -->
                <div class="section-title" style="margin-top:24px;">
                    <i class="bi bi-patch-check"></i> Correct Answer
                </div>

                <div class="correct-select-wrapper">
                    <?php
                    $opts = [
                        'A' => 'badge-a',
                        'B' => 'badge-b',
                        'C' => 'badge-c',
                        'D' => 'badge-d'
                    ];
                    foreach ($opts as $val => $cls):
                        $checked = ($question['correct_option'] === $val) ? 'checked' : '';
                        ?>
                        <input type="radio" class="correct-option-btn" name="correct_option" id="opt<?php echo $val; ?>"
                            value="<?php echo $val; ?>" <?php echo $checked; ?> required>
                        <label for="opt<?php echo $val; ?>">
                            <span class="option-badge <?php echo $cls; ?>" style="width:28px;height:28px;font-size:14px;">
                                <?php echo $val; ?>
                            </span>
                            Option <?php echo $val; ?>
                        </label>
                    <?php endforeach; ?>
                </div>

                <!-- Explanation -->
                <div class="section-title" style="margin-top:24px;">
                    <i class="bi bi-lightbulb"></i> Explanation
                </div>

                <div class="form-group">
                    <label>Explanation <span style="color:#aaa;font-weight:400;">(optional)</span></label>
                    <textarea name="explanation" class="form-control"
                        placeholder="Explain why the correct answer is correct (shown to users on their result page)..."
                        style="min-height:90px;"><?php echo htmlspecialchars($question['explanation'] ?? ''); ?></textarea>
                </div>

                <!-- Submit -->
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-lg"></i> Update Question
                </button>

            </form>

            <!-- Back -->
            <a href="../tests/manage_questions.php" class="back-link">
                <i class="bi bi-arrow-left"></i> Back to Manage Questions
            </a>

        </div>
    </div>

</body>

</html>