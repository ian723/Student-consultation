# Kabarak Student Consultation Booking System

A web-based booking system that allows students at Kabarak University to
browse academic staff and university services, view consultation availability,
and book appointments without having to guess office hours or queue in person.

The system supports students, lecturers, academic advisors, department heads,
counselors, university staff, and administrators. Students can browse providers
and manage bookings; providers manage their consultation slots and appointments;
and administrators manage staff accounts.

Built as a school project for Kabarak University using plain PHP and MySQL
(no framework), with HTML, CSS, and JavaScript for the interface.

---

## Features

### Students

- Register and log in.
- Browse **Staff & Services** with availability indicators: Available, Limited
  Slots, or Fully Booked, based on the number of future slots currently marked
  as available.
- View a provider's upcoming consultation slots and book an available slot.
- View **My Bookings** and cancel an upcoming booking, where cancellation is
  allowed.

### Staff and Service Providers

- Log in to a dedicated staff dashboard.
- Manage consultation slots and availability.
- See students who have booked their slots, with booking status and upcoming
  appointment information.
- Supported provider roles include lecturers, academic advisors, department
  heads, counselors, and university staff.

### Administrators

- Log in through the Staff Login page using an admin account.
- Access a separate admin dashboard.
- Create, edit, deactivate, and reactivate staff accounts.

### Under the Hood

- Uses PHP sessions and role-based access checks for protected pages.
- Passwords are hashed using `password_hash()` and checked with
  `password_verify()`.
- Database queries use PDO prepared statements, and rendered output is escaped.
- Forms use CSRF protection where implemented.
- Consultation slots use the `lecturer_id` database column to reference the
  provider's user ID, including supported non-lecturer staff roles.
- Booking concurrency and duplicate-booking protection should be verified
  against the current database schema and booking endpoint before claiming
  guarantees about simultaneous bookings.

---

## Tech Stack

- PHP 8+ (plain PHP scripts with PDO for database access; no framework)
- MySQL / MariaDB
- Vanilla HTML, CSS, and JavaScript (no build step or frontend framework)

---

## Project Structure

```text
sql/
  schema.sql             Database tables and constraints
  seed.sql               Demo/seed data, if provided
src/
  config.example.php     Example database configuration
  config.php             Local database credentials (gitignored)
  db.php                 PDO connection helper
  auth.php               Sessions, login/logout, role guards, CSRF and flash messages
  availability.php       Provider availability and slot queries
public/                  Web root
  index.php              Homepage
  register.php           Student registration
  login_student.php      Student login
  login_staff.php        Staff and admin login
  logout.php             Logout
  student/               Student pages (dashboard, provider directory, slots and bookings)
  staff/                  Staff dashboard, slot management and bookings
  admin/                  Admin dashboard and staff account management
  partials/               Shared header/footer/bootstrap includes
  assets/                 Stylesheets and other public assets
```

---

## Setup

1. Install a local PHP and MySQL environment if you don't have one.
   [XAMPP](https://www.apachefriends.org/) is a straightforward option and
   includes phpMyAdmin.

2. Create and import the database.

   Start MySQL in XAMPP and open phpMyAdmin. Create the database expected by
   the application; the current local configuration uses
   `kabarak_consultation`.

   Import `sql/schema.sql` first, then `sql/seed.sql` if you want the supplied
   demo data. You can use phpMyAdmin's Import tab, or the MySQL command line:

   ```sh
   mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS kabarak_consultation;"
   mysql -u root -p kabarak_consultation < sql/schema.sql
   mysql -u root -p kabarak_consultation < sql/seed.sql
   ```

   If your local MySQL root account has no password, omit `-p`. If either SQL
   file contains database-creation or selection statements, follow the
   instructions in those files and avoid importing seed data repeatedly.

3. Configure the database connection.

   Copy `src/config.example.php` to `src/config.php`:

   ```powershell
   Copy-Item src/config.example.php src/config.php
   ```

   Edit `src/config.php` to match your local MySQL host, port, database name,
   username, and password. Keep `config.php` out of Git; never commit real
   credentials.

4. Run the application.

   From the project root, start PHP's built-in development server. With XAMPP
   installed in the default Windows location:

   ```powershell
   C:\xampp\php\php.exe -S localhost:8000 -t public
   ```

   Alternatively, if PHP is on your `PATH`:

   ```sh
   php -S localhost:8000 -t public
   ```

   Open [http://localhost:8000]in your browser. Keep
   the terminal open while testing. The built-in server is for local
   development, not production hosting.

   To use XAMPP Apache, the app's URL paths are rooted at `/`. Apache's document
   root must point to the project's `public/` directory, or you must configure
   a suitable virtual host. Simply browsing to a nested `.../public/` path may
   cause CSS and links to fail.

---

## Try It Out

1. Register a new student account from the homepage.
2. Log in as a student and browse **Staff & Services**.
3. Select a provider, view their upcoming consultation slots, and book an
   available slot.
4. Visit the student dashboard or bookings page to review the appointment.
5. Log in through Staff Login to manage provider slots and view bookings.
6. Log in as an administrator to manage staff accounts.

### Login Pages

- Student Login: [http://localhost:8000/login_student.php]
- Staff and Admin Login: [http://localhost:8000/login_staff.php]
- Admin dashboard (after logging in):
  [http://localhost:8000/admin/dashboard.php]

Use test accounts only for demonstrations. Check the current database seed file
or create accounts through the supported workflows for valid credentials. Do
not publish passwords, password hashes, or private credentials in this README.

---

## Possible Future Improvements

- Add automated tests for authentication, role permissions, availability
  calculations, and booking conflicts.
- Add email/SMS confirmations and appointment reminders.
- Add appointment rescheduling and configurable cancellation rules.
- Improve searching and filtering by provider name, department, service, and
  availability.
- Support recurring consultation slots.
- Add administrator reports for booking counts and provider usage.
- Continue improving accessibility and mobile responsiveness.
- Add deployment instructions for a secure staging or production environment.
