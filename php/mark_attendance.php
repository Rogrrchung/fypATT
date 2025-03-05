<?php
include 'db.php';

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

$faceImage = $_POST['face_image'];  // Get base64 image
$courseId = $_POST['course_id']; // Course ID from frontend

// Validate input
if (empty($faceImage) || empty($courseId)) {
    die("ERROR: Missing required data.");
}

// Ensure the uploads directory exists
$uploadDir = "../uploads/";
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

// Convert base64 to an image file
$imageData = base64_decode(str_replace("data:image/png;base64,", "", $faceImage));
$imagePath = $uploadDir . uniqid() . ".png";

if (!file_put_contents($imagePath, $imageData)) {
    die("ERROR: Failed to save image.");
}

// Fetch all stored face encodings from database
$query = "SELECT student_id, face_encoding FROM students";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Database query failed - " . mysqli_error($conn));
}

$matchedUser = null;

// Set correct Python path
$pythonPath = "C:/Users/G3-15/AppData/Local/Programs/Python/Python312/python.exe";
$scriptPath = realpath("../python/verify_face.py");

while ($row = mysqli_fetch_assoc($result)) {
    $dbFaceEncoding = json_encode(json_decode($row['face_encoding'])); // Ensure valid JSON
    $dbFaceEncodingEscaped = escapeshellarg($dbFaceEncoding);

    // Run Python script to compare faces
    $command = escapeshellcmd("$pythonPath $scriptPath $imagePath $dbFaceEncodingEscaped");
    $matchResult = shell_exec($command);

    // Debugging
    file_put_contents("debug_log.txt", "Python Output: " . $matchResult . PHP_EOL, FILE_APPEND);

    if (strpos($matchResult, "Match Found") !== false) {
        $matchedUser = $row;
        break;
    }
}

// ✅ If face is recognized, mark attendance
if ($matchedUser) {
    $studentId = $matchedUser['student_id'];

    // ✅ Prevent duplicate attendance for the same student on the same day
    $checkQuery = "SELECT * FROM attendance WHERE student_id = ? AND course_id = ? AND DATE(timestamp) = CURDATE()";
    $stmt = mysqli_prepare($conn, $checkQuery);
    mysqli_stmt_bind_param($stmt, "ii", $studentId, $courseId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        echo "ERROR: Attendance already marked today.";
        exit;
    }

    // ✅ Insert attendance record
    $insertQuery = "INSERT INTO attendance (student_id, course_id, timestamp) VALUES (?, ?, NOW())";
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "ii", $studentId, $courseId);

    if (mysqli_stmt_execute($stmt)) {
        echo "Attendance Marked Successfully!";
    } else {
        echo "ERROR: Could not mark attendance - " . mysqli_error($conn);
    }
} else {
    echo "ERROR: Face not recognized. Try again.";
}

// Close the database connection
mysqli_close($conn);
?>
