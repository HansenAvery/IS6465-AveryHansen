-- Part 1: Create Tables
CREATE TABLE if not exists students (
  student_id INTEGER PRIMARY KEY,
  student_name TEXT NOT NULL,
  student_email TEXT NOT NULL UNIQUE
);

CREATE TABLE if not exists tutoring_requests (
  request_id INTEGER PRIMARY KEY,
  student_id INTEGER NOT NULL,
  course TEXT NOT NULL,
  topic TEXT NOT NULL,
  preferred_date TEXT NOT NULL,
  preferred_time TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Open',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id)
);

-- Part 2: Insert Data
INSERT OR ignore INTO students (student_name, student_email)
VALUES ('Maria Lopez', 'maria.lopez@example.com');

INSERT OR ignore INTO students (student_name, student_email)
VALUES ('Jordan Lee', 'jordan.lee@example.edu');

INSERT OR ignore INTO students (student_name, student_email)
VALUES ('Avery Patel', 'avery.patel@example.edu');

INSERT OR ignore INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (1, 'IS 6465', 'PHP arrays and forms', '2026-11-03', '14:00'); 

INSERT OR ignore INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (2, 'IS 6465', 'Forms', '2026-11-04', '16:00'); 

INSERT OR ignore INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (2, 'IS 6465', 'SQL SELECT', '2026-11-04', '14:00'); 

INSERT OR ignore INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (3, 'IS 6465', 'SQLite CRUD', '2026-11-05', '14:00'); 

-- Part 3: Read the stored data
SELECT * FROM students;

SELECT * FROM tutoring_requests;

SELECT * FROM tutoring_requests WHERE status = 'Open';

Select * from tutoring_requests WHERE request_id = "3";

 SELECT
  r.request_id,
  s.student_name,
  s.student_email,
  r.course, r.topic,
  r.preferred_date, r.preferred_time, r.status
FROM tutoring_requests AS r
JOIN students AS s ON r.student_id = s.student_id
ORDER BY r.preferred_date;


-- Part 4: Update one request
UPDATE tutoring_requests
SET topic = 'PHP arrays and forms - updated',
    preferred_date = '2026-10-12',
    preferred_time = '15:00'
WHERE request_id = 1;

UPDATE tutoring_requests
SET topic = 'Forms - updated',
    preferred_date = '2026-10-13',
    preferred_time = '16:00'
WHERE request_id = 11;

UPDATE tutoring_requests
SET status = 'Cancelled'
WHERE request_id = 9;

select * from tutoring_requests;

-- Part 5: Delete one test request
INSERT OR ignore INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (1, 'IS 6465', 'DELETE PRACTICE', '2026-11-03', '14:00');

select * from tutoring_requests;

DELETE from tutoring_requests
WHERE request_id = 18;

select * from tutoring_requests;

--UPDATE tutoring_requests
--SET status = 'Cancelled'
--WHERE request_id = 1;

--select * from tutoring_requests;