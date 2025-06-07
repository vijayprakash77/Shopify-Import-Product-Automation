# Laravel Shopify CSV Import System

A Laravel 12 application that allows users to upload CSV files containing product data, process them asynchronously, and import products to Shopify with real-time status tracking.

## Project Overview

This system provides a complete solution for importing product data from CSV files to Shopify stores. It features asynchronous processing, comprehensive logging, status tracking, and a user-friendly dashboard interface.
## Project overflow

https://www.loom.com/share/138a486df36c49c98624011e3740a705?sid=2911ec61-06fd-48b7-80a3-7937311cbcf5

## Image
![project-image-1](https://github.com/user-attachments/assets/4af0f457-4c8d-4285-b7dc-36bdd512fa59)

![project-image-2](https://github.com/user-attachments/assets/171ebd1f-124c-4fec-9e95-0998a414d64f)

![project-image-3](https://github.com/user-attachments/assets/50c73dd1-ddf1-45c7-b680-d2d4a4a5168e)

![project-image-4](https://github.com/user-attachments/assets/3e9ae432-c96a-43c2-88be-96dd19cbd03e)

### Key Features

- **CSV File Upload**: Clean, responsive interface for uploading product CSV files
- **Asynchronous Processing**: Background job processing for large CSV files
- **Shopify Integration**: Direct API integration with Shopify stores
- **Real-time Status Tracking**: Monitor import progress and status
- **Error Handling**: Comprehensive error logging and user feedback
- **Dashboard**: Complete overview of all imports and their statuses
- **Scheduled Imports**: Automated cron-based import processing

## Prerequisites

- PHP 8.1 or higher
- Composer
- XAMPP (for local development)
- Shopify store with API access

## Installation & Setup

### 1. Clone the Repository

```bash
git clone <your-repository-url>
cd shopify-csv-app
```

### 2. Install Dependencies

```bash
# Install PHP dependencies
composer install

```

### 3. Setup XAMPP

1. Install XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Start Apache and MySQL services from XAMPP Control Panel
3. Create a new database named `shopify_csv_app` in phpMyAdmin

### 4. Environment Configuration

Copy the example environment file and configure it:

```bash
cp .env.example .env
php artisan key:generate
```

Update your `.env` file with the following configurations:

```env
# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=shopify_csv_app
DB_USERNAME=root
DB_PASSWORD=

# Shopify Configuration
SHOPIFY_SHOP_URL=https://testing-vijay-store.myshopify.com
SHOPIFY_API_KEY=shpat_xxxxxxxxxxxxxxxx
SHOPIFY_COLLECTION_ID=xxxxxxxxxxx

# Queue Configuration
QUEUE_CONNECTION=database
```

### 5. Database Setup

Run the database migrations:

```bash
php artisan migrate
```

### 6. Create Required Jobs and Commands

Generate the necessary Laravel components:

```bash
# Create the Shopify import job
php artisan make:job ProcessShopifyImport

# Create the import cron command
php artisan make:command ImportCron --command=Import:cron
```

### 7. Setup Task Scheduler

#### For Linux/macOS:
```bash
crontab -e
```

Add this line to run the Laravel scheduler every minute:
```bash
* * * * * cd /mnt/c/Users/shopify-csv-app && php artisan schedule:run >> /dev/null 2>&1
```

#### For Windows:
Use Task Scheduler to create a task that runs every minute with this command:
```cmd
cd /path/to/your/project && php artisan schedule:run 
```

### 8. Configure Laravel Scheduler

Add the following to your `routes/console.php` file:

```php
<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

// Add this line for the import cron job
Schedule::command('Import:cron')->everyMinute();
```

### 9. Create Test User

Create a dummy user for testing:

```bash
php artisan tinker
```

Then run:
```php
User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password123')
]);
exit
```

### 10. Setup Shopify Custom App

1. Go to your Shopify admin panel: `https://testing-vijay-store.myshopify.com/admin`
2. Navigate to **Settings** → **Apps and sales channels**
3. Click **Develop apps** → **Create an app**
4. Configure the app with necessary scopes:
   - `read_products`
   - `write_products`
   - `read_inventory`
   - `write_inventory`
5. Install the app and copy the API key to your `.env` file

### 11. Start the Application

```bash
# Start the Laravel development server
composer run dev

```

## Usage

1. **Login**: Access the application at `http://localhost:8000` and login with your test user credentials
2. **Upload CSV**: Navigate to the upload section and select your product CSV file
3. **Monitor Progress**: Use the dashboard to track import status and view any errors
4. **Shopify Integration**: Products will be automatically imported to your Shopify store

## CSV Format

Your CSV file should include the following columns:
 check sample from this path Shopify-Import-Product-Automation\storage\app\private\imports

## Project Structure

```
├── app/
│   ├── Jobs/
│   │   └── ProcessShopifyImport.php
│   ├── Console/
│   │   └── Commands/
│   │       └── ImportCron.php
│   ├── Models/
│   └── Http/
│       └── Controllers/
├── database/
│   └── migrations/
├── resources/
│   ├── views/
│   └── js/
├── routes/
│   ├── web.php
│   └── console.php
└── storage/
    └── logs/
```

## Implementation Details

### Background Processing
The system uses Laravel's queue system to process CSV files asynchronously, preventing timeouts for large files.

### Error Handling
Comprehensive error logging captures import failures with detailed messages for debugging.

### Shopify Integration
Direct API integration using Shopify's REST API/Graphql API for product creation and updates.

### Status Tracking
Real-time status updates for each product import (pending, processing, successful, failed).

## Troubleshooting

### Common Issues

1. **Queue not processing**: Ensure scheduler is running
2. **Shopify API errors**: Verify API credentials and app permissions
3. **Database connection**: Check XAMPP MySQL service is running
4. **File upload issues**: Check file permissions and PHP upload limits

### Logs

Check the following log files for debugging:
- `storage/logs/laravel.log` - General application logs
- Laravel telescope (if installed) - Request/job monitoring

## Testing

1. Prepare a test CSV file with sample product data
2. Login to the application
3. Upload the CSV file
4. Monitor the dashboard for import progress
5. Verify products appear in your Shopify store

## Design Decisions

- **Asynchronous Processing**: Chose queue-based processing to handle large CSV files without timeouts
- **Database Tracking**: Implemented comprehensive status tracking for better user experience
- **Modular Architecture**: Separated concerns with dedicated jobs, commands, and controllers
- **Error Resilience**: Built-in retry mechanisms and detailed error logging

## Future Enhancements

- Real-time WebSocket updates for dashboard
- Advanced CSV validation and mapping
- Bulk product updates and inventory sync
- Multi-store support

## Support

For issues or questions, please check the logs first, then create an issue in the GitHub repository with detailed error information and steps to reproduce.# Shopify-Import-Product-Automation
