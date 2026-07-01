# Futsal Booking System

A web-based Futsal Booking System built using **PHP**, **MySQL**, **HTML**, **CSS**, and **JavaScript**. This project allows users to register, log in, view available futsal courts, book courts, manage their bookings, and update their profile. It also includes an admin panel for managing futsal venues, courts, users, bookings, and payments.

## About the Project

The Futsal Booking System is designed to make futsal court booking easier and more organized. Instead of booking manually through phone calls or in person, users can search available courts, select a booking date and time, and manage their bookings through the system.

The admin side allows the system owner to manage futsal venues, courts, registered users, booking records, and payment status from a separate admin dashboard.

## Features

### User Features

- User registration
- User login and logout
- User dashboard
- View available futsal courts
- Book futsal courts by date and time
- View personal booking history
- Manage user profile
- Cancel or manage bookings

### Admin Features

- Admin login
- Admin dashboard
- Add new futsal venues
- Edit futsal venue details
- Manage futsal venues
- Add new courts
- Edit court details
- Manage courts
- Manage registered users
- Manage court bookings
- Manage payment records
- Update booking status
- Update payment status

### Booking Features

- Select futsal court
- Select booking date
- Select start and end time
- Check court availability
- Prevent booking time conflicts
- Calculate booking price based on court price and duration
- Store booking records in the database

### Payment Features

- Store payment amount
- Manage payment method
- Track payment status
- Support payment statuses such as unpaid, paid, and refunded

## Tech Stack

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- Bootstrap
- Font Awesome
- XAMPP
- phpMyAdmin

## Database Tables

The project uses a MySQL database with the following main tables:

- `users` - stores admin and player account details
- `futsals` - stores futsal venue information
- `courts` - stores court details and pricing
- `bookings` - stores court booking records
- `payments` - stores payment details for bookings

## Project Structure

```text
Futsal-Booking-System/
├── futsal-booking-system/
│   ├── admin/
│   │   ├── add-court.php
│   │   ├── add-futsal.php
│   │   ├── dashboard.php
│   │   ├── edit-court.php
│   │   ├── edit-futsal.php
│   │   ├── manage-bookings.php
│   │   ├── manage-courts.php
│   │   ├── manage-futsals.php
│   │   ├── manage-payments.php
│   │   ├── manage-users.php
│   │   └── sidebar.php
│   │
│   ├── assets/
│   │   ├── css/
│   │   └── js/
│   │
│   ├── auth/
│   │   ├── login.php
│   │   ├── logout.php
│   │   └── register.php
│   │
│   ├── config/
│   │   └── database.php
│   │
│   ├── exported_sql/
│   │   └── 25123788.sql
│   │
│   ├── includes/
│   │   ├── footer.php
│   │   ├── functions.php
│   │   ├── header.php
│   │   └── navbar.php
│   │
│   ├── user/
│   │   ├── book-court.php
│   │   ├── dashboard.php
│   │   ├── get_courts.php
│   │   ├── my-bookings.php
│   │   └── profile.php
│   │
│   ├── index.php
│   └── LOGIN_CREDENTIALS.txt
│
└── README.md
```

## How to Run the Project

### Step 1: Install XAMPP

Install XAMPP on your system and start:

- Apache
- MySQL

### Step 2: Move the Project Folder

Copy the `futsal-booking-system` folder into the XAMPP `htdocs` directory.

Example:

```text
C:\xampp\htdocs\futsal-booking-system
```

### Step 3: Create the Database

Open phpMyAdmin in your browser:

```text
http://localhost/phpmyadmin
```

Create a new database named:

```text
25123788
```

### Step 4: Import the SQL File

Import the SQL file from:

```text
futsal-booking-system/exported_sql/25123788.sql
```

This will create the required tables and sample data.

### Step 5: Check Database Configuration

Open this file:

```text
config/database.php
```

Make sure the database details match your local XAMPP setup:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', '25123788');
```

### Step 6: Run the Project

Open the project in your browser:

```text
http://localhost/futsal-booking-system/
```

## Login Information

The project includes sample login credentials for testing inside:

```text
LOGIN_CREDENTIALS.txt
```

You can also create a new user account from the registration page.

## Main Pages

### Public Pages

- Home page
- Login page
- Registration page

### User Pages

- User dashboard
- Book court page
- My bookings page
- Profile page

### Admin Pages

- Admin dashboard
- Manage futsals
- Manage courts
- Manage users
- Manage bookings
- Manage payments

## Author

**Bibek Karki**

- GitHub: [BibekKarki777](https://github.com/BibekKarki777)
- LinkedIn: [Bibek Karki](https://www.linkedin.com/in/bibekkarkinp/)

## License

This project is created for academic and learning purposes.
