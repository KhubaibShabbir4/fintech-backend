## Payments Platform (Laravel 12 + Stripe)

Multi-tenant payments gateway boilerplate with merchant onboarding, Stripe Checkout, webhooks, refunds, admin approval, and role-based APIs powered by Laravel Sanctum and spatie/laravel-permission.

### Tech stack
- **Backend**: Laravel 12, PHP 8.2
- **Auth**: Sanctum personal access tokens
- **RBAC**: spatie/laravel-permission
- **Payments**: stripe/stripe-php (Checkout + Webhooks + Refunds)
- **Build tooling**: Vite, Tailwind

## Prerequisites
- PHP 8.2+
- Composer
- Node.js 18+ and npm
- Database (MySQL/MariaDB/PostgreSQL/SQLite)
- Stripe account and API keys

## Setup
1) Install dependencies
```bash
composer install
npm install
```

2) Create and configure `.env`
```bash
cp .env.example .env
php artisan key:generate
```
Set database and Stripe credentials:
```env
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_user
DB_PASSWORD=your_pass

STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

3) Run migrations and seed roles/admin
```bash
php artisan migrate
php artisan db:seed --class=Database\\Seeders\\RoleSeeder
php artisan db:seed --class=Database\\Seeders\\AdminUserSeeder
```

Default admin user: 
admin@example.com / password123 // in case of AWS backend
khubaib@gmail.com / password123 // in case of being local served


4) Start development
```bash
composer run dev
```
This runs the API server, queue listener, logs, and Vite in parallel.

## Stripe Webhooks (local)
Forward Stripe events to your local webhook:
```bash
stripe listen --forward-to http://localhost:8000/api/payments/webhook
```
Use the printed signing secret as `STRIPE_WEBHOOK_SECRET`.

## Authentication
- Obtain a bearer token via `POST /api/auth/login`.
- Send `Authorization: Bearer <token>` to access protected routes.
- Roles: `merchant` and `admin` (guard `api`). Merchants must be `verified` for some endpoints.

## API Endpoints
Base URL: `/api`

### Public (no auth)
- `POST /payments/checkout` — Create Stripe Checkout Session
  - Body fields (validated):
    - `merchant_id` (required, exists in `merchants`)
    - `amount` (required, numeric, min 1)
    - `currency` (optional, 3-letter; default `usd`)
    - `method` (required: `card|upi|wallet`)
    - `customer.{name,email,phone}` (optional)
    - `cart` (optional array)
    - `return_url_success`, `return_url_failure` (optional URLs)
  - Response: `{ url, session_id }`

- `GET /payments/status/{reference}` — Get payment by reference

- `POST /payments/webhook` — Stripe webhook endpoint

Payment redirect pages (Blade):
- `GET /payment/success`
- `GET /payment/cancel`

### Auth
- `POST /auth/register` — Create `admin` or `merchant` user
- `POST /auth/login` — Get bearer token
- `GET /auth/profile` — Get current user
- `POST /auth/logout` — Revoke current token

### Merchant (requires `auth:sanctum`, role `merchant`, and often verified)
- `POST /merchant/register` — Create/complete merchant profile and start Stripe onboarding
- `POST /merchant/onboarding-link` — Get Stripe onboarding link
- `GET /merchant/profile` — Combined user and merchant profile (verified middleware)
- `GET /merchant/user-profile` — User + merchant snapshot
- `POST /merchant/update` — Update merchant profile
- `GET /merchant/transactions` — List transactions (auto-scoped to merchant)
- `GET /merchant/transactions/export` — CSV export
- `GET /merchant/transactions/{id}` — Show transaction
- `POST /merchant/transactions/{paymentId}/refund` — Full refund by Stripe session
  - Body: `{ "reason": "string" }`

### Admin (requires `auth:sanctum`, role `admin`)
- `GET /admin/dashboard` — Pending merchants
- `POST /admin/approve-merchant/{id}` — Mark merchant as verified
- `POST /admin/reject-merchant/{id}` — Mark merchant as rejected
- `PATCH /admin/merchants/{id}/status` — Set status `verified|rejected`
- `GET /admin/merchants` — All merchant users with profile info
- `GET /admin/transactions` — List transactions
- `GET /admin/transactions/export` — CSV export
- `GET /admin/transactions/{id}` — Show transaction
- `POST /admin/transactions/{paymentId}/refund` — Refund payment
- `GET /admin/stats/{revenue|methods|transactions}` — Aggregated stats

## Payments Flow
1) Merchant signs up (`role=merchant`) and logs in
2) Calls `POST /api/merchant/register` → creates merchant profile, returns Stripe onboarding URL
3) Completes Stripe onboarding → merchant has `stripe_account_id`
4) Public checkout: client calls `POST /api/payments/checkout` with `merchant_id` and details → receive `url` to redirect customer
5) Customer pays on Stripe → redirected to `/payment/success?session_id=...`
6) Webhooks update `payments` and `transactions` statuses (`paid/success/refunded`)

Statuses you may observe: `pending`, `paid`/`succeeded`, `failed`, `refunded`.

## Example: Create Checkout
```http
POST /api/payments/checkout
Content-Type: application/json

{
  "merchant_id": 1,
  "amount": 49.99,
  "currency": "usd",
  "method": "card",
  "customer": {"name": "Jane Doe", "email": "jane@example.com"},
  "cart": [{"sku": "sku_123", "qty": 1}],
  "return_url_success": "http://localhost:8000/payment/success",
  "return_url_failure": "http://localhost:8000/payment/cancel"
}
```
Response
```json
{ "url": "https://checkout.stripe.com/c/session_...", "session_id": "cs_test_..." }
```
