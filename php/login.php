<?php
include 'db.php';

// Ensure session is started
if (!isset($_SESSION)) {
    session_start();
}

$faceImage = $_POST['face_image'] ?? '';

// Validate input
if (empty($faceImage)) {
    die("ERROR: No face image received.");
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

if (!file_exists($imagePath)) {
    die("ERROR: Image file was not saved correctly at $imagePath.");
}

// Fetch all stored face encodings from the database
$query = "SELECT student_id, username, face_encoding FROM students";
$result = mysqli_query($conn, $query);

if (!$result) {
    die("ERROR: Database query failed - " . mysqli_error($conn));
}

$matchedUser = null;
$pythonPath = "C:/Users/G3-15/AppData/Local/Programs/Python/Python312/python.exe";
$scriptPath = realpath("../python/verify_face.py");

while ($row = mysqli_fetch_assoc($result)) {
    $studentId = $row['student_id'];
    $username = $row['username'];
    $faceEncodingBlob = $row['face_encoding'];

    if (empty($faceEncodingBlob)) {
        file_put_contents("debug_log.txt", "Error: No face encoding found for Student ID $studentId\n", FILE_APPEND);
        continue;
    }

    // Convert BLOB to Base64
    $faceEncodingBase64 = base64_encode($faceEncodingBlob);

    // Debugging: Log encoding length
    file_put_contents("debug_log.txt", "Face Encoding Length: " . strlen($faceEncodingBlob) . " for Student ID: $studentId\n", FILE_APPEND);

    // Fix: Wrap Base64 in double quotes to prevent argument parsing errors
    $command = "$pythonPath $scriptPath \"$imagePath\" \"$faceEncodingBase64\"";
    $matchResult = shell_exec($command);

    // Debugging: Log Python output
    file_put_contents("debug_log.txt", "Python Output: " . trim($matchResult) . PHP_EOL, FILE_APPEND);

    if (!empty($matchResult) && strpos($matchResult, "Match Found") !== false) {
        $matchedUser = $row;
        break;
    }
}

// Fix: Only log in if $matchedUser is set
if ($matchedUser !== null) {
    $_SESSION['user_id'] = $matchedUser['student_id'];
    $_SESSION['username'] = $matchedUser['username'];
    echo "Login Successful! Welcome " . htmlspecialchars($matchedUser['username']);
} else {
    echo "ERROR: Face not recognized. Try again.";
}

mysqli_close($conn);
?>
