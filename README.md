# Student Record System

A simple PHP and MySQL student management app with:

- `school_db` database and `students` table
- Database-backed user registration and login
- PDO database connection
- Session-based authentication
- Create, View, Update, and Delete student records
- Profile picture upload for each student
- Student number validation using the `BN-2310069-1` format
- Prepared statements for database writes

## Setup

1. Import [`database/setup.sql`](database/setup.sql) into MySQL.
2. Check [`config/database.php`](config/database.php) and update the credentials if your local database settings are different.
3. Open the project in Laragon and visit `register.php` to create an account, or go to `login.php` if you already have one.

## Students Table Fields

- `student_number`
- `full_name`
- `email`
- `course`
- `year_level`
- `profile_image`
