
-- Seed data for local development / demo
-- Existing password hashes are retained from the database records.
-- Do not use truncated password hashes.

USE `kabarak_consultation`;

-- Sample lecturers, student, administrator, and university staff
INSERT INTO users
    (full_name, email, password_hash, role, course, department, service, description)
VALUES
('Dr. Alice Mwangi', 'alice.mwangi@kabarak.ac.ke',
 '$2y$12$qXa28xj9Lo.fnGz0IifyPuyGjZcwtXfIZAUX9Q4gH53e4Q1zyomru',
 'lecturer', NULL, 'Computer Science', NULL, NULL),

('Dr. Brian Kiptoo', 'brian.kiptoo@kabarak.ac.ke',
 '$2y$12$qXa28xj9Lo.fnGz0IifyPuyGjZcwtXfIZAUX9Q4gH53e4Q1zyomru',
 'lecturer', NULL, 'Information Technology', NULL, NULL),

('Prof. Caroline Wafula', 'caroline.wafula@kabarak.ac.ke',
 '$2y$12$qXa28xj9Lo.fnGz0IifyPuyGjZcwtXfIZAUX9Q4gH53e4Q1zyomru',
 'lecturer', NULL, 'Mathematics', NULL, NULL),

('System Administrator', 'admin@kabarak.ac.ke',
 '$2y$10$VnExT/P8dBApIyk8QZHBReog4D5ljpjNtYuHOgUGrlvx43I/A5Oum',
 'admin', 'N/A', 'Administration', NULL,
 'Manages staff accounts and access.'),

('Dr. David Mwangi', 'davidmwangi@kabarak.ac.ke',
 '$2y$10$0gVG7yk9a1oFskPpyCFjiegBrxL1ktk3f03noHmyEe0e4JZzbvg0a',
 'counselor', 'N/A', 'Counseling Services', NULL,
 'Provides confidential support to students.'),

('Dr. Nelius Nyatichi', 'neliusnyatichi@kabarak.ac.ke',
 '$2y$10$JypBY1KROaQFm.DsqYa/ButjX7/xt/ZLIuvcmMuY7xn7qMIAzgJZ6',
 'academic_advisor', 'N/A', 'Law School', NULL, NULL),

('Dr. Mark Phares', 'markphares@kabarak.ac.ke',
 '$2y$10$ibBPYTuLN1r6emcr.4whD.9gZ.AodXh5fghL0sCoPYCtrWpQkxkMa',
 'department_head', 'N/A', 'School of Medicine', NULL, NULL),

('Ms. Nicole Wangui', 'nicolewangui@kabarak.ac.ke',
 '$2y$10$KDIfFMD.vfCJQwm8kbodz.tiPbGo6lFX/.xnNyzeLmUV44QpFhUj2',
 'university_staff', 'N/A', 'University Services', 'Library',
 'Assists students in accessing library resources.'),

('Mr. Kenneth Ojijo', 'kennethojijo@kabarak.ac.ke',
 '$2y$10$srogLYGTcmsFKsy9Jd5xOeA8VzeZQ7JlHpAcJrvgfT7KrAi3.I0Ou',
 'university_staff', 'N/A', 'University Services', 'ICT Services',
 'Provides technical support to students and staff.'),

('Mr. Sam Kelvin', 'samkelvin@kabarak.ac.ke',
 '$2y$10$WF2z5F23MSbc/gGM3/FmS.TI/FUsuBUZnpslILvC0xvaERpKbq/BW',
 'university_staff', 'N/A', 'University Services', 'Finance',
 'Assists students with financial inquiries.'),

('Mr. Jonah Mwirigi', 'jonahmwirigi@kabarak.ac.ke',
 '$2y$10$chFtTbj1XRwgIlBhhuOQje8joUF5GYDJ4AjX/V9X4QDByHIsk7Fh.',
 'university_staff', 'N/A', 'University Services', 'Accomodation-Men',
 'Assists male students with hostel room allocation.'),

('Ms. Grace Ndegwa', 'gracendegwa@kabarak.ac.ke',
 '$2y$10$Cb6068eK7oteK356Jq10J.0laqQXoq.j4naY66/BvTsXeqxoXtvxy',
 'university_staff', 'N/A', 'University Services', 'Accomodation-Ladies',
 'Assists female students with hostel room allocation.'),

('Dr. Shalom Atieno', 'shalomatieno@kabarak.ac.ke',
 '$2y$10$u/RUFmtl0hGLWpcjRQbOW.mfN298HdGXKtezis.u9K/9RYOASXziO',
 'lecturer', 'N/A', 'Business', NULL, NULL),

('Dr. Clare Wanjiku', 'clarewanjiku@kabarak.ac.ke',
 '$2y$10$OtTf/1JJUpUqAd1LoWj3delXKikwsK0R7cnHY2B1EMOZglMT6vjOu',
 'department_head', 'N/A', 'School of Music', NULL, NULL),

('Dr. Annet Wambui', 'annetwambui@kabarak.ac.ke',
 '$2y$10$5oph0eyHQLMHJH7pHQ6qnufsFbpO1.ulLUYUk5UNBrgj1ziREjvya',
 'department_head', 'N/A', 'School of hospitality', NULL, NULL),

('Mr. Harun Kipkebut', 'harunkipkebut@kabarak.ac.ke',
 '$2y$10$9ui8aCeKwwfvZ79Jru8J8uOpGyoufGDQ0/43EIviG6xb8cS6hPZli',
 'university_staff', 'N/A', 'University Services',
 'Academic Records Office',
 'Assists students with academic transcripts and examination records.');



-- Sample future consultation slots for all staff members.
-- Dates are relative to NOW so the seed remains usable.
-- The staff_id column references users.id, including non-lecturer staff.

INSERT INTO consultation_slots
    (staff_id, slot_date, start_time, end_time, status)

-- Lecturers
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),
     '09:00:00', '09:30:00', 'available' 
FROM users WHERE email = 'alice.mwangi@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),
     '10:00:00', '10:30:00', 'available' 
FROM users WHERE email = 'alice.mwangi@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY),
     '09:00:00', '09:30:00', 'available' 
FROM users WHERE email = 'alice.mwangi@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), 
    '11:00:00', '11:30:00', 'available'
FROM users WHERE email = 'brian.kiptoo@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY),
    '13:00:00', '13:30:00', 'available'
FROM users WHERE email = 'brian.kiptoo@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),
    '14:00:00', '14:30:00', 'available'
FROM users WHERE email = 'caroline.wafula@kabarak.ac.ke';

UNION ALL


-- Academic advisor: Shalom Atieno
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),
       '09:00:00', '10:00:00', 'available'
FROM users WHERE email = 'shalomatieno@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY),
       '11:00:00', '12:00:00', 'available'
FROM users WHERE email = 'shalomatieno@kabarak.ac.ke'

-- Academic advisor: Nelius Nyatichi
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),
       '10:00:00', '11:00:00', 'available'
FROM users WHERE email = 'neliusnyatichi@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 6 DAY),
       '14:00:00', '15:00:00', 'available'
FROM users WHERE email = 'neliusnyatichi@kabarak.ac.ke'

-- Department head: Mark Phares
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY),
       '09:00:00', '10:00:00', 'available'
FROM users WHERE email = 'markphares@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY),
       '13:00:00', '14:00:00', 'available'
FROM users WHERE email = 'markphares@kabarak.ac.ke'

-- Department head: Clare Wanjiku
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY),
       '10:00:00', '11:00:00', 'available'
FROM users WHERE email = 'clarewanjiku@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 8 DAY),
       '09:00:00', '10:00:00', 'available'
FROM users WHERE email = 'clarewanjiku@kabarak.ac.ke'

-- Department head: Annnet Wambui
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY),
       '09:00:00', '10:00:00', 'available'
FROM users WHERE email = 'annetwambui@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 9 DAY),
       '11:00:00', '12:00:00', 'available'
FROM users WHERE email = 'annetwambui@kabarak.ac.ke'

-- Counselor: David Mwangi
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY),
       '14:00:00', '15:00:00', 'available'
FROM users WHERE email = 'davidmwangi@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 6 DAY),
       '10:00:00', '11:00:00', 'available'
FROM users WHERE email = 'davidmwangi@kabarak.ac.ke'

-- University staff: Library — Nicole Wangui
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY),
       '11:00:00', '12:00:00', 'available'
FROM users WHERE email = 'nicolewangui@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY),
       '09:00:00', '10:00:00', 'available'
FROM users WHERE email = 'nicolewangui@kabarak.ac.ke'

-- University staff: ICT Services — Kenneth Ojijo
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY),
       '13:00:00', '14:00:00', 'available'
FROM users WHERE email = 'kennethojijo@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 8 DAY),
       '11:00:00', '12:00:00', 'available'
FROM users WHERE email = 'kennethojijo@kabarak.ac.ke'

-- University staff: Finance — Sam Kelvin
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY),
       '14:00:00', '15:00:00', 'available'
FROM users WHERE email = 'samkelvin@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 6 DAY),
       '11:00:00', '12:00:00', 'available'
FROM users WHERE email = 'samkelvin@kabarak.ac.ke'

-- University staff: Men's accommodation — Jonah Mwirigi
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY),
       '14:00:00', '15:00:00', 'available'
FROM users WHERE email = 'jonahmwirigi@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY),
       '11:00:00', '12:00:00', 'available'
FROM users WHERE email = 'jonahmwirigi@kabarak.ac.ke'

-- University staff: Ladies' accommodation — Grace Ndegwa
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 6 DAY),
       '09:00:00', '10:00:00', 'available'
FROM users WHERE email = 'gracendegwa@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 9 DAY),
       '14:00:00', '15:00:00', 'available'
FROM users WHERE email = 'gracendegwa@kabarak.ac.ke'

-- University staff: Academic Records Office — Harun Kipkebut
UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 7 DAY),
       '14:00:00', '15:00:00', 'available'
FROM users WHERE email = 'harunkipkebut@kabarak.ac.ke'

UNION ALL

SELECT id, DATE_ADD(CURDATE(), INTERVAL 9 DAY),
       '09:00:00', '10:00:00', 'available'
FROM users WHERE email = 'harunkipkebut@kabarak.ac.ke';
