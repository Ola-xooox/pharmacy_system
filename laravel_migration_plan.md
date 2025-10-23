# MJ Pharmacy Laravel Migration Plan

## 📋 Current System Features
- Authentication with OTP
- Admin Portal (Dashboard, Reports, User Management)
- Inventory Management (Products, Tracking, Purchase History)
- POS System (Point of Sale)
- Customer Management System
- API Endpoints
- Multi-user roles (Admin, Staff, etc.)

## 🚀 Migration Phases

### Phase 1: Laravel Setup & Foundation (Week 1)

#### Day 1-2: Environment Setup
```bash
# Install Laravel
composer create-project laravel/laravel mj-pharmacy-laravel
cd mj-pharmacy-laravel

# Install additional packages
composer require laravel/ui
composer require intervention/image
composer require barryvdh/laravel-dompdf
composer require maatwebsite/excel
```

#### Day 3-4: Database Migration
```php
// Create migrations for existing tables
php artisan make:migration create_users_table
php artisan make:migration create_products_table
php artisan make:migration create_inventory_table
php artisan make:migration create_sales_table
php artisan make:migration create_customers_table
php artisan make:migration create_otp_verification_table
```

#### Day 5-7: Authentication System
```php
// Custom OTP Authentication
php artisan make:middleware OTPMiddleware
php artisan make:controller Auth\OTPController
php artisan make:mail OTPMail
```

### Phase 2: Core Features (Week 2-3)

#### Admin Portal
```php
php artisan make:controller Admin\DashboardController
php artisan make:controller Admin\UserController
php artisan make:controller Admin\ReportController
```

#### Inventory System
```php
php artisan make:controller Inventory\ProductController
php artisan make:controller Inventory\StockController
php artisan make:controller Inventory\PurchaseController
```

#### POS System
```php
php artisan make:controller POS\SaleController
php artisan make:controller POS\TransactionController
```

### Phase 3: Advanced Features (Week 4)

#### API Development
```php
php artisan make:controller API\ProductAPIController
php artisan make:controller API\CustomerAPIController
php artisan make:controller API\InventoryAPIController
```

#### Gmail Integration
```env
# .env configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=lhandelpamisa0@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@pharmacymj.com
MAIL_FROM_NAME="MJ Pharmacy"
```

## 🎯 Laravel Advantages for Your System

### 1. Better Email System
```php
// Simple OTP sending
Mail::to($email)->send(new OTPMail($otp));

// Queue for better performance
Mail::to($email)->queue(new OTPMail($otp));
```

### 2. Robust Authentication
```php
// Built-in authentication with custom OTP
Auth::attempt(['email' => $email, 'password' => $password])
```

### 3. API Development
```php
// RESTful APIs with Laravel Sanctum
Route::apiResource('products', ProductAPIController::class);
```

### 4. Database Management
```php
// Eloquent ORM instead of raw SQL
$products = Product::where('stock', '>', 0)->get();
```

### 5. Modern Frontend
```php
// Blade templates with components
@component('components.dashboard-card')
    @slot('title', 'Total Sales')
    @slot('value', $totalSales)
@endcomponent
```

## 📁 Proposed Laravel Structure

```
mj-pharmacy-laravel/
├── app/
│   ├── Http/Controllers/
│   │   ├── Auth/OTPController.php
│   │   ├── Admin/DashboardController.php
│   │   ├── Inventory/ProductController.php
│   │   ├── POS/SaleController.php
│   │   └── API/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Product.php
│   │   ├── Sale.php
│   │   └── Customer.php
│   ├── Mail/
│   │   └── OTPMail.php
│   └── Middleware/
│       └── OTPMiddleware.php
├── database/
│   └── migrations/
├── resources/
│   ├── views/
│   │   ├── auth/
│   │   ├── admin/
│   │   ├── inventory/
│   │   └── pos/
│   └── js/
└── routes/
    ├── web.php
    └── api.php
```

## 🔧 Migration Steps

### Step 1: Data Export
```sql
-- Export current database
mysqldump -u root pharmacy_system > pharmacy_backup.sql
```

### Step 2: Laravel Setup
```bash
# Create new Laravel project
composer create-project laravel/laravel mj-pharmacy-laravel

# Configure database
cp .env.example .env
php artisan key:generate
```

### Step 3: Database Migration
```php
// Import existing data structure
php artisan migrate
php artisan db:seed
```

### Step 4: Feature Migration
- Convert PHP files to Laravel controllers
- Convert HTML to Blade templates
- Implement Laravel authentication
- Set up Gmail SMTP

### Step 5: Testing & Deployment
```bash
# Run tests
php artisan test

# Deploy to production
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 📧 Gmail OTP Implementation

### Mail Class
```php
<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;

class OTPMail extends Mailable
{
    public $otp;
    
    public function __construct($otp)
    {
        $this->otp = $otp;
    }
    
    public function build()
    {
        return $this->subject('MJ Pharmacy - Your Login OTP Code')
                   ->view('emails.otp')
                   ->with(['otp' => $this->otp]);
    }
}
```

### Controller
```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OTPMail;
use Illuminate\Support\Facades\Mail;

class OTPController extends Controller
{
    public function sendOTP(Request $request)
    {
        $otp = rand(100000, 999999);
        
        // Store OTP in database
        OTPVerification::create([
            'email' => $request->email,
            'otp_code' => $otp,
            'expires_at' => now()->addMinutes(5)
        ]);
        
        // Send via Gmail SMTP
        Mail::to($request->email)->send(new OTPMail($otp));
        
        return response()->json(['success' => true]);
    }
}
```

## 🎯 Benefits of Laravel Migration

1. **Better Gmail Integration** - Built-in SMTP with retry mechanisms
2. **Modern Architecture** - MVC pattern, dependency injection
3. **Security** - CSRF protection, SQL injection prevention
4. **Performance** - Query optimization, caching
5. **Maintainability** - Clean code structure, testing framework
6. **Scalability** - Queue system, database optimization
7. **API Development** - RESTful APIs with Sanctum authentication

## 📅 Timeline

- **Week 1**: Laravel setup, database migration, authentication
- **Week 2**: Admin portal, inventory system
- **Week 3**: POS system, customer management
- **Week 4**: API development, testing, deployment

## 💰 Cost Considerations

- **Development Time**: 3-4 weeks
- **Learning Curve**: Laravel fundamentals
- **Hosting**: Same Hostinger hosting works
- **Benefits**: Better email delivery, modern architecture, easier maintenance

## 🚀 Next Steps

1. **Backup current system** (already working perfectly)
2. **Set up Laravel development environment**
3. **Start with authentication system migration**
4. **Gradually migrate features**
5. **Test thoroughly before switching**

The Laravel migration will solve your Gmail OTP issues and provide a modern, scalable foundation for your pharmacy system!
