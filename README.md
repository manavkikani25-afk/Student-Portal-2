# Student Portal &bull; Integrated Student &amp; Academic Portal
### Subject: Web Development Frameworks (ITUE203) &bull; Semester 3 (2026-27)
**Faculty of Technology &amp; Engineering (FTE)**  
**Charotar University of Science and Technology (CHARUSAT)**

---

## 1. Project Overview
**Student Portal** is an end-to-end web portal engineered for university students and administrators. Built strictly according to the academic guidelines of **ITUE203 (Web Development Frameworks)**, it covers every syllabus milestone from Practical 1 to Practical 15 without relying on heavy external third-party frameworks like React, Angular, or Laravel.

---

## 2. Practical Mapping &amp; Technical Coverage

| Practical | Module / Topic | File(s) Implementing Feature | Key Skills &amp; Concepts |
| :--- | :--- | :--- | :--- |
| **Prac 1** | Requirement Analysis, Sitemap, Folder Structuring &amp; Git | `README.md`, Directory layout | Functional requirements, clean sitemap, modular design |
| **Prac 2** | Semantic HTML5 &amp; Accessibility Structure | `Home.html`, `About.html`, `Courses.html` | `<header>`, `<nav>`, `<main>`, `<section>`, `<article>`, `<aside>`, `<footer>`, ARIA, skip-to-content |
| **Prac 3** | Responsive UI Design using CSS Grid &amp; Flexbox | `style.css` | CSS variables, Flexbox navbar, CSS Grid layouts, media queries for mobile, tablet &amp; desktop |
| **Prac 4** | JavaScript DOM Manipulation &amp; Interactivity | `script.js`, `Home.html` | Hamburger menu, Light/Dark theme switcher with `localStorage`, collapsible FAQ accordion, modal dialog, image/content slider, dismissible notification banner |
| **Prac 5** | Registration Form with Frontend Validation | `Signup.html`, `script.js` | HTML5 input types, regular expressions for Name, Email, 10-digit Mobile, real-time password strength meter, confirm password match |
| **Prac 6** | Rendering External JSON using Fetch API | `events.html`, `events.json`, `script.js` | Fetch API, dynamic card generation, search filter, category filter, sorting, pagination |
| **Prac 7** | PHP Form Processing with CSV &amp; JSON File Storage | `Help.html`, `process.php`, `data/` | Server-side validation, sanitization (`filter_var`), appending into `data/inquiries.csv` and `data/inquiries.json` |
| **Prac 8** | MySQL Schema Design &amp; PDO Connectivity | `database.sql`, `db.php`, `fetch.php` | Normalized relational schema, PDO connection, prepared statements, utf8mb4 |
| **Prac 9** | Secure Registration with Password Hashing | `Signup.html`, `signup.php` | Duplicate email verification, Bcrypt hashing via `password_hash()`, role assignment |
| **Prac 10** | Secure Login, Sessions, RBAC &amp; Remember-Me | `Login.html`, `login.php`, `auth.php`, `remember.php`, `logout.php` | `password_verify()`, `session_regenerate_id()`, 15-min inactivity timeout, split-token remember-me cookie |
| **Prac 11** | Student Management CRUD with Search &amp; Filter | `students.php` | MySQLi prepared statements (SELECT, INSERT, UPDATE, DELETE), delete confirmation prompt, course/status filter, CSV export |
| **Prac 12** | Event Management CRUD with Validated Poster Upload | `events.php`, `uploads/` | Image MIME &amp; extension check (JPG, PNG, WEBP), max 2MB size limit, unique file naming, old poster cleanup on update/delete |
| **Prac 13** | PHP RESTful JSON API &amp; AJAX CRUD | `api.php`, `ajax_events.html` | JSON endpoints (GET, POST, PUT, DELETE), Fetch API asynchronous CRUD without page refresh, HTTP status codes |
| **Prac 14** | Admin Dashboard with RBAC, Dynamic Menu &amp; Audit Log | `admin_dashboard.php` | SQL count metrics, latest student registrations, dynamic role-based menus, automated tracking in `audit_logs` |
| **Prac 15** | Final Integration, Deployment &amp; Documentation | Whole `Student Portal/` Project | End-to-end navigable system, seed database, comprehensive testing report |

---

## 3. Technology Stack
- **Frontend:** Semantic HTML5, Modern CSS3 (Grid &amp; Flexbox, CSS Custom Properties, Dark Mode), Vanilla JavaScript (ES6+, Fetch API, DOM Events).
- **Backend:** Core PHP 8 (Procedural &amp; Modular), Session Handling, MySQLi prepared statements, PDO.
- **Database:** MySQL 8 / MariaDB (Relational design, Foreign key constraints with cascading deletes).
- **Local Server Environment:** XAMPP (Apache 2.4 &amp; MySQL / MariaDB).

---

## 4. Local Deployment Instructions (XAMPP)

### Step 1: Copy Project to XAMPP htdocs
Copy or move the `Student Portal` folder into your XAMPP web root directory:
```text
C:\xampp\htdocs\Student Portal
```

### Step 2: Start Apache and MySQL
1. Launch **XAMPP Control Panel**.
2. Click **Start** for **Apache**.
3. Click **Start** for **MySQL**.

### Step 3: Import the Database
1. Open your web browser and navigate to: [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
2. Click on the **Import** tab in the top navigation.
3. Click **Choose File** and select `database.sql` from `C:\xampp\htdocs\Student Portal\database.sql`.
4. Click **Import** at the bottom.
5. The `campussync_db` database will be created with all 7 tables and 22+ pre-seeded student records.

### Step 4: Open in Web Browser
Access the portal by visiting:
```text
http://localhost/Student Portal/
```
or directly access any specific page:
- Home: `http://localhost/Student Portal/Home.html`
- Sign In: `http://localhost/Student Portal/Login.html`
- Student Registration: `http://localhost/Student Portal/Signup.html`
- Admin Dashboard: `http://localhost/Student Portal/admin_dashboard.php`
- Student CRUD: `http://localhost/Student Portal/students.php`
- Events CRUD: `http://localhost/Student Portal/events.php`
- AJAX Events: `http://localhost/Student Portal/ajax_events.html`
- PDO Demo: `http://localhost/Student Portal/fetch.php`

---

## 5. Pre-Seeded Test Credentials

| User Role | Email Address | Password | Landing Page |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@campus.edu` | `Admin@12345` | `admin_dashboard.php` |
| **Student** | `student@campus.edu` | `Student@123` | `dashboard.php` |

---

## 6. Project Directory Structure
```text
Student Portal/
├── Home.html             # Homepage with Hero, Carousel Slider, Accordion & Modals
├── About.html            # Institutional overview, vision, mission & infrastructure
├── Courses.html          # Degree curricula (CSE, IT, CE, AIML) & eligibility
├── Timetable.html        # Interactive weekly class schedule & lab allocations
├── StudyMaterial.html    # Verified subject notes, manuals & download links
├── Admission.html        # Admission criteria, 4-step workflow & fee schedule
├── FAQ.html              # Frequently Asked Questions with collapsible accordion
├── Help.html             # Student Helpdesk with inquiries form
├── Signup.html           # Registration form with live regex validation & strength meter
├── Login.html            # Authentication form with session & remember-me checkbox
├── events.html           # External JSON rendering with Fetch API, search, filter & pagination
├── ajax_events.html      # Practical 13 Asynchronous Fetch API CRUD without page reload
│
├── admin_dashboard.php   # Admin control panel with metrics, signups & audit trail
├── dashboard.php         # Student portal dashboard with enrolled courses & event registrations
├── students.php          # Practical 11 Student CRUD with search, filter & CSV export
├── events.php            # Practical 12 Event CRUD with validated poster upload
├── api.php               # Practical 13 RESTful JSON API endpoints
│
├── auth.php              # Session authentication, timeout (15 min) & requireRole RBAC middleware
├── db.php                # Database connectivity (MySQLi + PDO) & audit logging helper
├── login.php             # Login processor with password_verify() & fixation prevention
├── logout.php            # Session destruction and cookie purge
├── signup.php            # Secure registration inserting into users & user_roles
├── process.php           # Practical 7 form processor saving to CSV & JSON
├── remember.php          # Practical 10 split-token cryptographic remember-me handler
├── fetch.php             # Practical 8 PDO connectivity demonstration
│
├── style.css             # Main stylesheet (CSS variables, light/dark themes, responsive grid/flex)
├── script.js             # Client JS (slider, menu, accordion, modal, regex validation, fetch API)
├── events.json           # External JSON dataset for Practical 6
├── faqs.json             # External JSON dataset for FAQs
├── database.sql          # Full MySQL database export with 7 tables & seed data
├── README.md             # Documentation & viva preparation guide
│
├── data/                 # Server storage for Practical 7 (inquiries.csv & inquiries.json)
└── uploads/              # Storage directory for event posters (max 2MB validated)
```

---

## 7. Viva & Practical Exam Questions Prep (ITUE203)

1. **How is password security handled in Practical 9 and 10?**
   - Passwords are never stored in plain text. We utilize PHP's `password_hash($pwd, PASSWORD_DEFAULT)` which produces a cryptographically secure Bcrypt hash with salt included. During login, `password_verify($pwd, $hash)` computes the match securely against timing attacks.

2. **How does session fixation prevention work in login?**
   - Immediately after credentials are confirmed, we call `session_regenerate_id(true)`. This assigns a new random session ID while invalidating the previous session identifier, thwarting session hijacking attempts.

3. **How is the split-token Remember-Me feature implemented in Practical 10?**
   - The cookie contains two values separated by a colon (`selector:validator`). The database stores the plain `selector` and a SHA-256 hash of the `validator`. The selector looks up the token record in $O(1)$, and `hash_equals()` compares the hash. Even if an attacker reads the database, the validator cannot be reconstructed.

4. **How are SQL injections prevented in Practical 11 and 12?**
   - All queries use parameterized prepared statements (`$stmt = $conn->prepare(...)` followed by `$stmt->bind_param(...)`). User inputs are treated strictly as data literals and never concatenated into SQL strings.

5. **How does the Fetch API in Practical 13 eliminate full page reloads?**
   - The frontend JavaScript intercepts form submits via `e.preventDefault()`, builds a JSON object, and sends it via `fetch('api.php', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(...) })`. The server returns JSON, and JavaScript modifies the DOM table directly.
