# Futsal Booking System

A web-based futsal court booking system built using PHP, MySQL, HTML, CSS, and JavaScript. This project allows users to register, log in, view available futsal courts, book courts, manage their bookings, and update their profile. It also includes an admin panel for managing futsals, courts, users, bookings, and payments.

## Project Overview

The Futsal Booking System is designed to make futsal court booking easier and more organized. Instead of booking manually through phone calls or in person, users can search available courts, select a booking time, and manage their bookings through the system.

The admin side allows the system owner to manage futsal venues, courts, booking records, payment status, and registered users.

## Features

### User Features

- User registration and login
- User dashboard
- View available futsal courts
- Book futsal courts by date and time
- View personal booking history
- Manage profile information
- Logout functionality

### Admin Features

- Admin dashboard
- Add, edit, and manage futsal venues
- Add, edit, and manage courts
- Manage user accounts
- Manage court bookings
- Manage payment records
- Update booking and payment status

## Technologies Used

- PHP
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- XAMPP
- phpMyAdmin

## Project Structure

```text
futsal-booking-system/
│
├── admin/
│   ├── dashboard.php
│   ├── add-futsal.php
│   ├── add-court.php
│   ├── manage-bookings.php
│   ├── manage-courts.php
│   ├── manage-futsals.php
│   ├── manage-payments.php
│   └── manage-users.php
│
├── auth/
│   ├── login.php
│   ├── register.php
│   └── logout.php
│
├── user/
│   ├── dashboard.php
│   ├── book-court.php
│   ├── my-bookings.php
│   ├── profile.php
│   └── get_courts.php
│
├── config/
│   └── database.php
│
├── includes/
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   └── functions.php
│
├── assets/
│   ├── css/
│   └── js/
│
├── exported_sql/
│   └── 25123788.sql
│
└── index.php
