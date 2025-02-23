import mysql.connector
import time

def connect_to_db():
    try:
        conn = mysql.connector.connect(
            host='localhost',   # XAMPP default host
            user='root',        # XAMPP default user
            password='',        # XAMPP default has no password
            database='dbrfid',  # Your provided database name
            port=3306           # MySQL default port
        )
        return conn
    except mysql.connector.Error as err:
        print(f"Error: {err}")
        return None

def check_rfid_access(card_id, gate):
    conn = connect_to_db()
    if conn is None:
        return False
    
    cursor = conn.cursor(dictionary=True)
    # Modify the query to include students.id as well
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
        # Print the student's details
        print(f"Student Details: LRN: {student['lrn']}, Name: {student['name']}, Grade: {student['grade']}, Section: {student['section']}")
        
        # Check for recent entry using student['id']
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

def simulate_rfid_scan():
    print("Simulating RFID scan...")
    simulated_rfid = input("Enter RFID card number: ")
    return simulated_rfid

def gate_control_system():
    print("RFID Two-Gate Entrance System Simulation")
    while True:
        gate = input("Select Gate (1 or 2): ")
        if gate not in ['1', '2']:
            print("Invalid gate selection. Please enter 1 or 2.")
            continue
        card_id = simulate_rfid_scan()
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
