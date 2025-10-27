# PHP CRUD App

A simple PHP + MySQL CRUD with image upload (stored in `uploads/`) and a clean Bootstrap 5 UI.

## Requirements
- PHP 8+
- MySQL / MariaDB
- Apache (XAMPP/Laragon/etc.)

## Setup
1. Create database:
   - Name: `crud_demo` (or change in `db.php`)
2. Create table:
   ```sql
   CREATE TABLE `users` (
     `id` INT NOT NULL AUTO_INCREMENT,
     `name` VARCHAR(255) NOT NULL,
     `email` VARCHAR(255) NOT NULL,
     `age` INT NOT NULL,
     `profile_image` VARCHAR(255) DEFAULT '',
     PRIMARY KEY (`id`)
   ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
   ```
3. Configure database credentials in `db.php`.
4. Ensure `uploads/` exists with write permissions. This project auto-creates it when you upload or update an image. The repo includes `uploads/.gitkeep` so the folder exists after clone, but images are ignored by git.
5. Start Apache/MySQL and open the app at `http://localhost/PJ/`.

## Image handling
- File uploads are stored in `uploads/` with sanitized filenames prefixed by a timestamp.
- When an image is missing, a fallback avatar from `ui-avatars.com` is shown.
- Basic hardening: safe filename sanitization and integer casting for IDs.

## Notes for GitHub
- `.gitignore` prevents committing `uploads/` and environment-specific files.
- Do not commit real credentials. Consider using a `db.example.php` and environment variables if you plan to host publicly.

## Future improvements
- Use prepared statements (mysqli/PDO).
- Client/server validation for size/type of images.
- Delete old image files on record update/delete.
- Pagination, search, toasts, and dark mode.
