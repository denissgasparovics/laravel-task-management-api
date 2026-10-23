# Task Management API

A RESTful API for managing projects, tasks, assignments, comments and task notifications. Built with Laravel, PHP, PostgreSQL and Laravel Sanctum.

## Features

- Token-based authentication: registration, login and logout.
- Project management with owner and member access control.
- Task CRUD with status, priority, due date and assignee.
- Task filtering by status, priority, assignee and due date.
- Sorting and pagination.
- Project membership and assignee validation.
- Task comments with author information.
- Project statistics, including task counts by status and overdue tasks.
- Queued notifications when a task is assigned.
- Scheduled command for identifying overdue tasks.
- Automated feature tests.
- Docker Compose development environment.

## Tech Stack

- PHP 8.5
- Laravel 13
- PostgreSQL 18
- Laravel Sanctum
- Docker Compose
- PHPUnit testing tools

## Requirements

- Docker Desktop with Docker Compose
- Git

PHP and Composer are available inside the Docker container, so they do not need to be installed locally for this setup.

## Getting Started

### 1. Clone the repository

```bash
git clone https://github.com/denissgasparovics/laravel-task-management-api.git
cd task-management-api
```

### 2. Configure environment variables

Create a local environment file:

```bash
cp .env.example .env
```

Set a strong local database password in `.env`:

```dotenv
DB_PASSWORD=your_password
```

The same `DB_PASSWORD` value is used by the application and PostgreSQL service through `compose.yaml`. Do not commit `.env` or real credentials.

### 3. Build and start Docker services

```bash
docker compose up -d --build
```

The Compose configuration defines the application, PostgreSQL database, and Laravel scheduler.

### 4. Generate the application key

```bash
docker compose exec app php artisan key:generate
```

### 5. Run database migrations

```bash
docker compose exec app php artisan migrate
```

### 6. Start the queue worker

In a separate terminal:

```bash
docker compose exec app php artisan queue:work
```

Keep the worker running to process queued notifications. The default local mail configuration uses `MAIL_MAILER=log`, which writes generated email messages to the Laravel log instead of delivering them to a real mailbox.

### 7. Verify the application

```bash
docker compose ps
docker compose exec app php artisan route:list --path=api
```

The API is exposed at `http://127.0.0.1:8000` by the supplied Compose configuration.

## Authentication

All protected endpoints require a Laravel Sanctum bearer token.

Example headers:

```http
Authorization: Bearer YOUR_ACCESS_TOKEN
Accept: application/json
```

### Register

`POST /api/register`

Example request:

```json
{
  "name": "Test User",
  "email": "testuser@example.com",
  "password": "Password123",
  "password_confirmation": "Password123"
}
```

### Login

`POST /api/login`

```json
{
  "email": "testuser@example.com",
  "password": "Password123"
}
```

Use the token returned by the API as a bearer token for protected endpoints.

### Logout

`POST /api/logout`

Requires authentication.

### Current user

`GET /api/user`

Requires authentication.

## API Endpoints

All endpoints below, except registration and login, require authentication.

### Authentication

| Method | Endpoint | Description |
|---|---|---|
| POST | `/api/register` | Register a user |
| POST | `/api/login` | Log in and receive a token |
| POST | `/api/logout` | Revoke the current token |
| GET | `/api/user` | Get the authenticated user |

### Projects

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/projects` | List projects owned by or shared with the authenticated user |
| POST | `/api/projects` | Create a project |
| GET | `/api/projects/{project}` | Get project details |
| PUT/PATCH | `/api/projects/{project}` | Update a project |
| DELETE | `/api/projects/{project}` | Delete a project |
| GET | `/api/projects/{project}/stats` | Get project task statistics |
| POST | `/api/projects/{project}/members/{user}` | Add a project member (owner only) |
| DELETE | `/api/projects/{project}/members/{user}` | Remove a project member (owner only) |

Project owners and members can view project details, tasks, comments and statistics. Members can add comments. Only the owner can create, update, or delete tasks, update or delete the project and manage project members.

### Tasks

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/projects/{project}/tasks` | List tasks in a project |
| POST | `/api/projects/{project}/tasks` | Create a task |
| GET | `/api/projects/{project}/tasks/{task}` | Get task details |
| PUT/PATCH | `/api/projects/{project}/tasks/{task}` | Update a task |
| DELETE | `/api/projects/{project}/tasks/{task}` | Delete a task |

Task listing supports filtering by `status`, `priority`, `assignee_id` and `due_date` as well as sorting and pagination. The `sort_by` parameter accepts `created_at`, `due_date`, `priority`, `title` or `status`; `sort_direction` accepts `asc` or `desc`. `per_page` defaults to 15 and is limited to 1-100.

### Comments

| Method | Endpoint | Description |
|---|---|---|
| GET | `/api/projects/{project}/tasks/{task}/comments` | List task comments |
| POST | `/api/projects/{project}/tasks/{task}/comments` | Add a comment |

## Task Rules

- Supported task statuses are `todo`, `in_progress` and `done`.
- Priority must be an integer between 1 and 5.
- A completed task cannot be moved directly from `done` back to `todo`.
- An assignee must be the project owner or a project member.
- Assigning a task dispatches a queued notification.
- Overdue tasks are identified by a due date earlier than the current date, excluding completed tasks.

## Project Statistics

`GET /api/projects/{project}/stats` returns:

- `project_id`
- `total`
- `todo`
- `in_progress`
- `done`
- `overdue`

The `overdue` count includes tasks with a due date before today whose status is not `done`.

## Scheduled Tasks

Run the overdue-task command manually:

```bash
docker compose exec app php artisan tasks:mark-overdue
```

The Laravel scheduler is configured to run this command hourly. The `scheduler` Docker service runs `php artisan schedule:work`.

The command currently reports the number of overdue, uncompleted tasks, it does not change task statuses.

## Running Tests

Run the complete test suite:

```bash
docker compose exec app php artisan test
```

The feature tests cover authentication, project and member permissions, task validation and filtering, assignment rules, notifications, comments, overdue task detection and project statistics.

## Database

PostgreSQL is used as the database. Schema changes are managed through Laravel migrations.

Check migration status:

```bash
docker compose exec app php artisan migrate:status
```
