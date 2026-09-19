<?php
session_start();
include '../includes/db_connect.php';

// ✅ Sirf admin hi access kare
if(!isset($_SESSION['role']) || $_SESSION['role'] !== "admin"){
    header("Location: ../auth/login.php");
    exit();
}

// ✅ User ID from URL
if(!isset($_GET['id'])){
    die("User ID required.");
}
$user_id = intval($_GET['id']);

// ✅ Profile fetch
$query = "SELECT * FROM user_profiles WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$profile = $result->fetch_assoc();

if(!$profile){
    die("Profile not found.");
}

// Build a root-relative URL for uploaded documents/photos. Handles every
// historical storage format (bare filename, "uploads/filename", a full
// filesystem path) as well as the newer per-user "folder/filename" format,
// so both old and new records display correctly.
function docURL($filename)
{
    if (empty($filename)) return '';
    if (str_starts_with($filename, 'http')) return $filename;
    $normalized = str_replace('\\', '/', $filename);
    $pos = strripos($normalized, '/uploads/');
    if ($pos !== false) {
        $normalized = substr($normalized, $pos + strlen('/uploads/'));
    } elseif (str_starts_with($normalized, 'uploads/')) {
        $normalized = substr($normalized, strlen('uploads/'));
    }
    return '/uploads/' . ltrim($normalized, '/');
}

// ✅ Profile photo
$profilePhoto = !empty($profile['profile_photo']) ? docURL($profile['profile_photo']) : "/assets/img/default-user.png";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin | View Profile</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .profile-card { text-align: center; padding: 20px; }
    .profile-card img {
      width: 150px; height: 150px;
      border-radius: 50%; border: 4px solid #0d6efd;
      object-fit: cover; margin-bottom: 15px;
    }
    .tab-pane .field { margin-bottom: 12px; }
    .field strong { width: 180px; display: inline-block; color: #333; }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="row g-4">
    
    <!-- Sidebar -->
    <div class="col-lg-3">
      <div class="card profile-card shadow-sm">
        <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Profile Photo">
        <h4 class="mb-1"><?php echo htmlspecialchars($profile['user_name']); ?></h4>
        <p class="mb-1 text-muted"><?php echo htmlspecialchars($profile['email']); ?></p>
        <p class="mb-1"><strong>Contact:</strong> <?php echo htmlspecialchars($profile['contact_no']); ?></p>
        <p class="mb-2"><strong>Experience:</strong> <?php echo ucfirst($profile['experience']); ?></p>
        <a href="../admin/admin_profiles.php" class="btn btn-secondary btn-sm">⬅ Back to List</a>
      </div>
    </div>
    
    <!-- Content -->
    <div class="col-lg-9">
      <div class="card shadow-sm">
        <div class="card-body">
          <!-- Tabs -->
          <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#personal">Personal</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#kyc">KYC</button></li>
            <?php if($profile['experience'] == 'experienced'): ?>
              <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#experience">Experience</button></li>
            <?php endif; ?>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#education">Education</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#emergency">Emergency</button></li>
          </ul>

          <div class="tab-content pt-3">
            
            <!-- Personal -->
            <div class="tab-pane fade show active" id="personal">
              <h5>Personal Information</h5>
              <div class="field"><strong>DOB:</strong> <?php echo htmlspecialchars($profile['dob']); ?></div>
              <div class="field"><strong>Current Address:</strong> <?php echo htmlspecialchars($profile['current_address']); ?></div>
              <div class="field"><strong>Permanent Address:</strong> <?php echo htmlspecialchars($profile['permanent_address']); ?></div>
            </div>

            <!-- KYC -->
            <div class="tab-pane fade" id="kyc">
              <h5>KYC & Bank</h5>
              <div class="field"><strong>Aadhar:</strong> <?php if($profile['aadhar_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['aadhar_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
              <div class="field"><strong>PAN:</strong> <?php if($profile['pan_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['pan_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
              <div class="field"><strong>Cheque:</strong> <?php if($profile['cheque_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['cheque_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
              <div class="field"><strong>Passbook:</strong> <?php if($profile['passbook_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['passbook_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
            </div>

            <!-- Experience -->
            <?php if($profile['experience'] == 'experienced'): ?>
            <div class="tab-pane fade" id="experience">
              <h5>Experience</h5>
              <div class="field"><strong>Offer Letter:</strong> <?php if($profile['offer_letter_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['offer_letter_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
              <div class="field"><strong>Relieving Letter:</strong> <?php if($profile['relieving_letter_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['relieving_letter_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
              <div class="field"><strong>Salary Slip:</strong> <?php if($profile['salary_slip_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['salary_slip_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
              <div class="field"><strong>Rehire Mail:</strong> <?php if($profile['up_rehire_mail_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['up_rehire_mail_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
            </div>
            <?php endif; ?>

            <!-- Education -->
            <div class="tab-pane fade" id="education">
              <h5>Education</h5>
              <div class="field"><strong>Qualification:</strong> <?php echo htmlspecialchars($profile['education']); ?></div>
              <div class="field"><strong>Marksheet:</strong> <?php if($profile['marksheet_doc']): ?><a class="btn btn-info btn-sm" href="<?php echo docURL($profile['marksheet_doc']); ?>" target="_blank">View</a><?php endif; ?></div>
            </div>

            <!-- Emergency -->
            <div class="tab-pane fade" id="emergency">
              <h5>Emergency Contact</h5>
              <?php if($profile['contact_relation'] == 'father'): ?>
                <div class="field"><strong>Father's Name:</strong> <?php echo htmlspecialchars($profile['father_name']); ?></div>
                <div class="field"><strong>Father's Contact:</strong> <?php echo htmlspecialchars($profile['father_contact']); ?></div>
                <div class="field"><strong>Father's Address:</strong> <?php echo htmlspecialchars($profile['father_address']); ?></div>
              <?php elseif($profile['contact_relation'] == 'mother'): ?>
                <div class="field"><strong>Mother's Name:</strong> <?php echo htmlspecialchars($profile['mother_name']); ?></div>
                <div class="field"><strong>Mother's Contact:</strong> <?php echo htmlspecialchars($profile['mother_contact']); ?></div>
                <div class="field"><strong>Mother's Address:</strong> <?php echo htmlspecialchars($profile['mother_address']); ?></div>
              <?php elseif($profile['contact_relation'] == 'other'): ?>
                <div class="field"><strong>Name:</strong> <?php echo htmlspecialchars($profile['other_name']); ?></div>
                <div class="field"><strong>Relation:</strong> <?php echo htmlspecialchars($profile['other_relation']); ?></div>
                <div class="field"><strong>Contact:</strong> <?php echo htmlspecialchars($profile['other_contact']); ?></div>
                <div class="field"><strong>Address:</strong> <?php echo htmlspecialchars($profile['other_address']); ?></div>
              <?php endif; ?>
            </div>

          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>