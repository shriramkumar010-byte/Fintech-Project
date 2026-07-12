# Fintech Project — Features, Pages, and Module Overview

## Project Purpose
This project is a Laravel 11 + Filament admin application for managing fintech-related workflows such as loan applications, CIBIL reports, EMI calculations, user management, and role-based access control.

## Main Technology Stack
- PHP 8.2
- Laravel 11
- Filament Admin Panel
- Livewire
- MySQL
- Redis
- Docker Compose
- Tailwind CSS
- Spatie Permission + Filament Shield

---

## Core Features

### 1. Loan Management
- Manage loan applications from the admin panel
- View application details, status, and related information
- Search and filter loan records easily
- Perform actions such as viewing, editing, or managing application status

### 2. CIBIL Report Management
- Manage customer CIBIL report records
- View credit score details and related reports
- Search and filter data for faster access

### 3. EMI Calculator
- A dedicated EMI calculator page for users/admins
- Calculates EMI and provides amortization-style results
- Designed with a modern UI and responsive styling

### 4. User and Admin Management
- Admin users can be managed through the Filament panel
- Users can be assigned roles
- Role-based access control is enabled using Filament Shield and Spatie Permission

### 5. Role and Permission Management
- Roles can be created and assigned permissions
- Shield integration helps manage Filament access control
- Users can be linked to roles for better security and administration

### 6. Dashboard and Reporting
- Dashboard widgets provide summary information for loans and CIBIL records
- Useful for quick monitoring of application trends and key metrics

### 7. Docker Support
- The project can run locally using Docker Compose
- Includes app, MySQL, Redis, phpMyAdmin, and Vite services

---

## Main Filament Pages and Resources

### Dashboard
- Main landing page of the admin panel
- Shows high-level business statistics and recent activity

### Loan Resource
- Used to manage loan applications
- Includes filters, search, and listing capabilities

### CIBIL Resource
- Used to manage CIBIL reports
- Supports search and filters for quick access

### EMI Resource / EMI Page
- Dedicated page for EMI calculations
- Useful for financial calculations and demo purposes

### User Resource
- Used to manage users in the admin panel
- Shows user data and assigned roles

### Role Resource
- Used to manage roles and permissions
- Integrated with Shield for Filament-based access control

---

## Important Project Structure

### App Layer
- app/Models: Eloquent models such as User, LoanApplication, CIBIL report models
- app/Filament: Filament resources, pages, widgets, and admin UI logic
- app/Providers: Service providers and application bootstrap logic

### Routes
- routes/web.php: web routes and fallback logout routes
- routes/api.php: API routes if used by the application

### Database
- database/migrations: database schema definitions
- database/seeders: demo and test data setup
- database/factories: factory classes for generating sample records

### Views and Frontend
- resources/views: Blade views for custom pages and UI components
- resources/css and resources/js: frontend styles and scripts

### Docker Files
- Dockerfile: container definition for the PHP app
- docker-compose.yml: multi-container local environment for app, DB, Redis, phpMyAdmin, and Vite

---

## Typical User Flow
1. Open the admin panel
2. Log in as an admin user
3. View dashboard stats and recent loans
4. Manage loan applications and CIBIL reports
5. Use EMI calculator for financial calculations
6. Create/manage users and roles as needed

---

## Role-Based Access Notes
The application uses Spatie Permission and Filament Shield to manage permissions. A user can be assigned a role, and the role can carry permissions for admin actions.

---

## Notes for Developers
- Seeders can be used to create demo data quickly
- Docker is configured for easy local setup
- The admin UI is highly customizable through Filament resources
- The codebase is organized around resources, widgets, models, and service providers

---

## Summary
This project is a complete admin solution for fintech-style operations with a strong focus on:
- loan and CIBIL management
- EMI calculations
- user and role administration
- dashboard insights
- Docker-based local development
