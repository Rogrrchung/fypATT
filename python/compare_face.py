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

def get_student_faces():
    """Retrieve all stored face encodings from the database."""
    cursor.execute("SELECT student_id, face_encoding FROM students")
    results = cursor.fetchall()
    student_encodings = {}

    for student_id, encoding in results:
        if encoding:  # Ensure encoding is not NULL
            np_encoding = np.frombuffer(encoding, dtype=np.float64)
            student_encodings[student_id] = np_encoding

    return student_encodings

def compare_faces(image_path):
    """Compare a given image with stored student faces."""
    student_faces = get_student_faces()

    # Load input image
    input_image = face_recognition.load_image_file(image_path)
    input_encodings = face_recognition.face_encodings(input_image)

    if not input_encodings:
        print("No face found in the input image.")
        return None

    input_encoding = input_encodings[0]  # Use the first detected face

    for student_id, stored_encoding in student_faces.items():
        match = face_recognition.compare_faces([stored_encoding], input_encoding, tolerance=0.5)
        if match[0]:  
            print(f"Match found: Student ID {student_id}")
            return student_id

    print("No match found.")
    return None

if __name__ == "__main__":
    image_path = "test_image.jpg"  # Change this to the actual image path
    matched_id = compare_faces(image_path)

    if matched_id:
        print(f"Face matched with Student ID: {matched_id}")
    else:
        print("No match found.")
