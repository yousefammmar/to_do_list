# To-Do List Project

This is a PHP-based To-Do List application.

## 🚀 Getting Started

To test or run this project on your local machine, follow these steps:

### Prerequisites
*   A local server environment like **XAMPP**, **WAMP**, or **MAMP**.
*   **PHP** 7.2 or higher.
*   **MySQL** Database.

### Installation

1.  **Clone or Download** this repository.
2.  **Move the project folder** to your server's root directory (e.g., `htdocs` in XAMPP or `www` in WAMP).
3.  **Database Setup**:
    *   Open your database management tool (e.g., **phpMyAdmin**).
    *   Create a new database named `tst` (or allow the code to use another name by updating `database.php`).
    *   Import the provided SQL file: `todo_list.sql`.
4.  **Configuration**:
    *   Open `database.php`.
    *   Ensure the `$user`, `$pass`, and `$dbname` variables match your local database configuration.
    
    ```php
    // Default configuration in database.php
    $host = "localhost";
    $user = "root";
    $pass = "";
    $dbname = "tst"; 
    ```

5.  **Run the App**:
    *   Open your browser and navigate to `http://localhost/your-project-folder/`.

## 📂 Project Structure
*   `index.php` - Main entry point.
*   `dashboard.php` - User dashboard (protected).
*   `database.php` - Database connection settings.
*   `todo_list.sql` - Database import file.

## ✨ Features
*   User Registration & Login.
*   Create, Read, Update, Delete (CRUD) for Tasks and Notes.
*   Profile Management.
