import cv2
import numpy as np
import face_recognition
import mysql.connector

# Database connection
db = mysql.connector.connect(
    host="localhost",
    user="root",
    password="",
    database="attendance_system"
)
cursor = db.cursor()

def encode_and_store_face(student_id, image_path):
    """Encodes a student's face from an image and stores it in the database."""
    print(f"Processing Student ID: {student_id}")

    # Read the image
    frame = cv2.imread(image_path)
    if frame is None:
        print("Error: Could not load image.")
        return False

    # Convert frame to RGB
    rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

    # Detect face and encode it
    encodings = face_recognition.face_encodings(rgb_frame)

    if not encodings:
        print("Error: No face detected in the image.")
        return False

    face_encoding = encodings[0]  # First detected face
    binary_encoding = face_encoding.tobytes()

    if not binary_encoding:
        print("Error: Encoding failed.")
        return False

    try:
        # Check if student exists
        cursor.execute("SELECT student_id FROM students WHERE student_id = %s", (student_id,))
        if cursor.fetchone() is None:
            print(f"Error: Student ID {student_id} not found in database.")
            return False

        # Store face encoding in the database
        cursor.execute("UPDATE students SET face_encoding = %s WHERE student_id = %s", (binary_encoding, student_id))
        db.commit()

        print(f"✅ Success: Face encoding stored for Student ID {student_id}")
        return True

    except mysql.connector.Error as err:
        print(f"❌ Database Error: {err}")
        return False

if __name__ == "__main__":
    import sys
    if len(sys.argv) != 3:
        print("Usage: python encode_face.py <image_path> <student_id>")
        sys.exit(1)

    image_path = sys.argv[1]
    student_id = sys.argv[2]

    result = encode_and_store_face(student_id, image_path)

    # Debugging: Save logs
    with open("debug_log.txt", "a") as log_file:
        log_file.write(f"Student ID: {student_id}, Image: {image_path}, Result: {result}\n")
