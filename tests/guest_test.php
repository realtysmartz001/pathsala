<?php
session_start();
include '../includes/db_connect.php';

$token = trim($_GET['token'] ?? '');

// ── Helper: render a simple error/status card and stop ──
function renderGuestMessage($icon, $title, $message, $isError = true)
{
    ?>
    <!DOCTYPE html>
    <html lang="en" data-theme="dark">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Guest Test Link</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
        <link rel="stylesheet" href="../assets/css/style.css">
        <link rel="stylesheet" href="../assets/css/layout.css">
        <style>
            body {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--bg-primary, #0d0d1a);
                font-family: 'Segoe UI', sans-serif;
                padding: 20px;
            }

            .msg-card {
                background: var(--card-bg, rgba(255, 255, 255, 0.07));
                border: 1px solid var(--border-color, rgba(255, 255, 255, 0.12));
                border-radius: 20px;
                padding: 44px 36px;
                max-width: 440px;
                width: 100%;
                text-align: center;
                color: var(--text-primary, #fff);
                box-shadow: 0 8px 40px rgba(0, 0, 0, 0.25);
            }

            .msg-icon {
                font-size: 48px;
                margin-bottom: 18px;
                color:
                    <?php echo $isError ? '#e74c3c' : '#c9a84c'; ?>
                ;
            }

            .msg-card h2 {
                font-family: 'Playfair Display', serif;
                font-size: 1.4rem;
                margin-bottom: 10px;
            }

            .msg-card p {
                color: var(--text-secondary, #aaa);
                font-size: 0.95rem;
                line-height: 1.6;
            }
        </style>
    </head>

    <body>
        <div class="msg-card">
            <div class="msg-icon"><i class="bi <?php echo htmlspecialchars($icon); ?>"></i></div>
            <h2><?php echo htmlspecialchars($title); ?></h2>
            <p><?php echo htmlspecialchars($message); ?></p>
        </div>
    </body>

    </html>
    <?php
    exit();
}

// ── Step 1: token must be present ──
if (empty($token)) {
    renderGuestMessage('bi-link-45deg', 'Invalid Link', 'This test link is missing a valid token. Please check the link you were given and try again.');
}

// ── Step 2: token must exist ──
$tokenEsc = mysqli_real_escape_string($conn, $token);
$linkRes = mysqli_query($conn, "SELECT * FROM guest_test_links WHERE token = '$tokenEsc' LIMIT 1");
$link = $linkRes ? mysqli_fetch_assoc($linkRes) : null;

if (!$link) {
    renderGuestMessage('bi-x-octagon', 'Link Not Found', 'This test link does not exist or may have been removed. Please contact the person who shared this link with you.');
}

// ── Step 3: not disabled ──
if ((int) $link['is_disabled'] === 1) {
    renderGuestMessage('bi-slash-circle', 'Link Disabled', 'This test link has been disabled by the administrator. Please contact them for a new link.');
}

// ── Step 4: not expired ──
if (strtotime($link['expiry_date']) < time()) {
    renderGuestMessage('bi-hourglass-bottom', 'Link Expired', 'This test link has expired. Please contact the administrator for a new link.');
}

// ── Step 5: attempts remaining ──
$linkedUserId = !empty($link['linked_user_id']) ? (int) $link['linked_user_id'] : null;
$attemptsUsed = 0;
if ($linkedUserId) {
    $attRes = mysqli_query($conn, "SELECT COUNT(*) AS c FROM user_attempts WHERE user_id = $linkedUserId");
    $attemptsUsed = (int) (mysqli_fetch_assoc($attRes)['c'] ?? 0);
}
if ($attemptsUsed >= (int) $link['max_attempts']) {
    renderGuestMessage('bi-flag', 'No Attempts Remaining', 'You have already used all available attempts for this test link.', false);
}

// ── Step 6: provision (or reuse) the candidate's users row ──
// If this exact candidate email already has a users row (e.g. they used
// a regenerated link, or the admin re-created a link for a returning
// candidate), reuse that same row so their attempt history stays
// together — otherwise create a new one.
// If the candidate didn't provide an email, generate a unique
// placeholder tied to this specific link's token, so multiple
// no-email guests never collide on an empty-string lookup and
// accidentally share the same users row/attempt history.
$candidateEmail = trim($link['candidate_email'] ?? '');
if ($candidateEmail === '') {
    $candidateEmail = 'guest_' . $link['token'] . '@noemail.local';
}
$emailEsc = mysqli_real_escape_string($conn, $candidateEmail);
if ($linkedUserId) {
    // Already provisioned previously — just refresh their role/set for
    // this specific test link (in case admin edited the link since).
    $roleEsc = mysqli_real_escape_string($conn, $link['job_role']);
    $setEsc = mysqli_real_escape_string($conn, $link['set_id']);
    mysqli_query($conn, "UPDATE users SET job_role = '$roleEsc', assigned_set = '$setEsc' WHERE id = $linkedUserId");
    $userId = $linkedUserId;
} else {
    // Reuse an existing users row for this exact email if one exists
    $existingRes = mysqli_query($conn, "SELECT id FROM users WHERE email = '$emailEsc' LIMIT 1");
    $existing = $existingRes ? mysqli_fetch_assoc($existingRes) : null;

    if ($existing) {
        $userId = (int) $existing['id'];
        $roleEsc = mysqli_real_escape_string($conn, $link['job_role']);
        $setEsc = mysqli_real_escape_string($conn, $link['set_id']);
        mysqli_query($conn, "UPDATE users SET job_role = '$roleEsc', assigned_set = '$setEsc', is_guest = 1, guest_link_id = {$link['id']} WHERE id = $userId");
    } else {
        $nameEsc = mysqli_real_escape_string($conn, $link['candidate_name']);
        $phoneEsc = mysqli_real_escape_string($conn, $link['candidate_phone']);
        $roleEsc = mysqli_real_escape_string($conn, $link['job_role']);
        $setEsc = mysqli_real_escape_string($conn, $link['set_id']);
        $placeholderPassword = bin2hex(random_bytes(16)); // never used to log in — guests never authenticate with a password

        mysqli_query($conn, "INSERT INTO users
            (name, email, password, is_verified, is_approved, job_role, assigned_set, is_guest, guest_link_id)
            VALUES ('$nameEsc', '$emailEsc', '$placeholderPassword', 1, 1, '$roleEsc', '$setEsc', 1, {$link['id']})");
        $userId = mysqli_insert_id($conn);
    }

    // Persist the link between this guest_test_links row and the users row
    mysqli_query($conn, "UPDATE guest_test_links SET linked_user_id = $userId WHERE id = {$link['id']}");
}

// Store candidate's phone on the session too — used nowhere in existing
// code, kept only in case future pages want it; does not affect
// take_test.php or submit_test.php, which never read this key.
$_SESSION['guest_candidate_phone'] = $link['candidate_phone'];

// ── Step 7: log the candidate in exactly like a normal registered user ──
// From this point on, take_test.php's existing registered-user branch
// takes over completely, unmodified.
$_SESSION['user_id'] = $userId;
$_SESSION['user_name'] = $link['candidate_name'];
$_SESSION['user_email'] = $candidateEmail;

header("Location: ../tests/take_test.php");
exit();