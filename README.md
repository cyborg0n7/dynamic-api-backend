# Dynamic API Backend

Laravel-based API Gateway backend for **dynamic routing**, **orchestration**, and **secure API management**.
This project allows developers to define APIs dynamically, control access via API keys and JWT authentication, and orchestrate multiple API calls in sequence.

---

## 🚀 Features

* **Dynamic API Routing** — Define APIs and route requests dynamically without hardcoding.
* **API Orchestration** — Execute multiple APIs in sequence with structured results.
* **JWT Authentication** — Secure user registration and login with token issuance.
* **API Key Authentication** — Middleware for API key access control.
* **Role-Based Access Control** — Restrict API access by user roles.
* **Transformation Rules** — Customize request/response transformations per API.
* **CRUD Operations** — Manage Users, APIs, Orchestration Rules, Request Logs, and Billing Records.
* **Extensible Architecture** — Designed for scalability and easy extension.

---

## 📦 Tech Stack

* **Backend Framework:** Laravel
* **Authentication:** JWT (JSON Web Tokens)
* **Database:** MySQL / MariaDB
* **Language:** PHP
* **Version Control:** Git + GitHub

---

## 🛠 Setup

1. **Clone the repository**

   ```bash
   git clone https://github.com/YOUR_USERNAME/dynamic-api-backend.git
   cd dynamic-api-backend
   ```

2. **Install dependencies**

   ```bash
   composer install
   ```

3. **Setup environment variables**

   ```bash
   cp .env.example .env
   ```

   Then configure your `.env` file with database credentials and JWT settings.

4. **Run migrations**

   ```bash
   php artisan migrate
   ```

5. **Serve the application**

   ```bash
   php artisan serve
   ```

---

## 📂 Project Structure

* `app/Http/Controllers` — API controllers and orchestration logic
* `app/Models` — Laravel models for Users, APIs, API Keys, Orchestration Rules, Request Logs
* `routes/api.php` — API routing definitions
* `database/migrations` — Database table definitions
* `config/jwt.php` — JWT authentication configuration

---

## 📝 Progress Log

**Day 1:**

* Created models, controllers, routes for Users, APIs, Orchestration Rules, Request Logs, Billing Records.
* Fixed model–controller mismatches.
* Tested CRUD for Request Logs.
* Configured DB environment variables.
* Attempted Redis setup (rolled back).
* Discussed RabbitMQ integration.

**Day 2:**

* Implemented JWT user registration/login APIs.
* Created middleware for API key authentication and role-based access control.
* Secured API keys with hashing/encryption.
* Added validations for API definitions.
* Stored authentication type and transformation rules.
* Created endpoint to retrieve API definitions.
* Added test API definition and confirmed dynamic routing works via `/api/test`.
* Implemented orchestration with `OrchestrationController` and POST `/api/orchestrate`.

---

## 📜 License

MIT License — see [LICENSE](LICENSE) for details.

---

## 📫 Contact

Created by **Eli El Bakkali**.
Feel free to open issues or submit pull requests.
