# BookStore

BookStore is a PHP-based web application for managing an online bookstore.  
The system includes user authentication, book browsing, cart and wishlist functionality, checkout, feedback, admin dashboard, order management, and basic reporting.

## Main Features

- User registration and login
- Email verification and password reset
- Book catalog and search
- Shopping cart
- Wishlist
- Checkout with Stripe test integration
- User profile page
- Feedback system
- Admin dashboard
- Book, genre, user, order, and discount management
- Sales and login/payment logs

## Technologies Used

- PHP
- MySQL
- HTML
- CSS
- JavaScript
- PHPMailer
- Stripe API

## Project Structure

```text
BookStore/
│
├── assets/
│   ├── css/
│   ├── js/
│   ├── uploads/
│   └── visual/
│
├── database/
│   ├── databaseConnection.php
│   └── databaseUserAccess.php
│
├── public/
│   ├── admin/
│   └── user/
│
├── services/
│   ├── auth/
│   ├── email/
│   ├── feedback/
│   ├── Frontend/
│   ├── messages/
│   ├── session/
│   └── StripeAPI/
│
├── config.example.php
└── README.md
