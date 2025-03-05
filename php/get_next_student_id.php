<?php
include 'db.php';

$query = "SELECT MAX(student_id) + 1 AS next_id FROM students";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
echo $row['next_id'] ?? 1;

mysqli_close($conn);
?>
