<?php
session_start();
require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

if (!isset($_SESSION['role']) || $_SESSION['role'] !== "admin") {
    header("Location: ../auth/login.php");
    exit();
}

$jobRoleLabels = [
    'tele_sales' => 'Tele Sales',
    'sales_consultant' => 'Sales Consultant',
    'team_leader' => 'Team Leader',
    'others' => 'Others',
];

// Respect the same job_role filter as admin_user.php, if one was applied
$roleFilter = trim($_GET['job_role'] ?? '');
if ($roleFilter !== '' && array_key_exists($roleFilter, $jobRoleLabels)) {
    $roleFilterEsc = mysqli_real_escape_string($conn, $roleFilter);
    $sql = "SELECT u.* FROM users u WHERE u.job_role = '$roleFilterEsc' ORDER BY u.created_at DESC";
} else {
    $sql = "SELECT u.* FROM users u ORDER BY u.created_at DESC";
}
$result = $conn->query($sql);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('All Users');

// ── Header row ──
$headers = ['#', 'Name', 'Email', 'Assigned Set', 'Job Role', 'Custom Job Role', 'Verified', 'Approved', 'Created At'];
$sheet->fromArray($headers, null, 'A1');

$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => 'C9933A'],
    ],
    'borders' => [
        'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B8860B']],
    ],
];
$sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

// ── Data rows ──
$rowNum = 2;
$i = 1;
while ($row = $result->fetch_assoc()) {
    $roleKey = $row['job_role'] ?? '';
    $roleDisp = $jobRoleLabels[$roleKey] ?? ($roleKey !== '' ? $roleKey : '—');
    $verified = $row['is_verified'] ? 'Yes' : 'No';
    $approved = $row['is_approved'] ? 'Yes' : 'No';
    $customRole = $row['custom_job_role'] ?? '';

    $sheet->fromArray([
        $i,
        $row['name'],
        $row['email'],
        $row['assigned_set'] ?: '—',
        $roleDisp,
        $customRole !== '' ? $customRole : '—',
        $verified,
        $approved,
        $row['created_at'],
    ], null, 'A' . $rowNum);

    $rowNum++;
    $i++;
}

// ── Column widths ──
foreach (range('A', 'I') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// ── Borders on data rows ──
$lastRow = $rowNum - 1;
if ($lastRow >= 2) {
    $sheet->getStyle('A2:I' . $lastRow)->applyFromArray([
        'borders' => [
            'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E0E0E0']],
        ],
    ]);
}

$filename = 'All_Users_Report_' . date('Y-m-d') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();