import mysql.connector
import time
import serial

# Setup the Serial connection to Arduino (Change COM3 to your actual port)
try:
    arduino = serial.Serial('COM3', 9600, timeout=1)  # Change COM port if needed
    print("Connected to Arduino. Waiting for RFID scans...")
except serial.SerialException:
    print("Error: Could not connect to Arduino. Check the COM port and try again.")
    arduino = None

def connect_to_db():
    try:
        conn = mysql.connector.connect(
            host='localhost',   # XAMPP default host
            user='root',        # XAMPP default user
            password='',        # No password for XAMPP
            database='dbrfid',  # Your provided database name
            port=3306           # MySQL default port
        )
        return conn
    except mysql.connector.Error as err:
        print(f"Database Error: {err}")
        return None

def check_rfid_access(card_id, gate):
    conn = connect_to_db()
    if conn is None:
        return False

    cursor = conn.cursor(dictionary=True)
    query = """
    SELECT students.id, students.lrn, students.name, grades.name AS grade, sections.name AS section
    FROM students
    JOIN grades ON students.grade_id = grades.id
    JOIN sections ON students.section_id = sections.id
    WHERE students.rfid = %s
    """
    cursor.execute(query, (card_id,))
    student = cursor.fetchone()

    if student:
        print(f"Student Details: LRN: {student['lrn']}, Name: {student['name']}, Grade: {student['grade']}, Section: {student['section']}")
        action = 'IN' if not has_recent_entry(student['id']) else 'OUT'
        log_entry(student['id'], gate, action)
        conn.close()
        return action

    conn.close()
    return None

def has_recent_entry(student_id):
    conn = connect_to_db()
    if conn is None:
        return False
    cursor = conn.cursor()
    query = "SELECT action FROM logs WHERE student_id = %s ORDER BY timestamp DESC LIMIT 1"
    cursor.execute(query, (student_id,))
    last_action = cursor.fetchone()
    conn.close()
    return last_action and last_action[0] == 'IN'

def log_entry(student_id, gate, action):
    conn = connect_to_db()
    if conn is None:
        return
    cursor = conn.cursor()
    query = "INSERT INTO logs (student_id, gate, action, timestamp) VALUES (%s, %s, %s, NOW())"
    cursor.execute(query, (student_id, gate, action))
    conn.commit()
    conn.close()

def read_rfid_from_arduino():
    """Reads RFID data from Arduino serial."""
    if not arduino:
        print("No Arduino connection.")
        return None
    
    while True:
        card_id = arduino.readline().decode().strip()
        if card_id:
            print(f"Scanned RFID: {card_id}")
            return card_id  # Return the scanned RFID

def gate_control_system():
    print("RFID Two-Gate Entrance System Simulation")
    while True:
        gate = input("Select Gate (1 or 2): ")
        if gate not in ['1', '2']:
            print("Invalid gate selection. Please enter 1 or 2.")
            continue
        
        print("Waiting for RFID scan...")
        card_id = read_rfid_from_arduino()
        if not card_id:
            print("No RFID detected. Try again.")
            continue
        
        action = check_rfid_access(card_id, f"Gate {gate}")
        if action == 'IN':
            print(f"IN recorded at Gate {gate}.")
        elif action == 'OUT':
            print(f"OUT recorded at Gate {gate}.")
        else:
            print("Access Denied. Please contact admin.")

        time.sleep(2)

if __name__ == "__main__":
    gate_control_system()
