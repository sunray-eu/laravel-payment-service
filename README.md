# Payment Processing Application

This is a Laravel application designed for handling payment processing using multiple payment platforms. It includes functionality for creating payment links, updating transaction statuses, and integrating with PayPal and a sample payment provider.

## Table of Contents

- [Payment Processing Application](#payment-processing-application)
  - [Table of Contents](#table-of-contents)
  - [Requirements](#requirements)
  - [Features](#features)
  - [Installation](#installation)
    - [Step 1: Clone the repository](#step-1-clone-the-repository)
    - [Step 2: Install dependencies](#step-2-install-dependencies)
    - [Step 3: Environment setup](#step-3-environment-setup)
    - [Step 4: Generate application key](#step-4-generate-application-key)
    - [Step 5: Run migrations](#step-5-run-migrations)
  - [Running the Application](#running-the-application)
  - [Project Structure](#project-structure)
    - [Controllers](#controllers)
    - [Models](#models)
    - [Routes](#routes)
    - [Services](#services)
    - [Traits](#traits)
    - [Events](#events)
    - [Listeners](#listeners)
    - [Providers](#providers)
  - [Contributing](#contributing)
  - [License](#license)
  - [Support](#support)

## Requirements

- PHP ^8.3
- Laravel 11
- Composer
- Node.js & npm (for chore scripts)

## Features

- Create payment links using PayPal and a sample payment provider.
- Update transaction statuses.
- Event-driven architecture for handling transactions.
- Integration with PayPal for payment processing.
- Integration with a sample payment provider for demonstration purposes.

## Installation

### Step 1: Clone the repository

```sh
git clone https://github.com/sunray-eu/laravel-payment-service.git
cd laravel-payment-service
```

### Step 2: Install dependencies

Install the necessary PHP and Node.js dependencies.

```sh
composer install
npm install
```

### Step 3: Environment setup

Copy the `.env.example` to `.env` and configure your environment variables.

```sh
cp .env.example .env
```

### Step 4: Generate application key

```sh
php artisan key:generate
```

### Step 5: Run migrations

Ensure you have a database configured in your `.env` file and then run:

```sh
php artisan migrate
```

## Running the Application

Start the application using the Laravel development server.

```sh
php artisan serve
```

## Project Structure

### Controllers

- **PaymentController**: Handles the creation of payment links using various payment platforms.
- **TransactionController**: Handles transaction status updates.

### Models

- **Transaction**: Represents a transaction with attributes such as amount, currency, provider, user ID, status, and payment link.

### Routes

The application defines routes in the `api.php` file for handling user information, creating payment links, and updating transaction statuses.

```php
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\TransactionController;

Route::middleware(['auth:api', 'scopes:create-transaction'])->group(function () {
    Route::post('/create-payment-link', [PaymentController::class, 'createPaymentLink']);
});

Route::middleware(['auth:api', 'scopes:update-transaction'])->group(function () {
    Route::post('/update-transaction-status', [TransactionController::class, 'updateStatus']);
});
```

### Services

- **PayPalService**: Handles interactions with PayPal's API for payment processing.
- **SampleService**: Handles interactions with a sample provider for payment processing.

### Traits

- **ConsumesExternalServices**: Provides a method to make requests to external services.

### Events

- **TransactionCreated**: Event triggered when a transaction is created.
- **TransactionStatusUpdated**: Event triggered when a transaction status is updated.

### Listeners

- **LogTransactionCreated**: Listener that logs the creation of a transaction.
- **LogTransactionStatusUpdated**: Listener that logs the update of a transaction status.

### Providers

- **AppServiceProvider**: Registers application services and bootstraps necessary services such as Passport and custom URL generation for password resets.

## Contributing

Contributions are welcome! Please create an issue or submit a pull request with your changes.

## License

This project is licensed under the MIT License.

## Support

For any questions or support, please open an issue on the GitHub repository.
