<?php
// admin_test.php
include '../includes/db_user.php';
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Test Results</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .editable {
            cursor: pointer;
        }
        .editable:focus {
            background-color: #e2f0ff;
        }
    </style>
</head>
<body>
<div class="container mt-4">
    <h2 class="mb-4">Test Results </h2>

    <input type="text" id="search" class="form-control mb-3" placeholder="Search by name or email...">

    <table class="table table-bordered table-hover" id="resultsTable">
        <thead class="thead-dark">
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Score</th>
                <th>Total</th>
                <th>Submitted At</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $query = "SELECT * FROM test_results ORDER BY id DESC";
            $result = mysqli_query($conn, $query);
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<tr data-id='{$row['id']}'>
                    <td>{$row['id']}</td>
                    <td>{$row['name']}</td>
                    <td>{$row['email']}</td>
                    <td contenteditable='true' class='editable' data-field='score'>{$row['score']}</td>
                    <td>{$row['total']}</td>
                    <td>{$row['submitted_at']}</td>
                    <td><button class='btn btn-danger btn-sm deleteBtn'>Delete</button></td>
                </tr>";
            }
            ?>
        </tbody>
    </table>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Live Search
    $("#search").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $("#resultsTable tbody tr").filter(function () {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });

    // Inline Score Update
    $(".editable").on("blur", function () {
        var newValue = $(this).text();
        var id = $(this).closest("tr").data("id");

        $.ajax({
            url: "../tests/update_score.php",
            method: "POST",
            data: { id: id, score: newValue },
            success: function (response) {
                if (response.trim() !== "success") {
                    alert("Failed to update score.");
                }
            }
        });
    });

    // Delete Row
    $(".deleteBtn").on("click", function () {
        var row = $(this).closest("tr");
        var id = row.data("id");

        if (confirm("Are you sure you want to delete this result?")) {
            $.ajax({
                url: "../tests/delete_result.php",
                method: "POST",
                data: { id: id },
                success: function (response) {
                    if (response.trim() === "success") {
                        row.remove();
                    } else {
                        alert("Failed to delete result.");
                    }
                }
            });
        }
    });
</script>
</body>
</html>
