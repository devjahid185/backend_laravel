# District Super App Backend (Laravel + MySQL + Sanctum)

## Stack
- Laravel 12
- MySQL
- Laravel Sanctum (Bearer token auth)

## Setup
1. Create MySQL database: `district_super_app`
2. Update `.env` DB credentials if needed
3. Run migrations and seeders:
   - `php artisan migrate:fresh --seed`
4. Start server:
   - `php artisan serve`

## Authentication
- Register: `POST /api/register`
- Login: `POST /api/login`
- Use returned token for all other APIs:
  - `Authorization: Bearer <token>`

All non-auth APIs are protected with `auth:sanctum`.

## Admin Seed Account
- Phone: `01700000000`
- Password: `Admin@12345`

## Core API Modules
- Auth / Profile
- Worker Directory + Categories
- Service Booking
- Business Directory
- Marketplace
- Chat
- Blood Donor
- Jobs + Apply
- Properties
- News / Notices / Emergency Contacts
- Payments (bKash, Nagad, Verify)
- Admin Dashboard + Moderation
