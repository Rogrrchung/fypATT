<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $student_id = $_POST['student_id'];
    $username = $_POST['username'];
    $password = $_POST['password'];
    $contact_number = $_POST['contact_number'];
    $faceImage = $_POST['face_image'] ?? '';

    if (empty($username) || empty($password) || empty($contact_number) || empty($faceImage)) {
        die("ERROR: All fields are required.");
    }

    // Hash the password before storing it
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

    // Convert base64 to an image file
    $uploadDir = "../uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $imageData = base64_decode(str_replace("data:image/png;base64,", "", $faceImage));
    $imagePath = $uploadDir . uniqid() . ".png";

    if (!file_put_contents($imagePath, $imageData)) {
        die("ERROR: Failed to save image.");
    }

    // Encode the face using Python script
    $pythonPath = "C:/Users/G3-15/AppData/Local/Programs/Python/Python312/python.exe";
    $scriptPath = realpath("../python/encode_face.py");
    $command = escapeshellcmd("$pythonPath $scriptPath $imagePath");
    $newFaceEncoding = shell_exec($command);

    if (!$newFaceEncoding || strlen($newFaceEncoding) < 10) {
        die("ERROR: Face encoding failed.");
    }

    // Convert encoding to BLOB
    $newFaceEncodingBlob = base64_decode($newFaceEncoding);

    // Fetch all stored face encodings
    $query = "SELECT student_id, username, face_encoding FROM students";
    $result = mysqli_query($conn, $query);

    if (!$result) {
        die("ERROR: Database query failed - " . mysqli_error($conn));
    }

    $scriptPathVerify = realpath("../python/verify_duplicate.py");

    while ($row = mysqli_fetch_assoc($result)) {
        $existingEncodingBase64 = base64_encode($row['face_encoding']);

        // Run Python script to check if face matches any stored face
        $commandVerify = escapeshellcmd("$pythonPath $scriptPathVerify $newFaceEncoding $existingEncodingBase64");
        $matchResult = shell_exec($commandVerify);

        if (strpos($matchResult, "Match Found") !== false) {
            die("ERROR: A user with this face is already registered.");
        }
    }

    // Insert into the database
    $stmt = $conn->prepare("INSERT INTO students (student_id, username, password, contact_number, face_encoding) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $student_id, $username, $hashed_password, $contact_number, $newFaceEncodingBlob);

    if ($stmt->execute()) {
        echo "Registration Successful!";
    } else {
        echo "ERROR: " . $stmt->error;
    }

    $stmt->close();
    mysqli_close($conn);
}
?>
