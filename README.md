# Online-Voting-System with Presentation
# Online Voting System

A full-stack web application for conducting secure digital elections in schools, colleges, and universities. Built with PHP, MySQL, and Vanilla JavaScript.

🌟 Key Features

* registration, login, and session management
* Secure online voting with one-vote-per-user system
* Candidate browsing with party and position details
* Admin panel for managing voters, candidates, and election results
* Real-time vote counting and result visualization using Chart.js
* Personal voter dashboard with voting status tracking
* Responsive UI for desktop and mobile devices
* AJAX-powered contact/support form

🛠️ Tech Stack

**Frontend:** HTML5, CSS3, JavaScript, Chart.js
**Backend:** PHP
**Database:** MySQL
**Server:** XAMPP

🗄️ Database

**Database name:** `project_db`

Tables used:

* `users`
* `candidates`
* `votes`
* `elections`
* `contact_messages`

All tables are connected through foreign key relationships to maintain normalized and secure data handling. 

🚀 Getting Started

1. Clone or download the project folder into your XAMPP `htdocs` directory
2. Start **Apache** and **MySQL** from the XAMPP Control Panel
3. Open **phpMyAdmin** and create a database named `project_db`
4. Import the `database.sql` file
5. Visit:

```text
http://localhost/online-voting-system/
```

in your browser.

Default database credentials in `db.php`:

```php
host = localhost
username = root
password = ""
database = project_db
```

Update them if your XAMPP setup is different.

🔐 Security

* Passwords are securely hashed using PHP `password_hash()`
* All database operations use prepared statements to prevent SQL Injection
* Voting access is protected using PHP sessions
* Duplicate voting is prevented through one-vote-per-user validation
* Admin pages require authentication before access

📂 Project Structure

```text
/online-voting-system
│── index.php
│── register.php
│── dashboard.php
│── vote.php
│── admin.php
│── logout.php
│── db.php
│── style.css
│── script.js
│── uploads/
│    └── candidate_photos/
```

📊 System Modules

### Voter Module

* User Registration
* Login Authentication
* Candidate Viewing
* Vote Casting
* Vote Status Tracking

### Admin Module

* Candidate Management
* Voter Management
* Election Control
* Result Publication
* Live Vote Monitoring

📈 Architecture

The system follows a Three-Tier Architecture:

1. Presentation Layer — HTML, CSS, JavaScript, Chart.js
2. Application Layer — PHP backend and session handling
3. Database Layer — MySQL database management system  

🎯 Objective

The Online Voting System aims to replace traditional paper-based voting with a secure, transparent, and efficient digital election platform for educational institutions. 

