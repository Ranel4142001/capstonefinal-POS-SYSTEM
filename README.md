POS System with Inventory Management

Project Overview

This project is a POS system with inventory management. It now uses a Laravel backend while preserving the original PHP frontend logic.

Project Structure

- frontend/  Legacy PHP frontend (views, includes, assets, and legacy APIs)
- backend/   Laravel backend (routes, controllers, auth tokens, migrations)

Quick Start (Local)

1. Install dependencies
   - cd backend
   - composer install

2. Configure environment
   - Copy backend/.env.example to backend/.env (already provided)
   - Update DB settings in backend/.env (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)

3. Migrate tables
   - php artisan migrate

4. Serve the app
   - php artisan serve
   - Open http://127.0.0.1:8000/

Notes

- The root route (/) loads the POS system. If you are not logged in, it redirects to /login.
- Legacy assets are served from /public/... and live in frontend/public/ (copied to backend/public/public for serving).
- If you need the sample data, import frontend/database/capstonefinal.sql with phpMyAdmin or MySQL CLI.

API Authentication (Access + Refresh Tokens)

The Laravel backend provides token-based authentication endpoints:

- POST /api/auth/login
  body: { "username": "...", "password": "..." }

- POST /api/auth/refresh
  body: { "refresh_token": "..." }

- GET /api/auth/me
  header: Authorization: Bearer <access_token>

- POST /api/auth/logout
  header: Authorization: Bearer <access_token>

Role-based authorization middleware is available as `role:admin` or `role:cashier` for future API routes.

Technologies Used

- Backend: Laravel (PHP)
- Frontend: HTML, CSS, JavaScript, Bootstrap
- Database: MySQL (relational tables)

Purpose

This capstone project demonstrates full-stack development skills with a practical POS system.