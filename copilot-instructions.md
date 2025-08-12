# GitHub Copilot Instructions for weeklytop20

Welcome to the `weeklytop20` project! This document provides guidelines and best practices for using GitHub Copilot effectively within this codebase.

## Project Overview
- **Framework:** Laravel 10
- **Frontend:** Tailwind CSS v4, Vite
- **Testing:** Pest, PHPUnit
- **Other Tools:** Composer, PostCSS

## Copilot Usage Guidelines

### 1. Code Style & Conventions
- **Follow PSR-12** for PHP code style.
- Use **Laravel best practices** for controllers, models, migrations, and services.
- For JavaScript/CSS, follow conventions already present in `resources/js` and `resources/css`.

### 2. File Organization
- Place new controllers in `app/Http/Controllers/`.
- Place new models in `app/Models/`.
- Place new services in `app/Services/`.
- Place new migrations in `database/migrations/`.
- Place new views in `resources/views/`.

### 3. Naming Conventions
- Use **StudlyCase** for class names (e.g., `ChartController`).
- Use **camelCase** for variables and methods.
- Use **snake_case** for database columns and migration files.

### 4. Testing
- Add new tests in `tests/Feature/` or `tests/Unit/` as appropriate.
- Use Pest for new tests unless PHPUnit is required.

### 5. Environment & Configuration
- Store sensitive data in `.env` (never commit secrets).
- Update `config/` files for new services or integrations.

### 6. Frontend
- Use Tailwind CSS utility classes for styling.
- Place new JS modules in `resources/js/`.
- Use Vite for asset bundling.

### 7. Dependency Management
- Use Composer for PHP dependencies.
- Use npm/yarn for JS/CSS dependencies.

### 8. Commit Messages
- Use clear, descriptive commit messages (e.g., `feat: add Spotify integration`).

### 9. Documentation
- Update `README.md` for major changes or new features.
- Document new commands or scripts.

## Additional Notes
- For questions about Laravel, refer to the [Laravel Documentation](https://laravel.com/docs).
- For Tailwind CSS, see the [Tailwind Docs](https://tailwindcss.com/docs).
- For Pest, see the [Pest Docs](https://pestphp.com/docs/introduction).

- Currently we're using Laravel 10 and Tailwind CSS v3, but we plan to upgrade to Laravel 12 and Tailwind CSS v4 in the future.

- For every tasks, please create a new branch with the format `feature/description` or `bugfix/description`.

- Maintain a tasks.md file in the root directory to create and track ongoing todo for tasks.

- When you finish a task, complete the task in tasks.md and create a commit with a clear description for the commit message.