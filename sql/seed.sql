-- Seed data for local development / demo
-- Lecturer password for ALL seeded lecturers below: "lecturer123"
-- (hash generated with PHP password_hash('lecturer123', PASSWORD_DEFAULT))

USE kabarak_consultation;

INSERT INTO users (full_name, email, password_hash, role, department) VALUES
('Dr. Alice Mwangi', 'alice.mwangi@kabarak.ac.ke', '$2y$12$qXa28xj9Lo.fnGz0IifyPuyGjZcwtXfIZAUX9Q4gH53e4Q1zyomru', 'lecturer', 'Computer Science'),
('Dr. Brian Kiptoo', 'brian.kiptoo@kabarak.ac.ke', '$2y$12$qXa28xj9Lo.fnGz0IifyPuyGjZcwtXfIZAUX9Q4gH53e4Q1zyomru', 'lecturer', 'Information Technology'),
('Prof. Caroline Wafula', 'caroline.wafula@kabarak.ac.ke', '$2y$12$qXa28xj9Lo.fnGz0IifyPuyGjZcwtXfIZAUX9Q4gH53e4Q1zyomru', 'lecturer', 'Mathematics');

-- Sample future consultation slots so the booking flow is demoable right away.
-- Dates are relative to NOW so the seed stays usable regardless of when it's run.
INSERT INTO consultation_slots (lecturer_id, slot_date, start_time, end_time, status)
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '09:00:00', '09:30:00', 'available' FROM users WHERE email = 'alice.mwangi@kabarak.ac.ke'
UNION ALL
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '10:30:00', 'available' FROM users WHERE email = 'alice.mwangi@kabarak.ac.ke'
UNION ALL
SELECT id, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '09:00:00', '09:30:00', 'available' FROM users WHERE email = 'alice.mwangi@kabarak.ac.ke'
UNION ALL
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '11:00:00', '11:30:00', 'available' FROM users WHERE email = 'brian.kiptoo@kabarak.ac.ke'
UNION ALL
SELECT id, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '13:00:00', '13:30:00', 'available' FROM users WHERE email = 'brian.kiptoo@kabarak.ac.ke'
UNION ALL
SELECT id, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', '14:30:00', 'available' FROM users WHERE email = 'caroline.wafula@kabarak.ac.ke';
-- Prof. Wafula intentionally seeded with only 1 slot to demo "Limited Slots" /
-- "Fully Booked" once it's booked.
