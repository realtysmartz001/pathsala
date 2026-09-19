<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
include '../includes/db_connect.php';

$success = 0;
$errors = [];
$message = "";

if (isset($_POST['uploadCSV'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "❌ File upload failed!";
    } elseif (pathinfo($file['name'], PATHINFO_EXTENSION) !== 'csv') {
        $message = "❌ Sirf CSV file allowed hai!";
    } else {
        $handle = fopen($file['tmp_name'], 'r');
        $header = fgetcsv($handle); // header skip

        while (($row = fgetcsv($handle)) !== false) {
            if (count($row) < 8) {
                $errors[] = "⚠️ Row skip hui: " . implode(", ", $row);
                continue;
            }

            $set_name       = mysqli_real_escape_string($conn, trim($row[0]));
            $set_id         = mysqli_real_escape_string($conn, trim($row[1]));
            $question_text  = mysqli_real_escape_string($conn, trim($row[2]));
            $option_a       = mysqli_real_escape_string($conn, trim($row[3]));
            $option_b       = mysqli_real_escape_string($conn, trim($row[4]));
            $option_c       = mysqli_real_escape_string($conn, trim($row[5]));
            $option_d       = mysqli_real_escape_string($conn, trim($row[6]));
            $correct_option = mysqli_real_escape_string($conn, strtoupper(trim($row[7])));

            if (!in_array($correct_option, ['A','B','C','D'])) {
                $errors[] = "⚠️ Invalid answer '$correct_option' for: $question_text";
                continue;
            }

            $query = "INSERT INTO questions 
                      (set_name, set_id, question_text, option_a, option_b, option_c, option_d, correct_option) 
                      VALUES 
                      ('$set_name','$set_id','$question_text','$option_a','$option_b','$option_c','$option_d','$correct_option')";

            if (mysqli_query($conn, $query)) {
                $success++;
            } else {
                $errors[] = "❌ DB Error: " . mysqli_error($conn);
            }
        }

        fclose($handle);
        $message = "✅ $success questions successfully upload ho gayi!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Bulk Upload</title>
    <style>
        body { font-family: Arial; background: #f0f4ff; }
        .box {
            width: 60%; margin: 50px auto;
            background: white; padding: 30px;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        h2 { color: #007bff; }
        input[type="file"] {
            display: block; margin: 15px 0;
            padding: 10px;
            border: 2px dashed #007bff;
            border-radius: 8px; width: 100%;
        }
        button {
            background: #007bff; color: white;
            padding: 12px 25px; border: none;
            border-radius: 8px; cursor: pointer;
            font-size: 15px;
        }
        button:hover { background: #0056b3; }
        .success { color: green; font-weight: bold; font-size: 18px; }
        .error-box {
            background: #fff3cd; border: 1px solid #ffc107;
            border-radius: 8px; padding: 10px; margin-top: 15px;
        }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 13px; }
        th { background: #007bff; color: white; padding: 8px; }
        td { border: 1px solid #ddd; padding: 8px; }
        tr:nth-child(even) { background: #f9f9f9; }
    </style>
</head>
<body>
<div class="box">
    <h2>📥 Bulk Question Upload</h2>

    <h4>📋 CSV mein yeh format follow karo:</h4>
    <table>
        <tr>
            <th>set_name</th><th>set_id</th><th>question_text</th>
            <th>option_a</th><th>option_b</th><th>option_c</th>
            <th>option_d</th><th>correct_option</th>
        </tr>
        <tr>
            <td>Real Estate Basics</td><td>rs001</td>
            <td>RERA ka full form?</td>
            <td>Real Estate Reg. Auth.</td><td>Real Estate Act</td>
            <td>Revenue Authority</td><td>Rental Act</td>
            <td>A</td>
        </tr>
    </table>

    <br>
    <form method="post" enctype="multipart/form-data">
        <label><b>📂 CSV File Select Karo:</b></label>
        <input type="file" name="csv_file" accept=".csv" required>
        <button type="submit" name="uploadCSV">📤 Upload Karo</button>
    </form>

    <?php if (!empty($message)): ?>
        <p class="success"><?php echo $message; ?></p>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
        <div class="error-box">
            <b>⚠️ Errors:</b>
            <?php foreach($errors as $e): ?>
                <p><?php echo $e; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
</body>
</html>
