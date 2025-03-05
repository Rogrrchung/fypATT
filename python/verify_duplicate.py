import sys
import numpy as np
import face_recognition
import base64

if len(sys.argv) != 3:
    print("Error: Missing encodings.")
    sys.exit(1)

try:
    new_encoding = np.frombuffer(base64.b64decode(sys.argv[1]), dtype=np.float64)
    existing_encoding = np.frombuffer(base64.b64decode(sys.argv[2]), dtype=np.float64)

    if new_encoding.shape[0] != 128 or existing_encoding.shape[0] != 128:
        print("Error: Encoding length incorrect.")
        sys.exit(1)

    # Compare faces (lower tolerance for stricter matching)
    match = face_recognition.compare_faces([existing_encoding], new_encoding, tolerance=0.5)

    if match[0]:
        print("Match Found")
    else:
        print("No Match Found")

except Exception as e:
    print(f"Error: {e}")
    sys.exit(1)
