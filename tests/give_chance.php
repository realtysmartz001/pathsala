<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}
include '../includes/db_connect.php';
if (!isset($_GET['user_id'])) {
    header("Location: ../tests/view_results.php");
    exit();
}
$user_id = (int) $_GET['user_id'];

// ✅ User ka naam + assigned_set + job_role lo
$uRes      = mysqli_query($conn, "SELECT name, assigned_set, extra_attempt, job_role FROM users WHERE id = $user_id");
$uRow      = mysqli_fetch_assoc($uRes);
$user_name = $uRow['name'];
$job_role  = trim($uRow['job_role'] ?? '');

// ✅ Job role display labels
$jobRoleLabels = [
    'tele_sales'       => 'Tele Sales',
    'sales_consultant' => 'Sales Consultant',
    'team_leader'      => 'Team Leader',
];
$roleLabel = $jobRoleLabels[$job_role] ?? null;

// ✅ Last attempt lo
$aRes = mysqli_query($conn, "SELECT attempt_no, set_no, end_time 
                              FROM user_attempts 
                              WHERE user_id = $user_id 
                              ORDER BY attempt_no DESC 
                              LIMIT 1");
$aRow         = mysqli_fetch_assoc($aRes);
$last_attempt = (int)($aRow['attempt_no'] ?? 0);
$last_set     = $aRow['set_no'] ?? $uRow['assigned_set'] ?? 'rs001';
$next_attempt = $last_attempt + 1;

// ✅ Safety Check 1 — Test abhi submit nahi hua
if (empty($aRow['end_time'])) {
    header("Location: ../tests/view_results.php?error=test_in_progress");
    exit();
}
// ✅ Safety Check 2 — Already extra chance pending hai
if ((int)$uRow['extra_attempt'] === 1) {
    header("Location: ../tests/view_results.php?error=already_pending");
    exit();
}
// ✅ Safety Check 3 — Naya attempt already insert ho chuka hai
$dupCheck = mysqli_query($conn, "SELECT id FROM user_attempts 
                                  WHERE user_id = $user_id 
                                  AND attempt_no = $next_attempt");
if (mysqli_num_rows($dupCheck) > 0) {
    header("Location: ../tests/view_results.php?error=already_inserted");
    exit();
}

// ✅ Chance label banao
function getChanceLabel($n) {
    $suffixes = ['th', 'st', 'nd', 'rd'];
    $v        = $n % 100;
    $suffix   = (isset($suffixes[($v - 20) % 10]))
                ? $suffixes[($v - 20) % 10]
                : (isset($suffixes[min($v, 3)])
                    ? $suffixes[min($v, 3)]
                    : $suffixes[0]);
    return $n . $suffix . " Chance";
}
$chance_label = getChanceLabel($next_attempt);

// ✅ Available sets fetch karo — filtered to this user's own job_role,
// so the admin can never accidentally assign a set belonging to a
// different role (e.g. giving a Tele Sales user a Team Leader set).
// Legacy users without a job_role fall back to seeing every set, exactly
// as before.
if (!empty($job_role)) {
    $job_role_esc = mysqli_real_escape_string($conn, $job_role);
    $setsRes = mysqli_query($conn, "SELECT DISTINCT set_id FROM questions WHERE job_role = '$job_role_esc' ORDER BY set_id");
} else {
    $setsRes = mysqli_query($conn, "SELECT DISTINCT set_id FROM questions ORDER BY set_id");
}

// ✅ Form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_chance'])) {
    $selected_set  = mysqli_real_escape_string($conn, $_POST['selected_set']);
    $uid           = (int) $_POST['uid'];
    $given_attempt = (int) $_POST['next_attempt'];

    // ✅ Double submit protection — phir se duplicate check
    $dupCheck2 = mysqli_query($conn, "SELECT id FROM user_attempts 
                                       WHERE user_id = $uid 
                                       AND attempt_no = $given_attempt");
    if (mysqli_num_rows($dupCheck2) === 0) {
        // ✅ Naya attempt row insert karo — job_role bhi save karo
        // (nullable, safe for legacy users without a job_role), matching
        // the same pattern take_test.php already uses.
        $job_role_esc = mysqli_real_escape_string($conn, $job_role);
        if ($job_role_esc !== '') {
            mysqli_query($conn, "INSERT INTO user_attempts (user_id, set_no, attempt_no, job_role) 
                                 VALUES ($uid, '$selected_set', $given_attempt, '$job_role_esc')");
        } else {
            mysqli_query($conn, "INSERT INTO user_attempts (user_id, set_no, attempt_no) 
                                 VALUES ($uid, '$selected_set', $given_attempt)");
        }
    }

    // ✅ Users table mein extra_attempt = 1 aur naya assigned_set save karo
    mysqli_query($conn, "UPDATE users 
                         SET extra_attempt = 1, assigned_set = '$selected_set' 
                         WHERE id = $uid");

    header("Location: ../tests/view_results.php?success=1");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Give Chance</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.12);
            padding: 35px 40px;
            width: 100%;
            max-width: 460px;
            text-align: center;
        }
        .card h2 { color: #2c3e50; margin-bottom: 8px; font-size: 22px; }
        .user-name { font-size: 16px; color: #555; margin-bottom: 6px; }
        .role-tag {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            border: 1px solid #c7d2fe;
            padding: 3px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 18px;
        }
        .chance-badge {
            background: #8e44ad;
            color: white;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 18px;
            font-weight: bold;
            display: inline-block;
            margin-bottom: 20px;
        }
        .info-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 22px;
            font-size: 14px;
            color: #856404;
            text-align: left;
        }
        .info-box span { font-weight: bold; }
        label {
            display: block;
            text-align: left;
            font-size: 14px;
            color: #333;
            margin-bottom: 6px;
            font-weight: bold;
        }
        select {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            margin-bottom: 22px;
            color: #333;
        }
        .btn-confirm {
            background: #27ae60;
            color: white;
            border: none;
            padding: 11px 30px;
            border-radius: 8px;
            font-size: 15px;
            cursor: pointer;
            width: 100%;
            font-weight: bold;
        }
        .btn-confirm:hover { background: #1e8449; }
        .btn-cancel {
            display: block;
            margin-top: 12px;
            color: #e74c3c;
            text-decoration: none;
            font-size: 13px;
        }
        .btn-cancel:hover { text-decoration: underline; }
        /* ✅ Error / Success Alert Box */
        .alert-box {
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
            text-align: left;
        }
        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
        }
        .no-sets-warning {
            background: #ffebee;
            border: 1px solid #ef9a9a;
            color: #c62828;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 12.5px;
            margin-bottom: 18px;
            text-align: left;
        }
    </style>
</head>
<body>
<div class="card">
    <h2>🎯 Give Another Chance</h2>
    <p class="user-name">👤 <b><?= htmlspecialchars($user_name) ?></b></p>
    <?php if ($roleLabel): ?>
        <div class="role-tag"><?= htmlspecialchars($roleLabel) ?></div>
    <?php endif; ?>

    <!-- Chance Badge -->
    <div class="chance-badge">
        🔄 Attempt <?= $next_attempt ?> (<?= $chance_label ?>)
    </div>

    <!-- Info Box -->
    <div class="info-box">
        ℹ️ You are giving <span><?= $chance_label ?></span> to this user.<br>
        Their next attempt will be <span>Attempt <?= $next_attempt ?></span>.
        <?php if ($roleLabel): ?>
            <br>Only <span><?= htmlspecialchars($roleLabel) ?></span> sets are shown below.
        <?php endif; ?>
    </div>

    <!-- Form -->
    <form method="POST">
        <input type="hidden" name="uid"            value="<?= $user_id ?>">
        <input type="hidden" name="next_attempt"   value="<?= $next_attempt ?>">
        <input type="hidden" name="confirm_chance" value="1">

        <label>📦 Assign Test Set:</label>
        <?php
        $setOptions = [];
        while ($sRow = mysqli_fetch_assoc($setsRes)) {
            $setOptions[] = $sRow['set_id'];
        }
        ?>
        <?php if (empty($setOptions)): ?>
            <div class="no-sets-warning">
                ⚠️ No question sets found for <?= htmlspecialchars($roleLabel ?? 'this role') ?>.
                Please add questions for this role before giving another chance.
            </div>
        <?php else: ?>
            <select name="selected_set">
                <?php foreach ($setOptions as $setId):
                    $sel = ($setId === $last_set) ? 'selected' : ''; ?>
                    <option value="<?= htmlspecialchars($setId) ?>" <?= $sel ?>>
                        <?= htmlspecialchars($setId) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        <?php endif; ?>

        <button type="submit" class="btn-confirm" <?= empty($setOptions) ? 'disabled' : '' ?>>
            ✅ Confirm — Give <?= $chance_label ?>
        </button>
        <a href="../tests/view_results.php" class="btn-cancel">✖ Cancel</a>
    </form>
</div>
</body>
</html>