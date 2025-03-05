import sys
import cv2
import numpy as np
import face_recognition
import base64

def decode_face_encoding(encoded_str):
    """Decode base64 face encoding to a NumPy array."""
    try:
        decoded_bytes = base64.b64decode(encoded_str)
        face_encoding = np.frombuffer(decoded_bytes, dtype=np.float64)  # Ensure correct dtype
        return face_encoding
    except Exception as e:
        print(f"Decoding Error: {e}")
        return None

if len(sys.argv) != 3:
    print("ERROR: Invalid number of arguments")
    sys.exit(1)

image_path = sys.argv[1]
face_encoding_base64 = sys.argv[2]

# Load captured image
captured_image = cv2.imread(image_path)
if captured_image is None:
    print("ERROR: Failed to load image")
    sys.exit(1)

# Convert image to RGB
rgb_image = cv2.cvtColor(captured_image, cv2.COLOR_BGR2RGB)

# Detect face and encode
face_locations = face_recognition.face_locations(rgb_image)
if len(face_locations) == 0:
    print("ERROR: No face detected in captured image")
    sys.exit(1)

captured_encoding = face_recognition.face_encodings(rgb_image, face_locations)[0]  # Extract first face
print(f"Captured Encoding Length: {len(captured_encoding)}")

# Decode stored encoding from Base64
stored_encoding = decode_face_encoding(face_encoding_base64)
if stored_encoding is None or len(stored_encoding) != 128:
    print("ERROR: Invalid stored encoding")
    sys.exit(1)

print(f"Decoded Encoding Length: {len(stored_encoding)}")

# Debugging: Print first 5 values of both encodings for comparison
print(f"Stored Encoding Sample: {stored_encoding[:5]}")
print(f"Captured Encoding Sample: {captured_encoding[:5]}")

# Compare face encodings
match = face_recognition.compare_faces([stored_encoding], captured_encoding, tolerance=0.5)  # Adjust tolerance if needed

if match[0]:
    print("Match Found")
else:
    print("No Match Found")
