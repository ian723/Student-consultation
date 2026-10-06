# Kabarak Student Consultation Booking System

A web-based booking system that lets students see when their lecturers are
free for consultation and book a slot directly, instead of guessing office
hours or turning up to a full queue. Lecturers manage their own availability
and see who's booked in with them.

Built as a school project for Kabarak University using plain **PHP + MySQL**
(no framework), implementing a 4-epic, 12-user-story spec: registration &
auth, lecturer/slot browsing, booking & cancellation, and
confirmations/administration.

## Features

**Students**
- Register and log in
- Browse lecturers with a live **availability indicator** — `Available`,
  `Limited Slots`, or `Fully Booked`, based on how many open slots a
  lecturer currently has
- View a lecturer's upcoming consultation slots and book one
- View "My Bookings" and cancel an upcoming booking (frees the slot again)

**Lecturers**
- Log in to a dedicated dashboard
- Create, edit, and delete consultation slots (locked once a slot is booked,
  to protect the student's booking)
- See which students have booked their slots, with status and upcoming flags

**Under the hood**
- Booking is done inside a DB transaction with row locking
  (`SELECT ... FOR UPDATE`), plus a DB-level uniqueness constraint, so two
  students can never book the same slot — verified by bypassing the UI and
  POSTing directly at the endpoint.
- Every protected page is guarded by role (student-only / lecturer-only) at
  the top of the script, not just hidden in the UI.
- Passwords are hashed with `password_hash()`; all queries use PDO prepared
  statements; all output is escaped; forms are CSRF-protected.

## Tech stack

- PHP 8+ (no framework — plain procedural scripts, PDO for the database)
- MySQL / MariaDB
- Vanilla HTML/CSS/JS (no build step, no frontend framework)

## Project structure

```
sql/
  schema.sql          Database tables (users, consultation_slots, bookings)
  seed.sql            Demo lecturers + sample slots
src/
  config.php          DB credentials (gitignored — copy from config.example.php)
  db.php              PDO connection helper
  auth.php            Sessions, login/logout, role guards, CSRF, flash messages
  availability.php     Availability-indicator logic, lecturer/slot queries
public/               Web root
  index.php, register.php, login_student.php, login_lecturer.php, logout.php
  student/            Student-only pages (dashboard, lecturers, booking, etc.)
  lecturer/           Lecturer-only pages (dashboard, slot management, bookings)
  partials/           Shared header/footer/bootstrap includes
  assets/style.css    Stylesheet
```

## Setup

1. **Install a local PHP + MySQL environment** if you don't have one —
   [XAMPP](https://www.apachefriends.org/) is the simplest option and
   includes phpMyAdmin for inspecting the database.

2. **Import the database:**
   ```
   mysql -u root < sql/schema.sql
   mysql -u root < sql/seed.sql
   ```
   (or run both files through phpMyAdmin's "Import" tab)

3. **Configure the database connection:**
   ```
   cp src/config.example.php src/config.php
   ```
   Edit `src/config.php` if your MySQL user/password differ from the
   XAMPP defaults (`root` / no password).

4. **Run it**, using whichever of these fits how you work:

   **Option 1 — PHP's built-in server** (quickest, no Apache config needed).
   From the project root:
   ```
   php -S localhost:8000 -t public
   ```
   Then open **http://localhost:8000**.

   **Option 2 — XAMPP's Apache**, serving this project directly.
   > The app's links (`/assets/style.css`, `/login_student.php`, etc.) are
   > absolute paths rooted at `/`. That only works if Apache's web root
   > **is** the `public/` folder — if you copy the whole project under
   > `htdocs` and browse to a subfolder (e.g.
   > `localhost/Student-consulation/public/`), those links will 404 and the
   > page will render unstyled.

   1. Copy (or clone) this project into `C:\xampp\htdocs\Student-consulation`
      (or wherever your XAMPP's `htdocs` lives).
   2. Open `C:\xampp\apache\conf\httpd.conf` and change both:
      ```
      DocumentRoot "C:/xampp/htdocs"
      <Directory "C:/xampp/htdocs">
      ```
      to point at this copy's `public` folder instead:
      ```
      DocumentRoot "C:/xampp/htdocs/Student-consulation/public"
      <Directory "C:/xampp/htdocs/Student-consulation/public">
      ```
   3. Restart Apache from the XAMPP Control Panel.
   4. Open **http://localhost/**.

      Note: this makes the project the *only* thing served from
      `localhost/` — if you host other projects under `htdocs` too, set up
      a dedicated virtual host (`httpd-vhosts.conf` + an entry in your
      `hosts` file) pointing at `public/` instead of changing the main
      `DocumentRoot`.

## Try it out

Register a new student account from the homepage, or log in as a
pre-seeded lecturer:

| Email                          | Password      |
|---------------------------------|--------------|
| alice.mwangi@kabarak.ac.ke      | lecturer123  |
| brian.kiptoo@kabarak.ac.ke      | lecturer123  |
| caroline.wafula@kabarak.ac.ke   | lecturer123  |

Lecturer accounts are pre-seeded rather than self-registered, matching the
project's user stories (only student registration is in scope).

## Possible future improvements

- Search/filter lecturers by department or name
- Recurring slots (e.g. "every Tuesday 2–4pm") instead of one-off creation
- Email/SMS reminders before a consultation
- An admin panel for managing lecturer accounts instead of SQL seeding
- Lecturer self-registration with an approval step
- A notes/reason field so students can say what they want to discuss
- Pagination once lecturer/booking lists grow large
- Automated tests (PHPUnit) for the booking/availability logic
