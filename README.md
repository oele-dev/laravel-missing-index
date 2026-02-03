# Laravel Missing Index Detector

Automatically detect SQL queries missing database indexes during development.

## Features

- 🔍 **Automatic Detection** - Listens to all database queries and analyzes them for missing indexes
- 📊 **EXPLAIN Analysis** - Uses MySQL's EXPLAIN to detect full table scans and missing indexes
- 🎯 **Smart Filtering** - Configurable threshold and table/pattern ignoring
- 📝 **Actionable Suggestions** - Provides SQL statements to add missing indexes
- 🚫 **Non-Intrusive** - Only runs in local/testing environments by default
- 📋 **Detailed Logging** - Logs detected issues with context and suggestions

## Installation

Install the package via Composer:

```bash
composer require oeledev/laravel-missing-index --dev
```

The package will automatically register itself via Laravel's package auto-discovery.

## Configuration

Publish the configuration file (optional):

```bash
php artisan vendor:publish --tag=missing-index-config
```

Configuration options in `config/missing-index.php`:

```php
return [
    // Enable/disable detection (null uses app.debug)
    'enabled' => env('MISSING_INDEX_ENABLED', null),

    // Only run in these environments
    'environments' => ['local', 'testing'],

    // Minimum query execution time to analyze (ms)
    'threshold_ms' => 0,

    // Tables to ignore
    'ignore_tables' => [
        'migrations',
        'sessions',
        'cache',
        'jobs',
        'failed_jobs',
    ],

    // Regex patterns to ignore
    'ignore_patterns' => [],

    // Output handlers
    'output' => [
        \OeleDev\MissingIndex\Outputs\Log::class,
    ],

    // Log channel for output
    'log_channel' => 'daily',
];
```

## Usage

Once installed, the package automatically:

1. Listens to all SELECT queries executed during requests
2. Runs EXPLAIN analysis on each query
3. Detects missing indexes based on:
   - Full table scans (`type = ALL`)
   - No index used (`key = NULL`)
   - High row counts without indexes
   - Using filesort or temporary tables
4. Logs warnings with actionable suggestions

### Example Output

When a missing index is detected, you'll see logs like:

```
[2024-01-15 10:30:45] local.WARNING: Missing Database Indexes Detected
{"count":2,"url":"http://localhost/users","method":"GET"}

[2024-01-15 10:30:45] local.WARNING: Missing Index on users
{
    "table":"users",
    "columns":["email","status"],
    "type":"ALL",
    "key":null,
    "rows":10000,
    "extra":"Using where",
    "execution_time_ms":125.43,
    "suggestion":"ALTER TABLE `users` ADD INDEX `idx_users_email_status` (email, status)",
    "sql":"SELECT * FROM users WHERE email = ? AND status = ?"
}
```

### Environment Variables

Control behavior via `.env`:

```env
# Explicitly enable/disable (overrides app.debug)
MISSING_INDEX_ENABLED=true

# Only analyze slow queries (in milliseconds)
MISSING_INDEX_THRESHOLD_MS=50

# Custom log channel
MISSING_INDEX_LOG_CHANNEL=stack
```

## How It Works

The package uses a middleware-based approach:

1. **QueryCollector** - Captures all SELECT queries via Laravel's `DB::listen()`
2. **ExplainAnalyzer** - Runs `EXPLAIN` on each query to detect performance issues
3. **MissingIndexReport** - Creates detailed reports with suggestions
4. **Output Handlers** - Logs findings (extensible for custom outputs)

### Detection Logic

A missing index warning is triggered when EXPLAIN shows:

- `type = 'ALL'` - Full table scan
- `key = NULL` - No index used
- `rows > 1000` AND no index
- `Extra` contains "Using filesort" or "Using temporary"

## Extending

### Custom Output Handlers

Create a custom output handler by implementing the `Output` interface:

```php
namespace App\MissingIndex;

use OeleDev\MissingIndex\Outputs\Output;
use Illuminate\Support\Collection;
use Illuminate\Http\Response;

class CustomOutput implements Output
{
    public function boot(): void
    {
        // Initialize
    }

    public function output(Collection $detectedIssues, Response $response): void
    {
        // Handle output (e.g., send to monitoring service)
    }
}
```

Then add it to your config:

```php
'output' => [
    \OeleDev\MissingIndex\Outputs\Log::class,
    \App\MissingIndex\CustomOutput::class,
],
```

## Requirements

- PHP 8.0 or higher
- Laravel 9.x, 10.x, or 11.x
- MySQL database (EXPLAIN syntax support)

## License

MIT

## Credits

Inspired by [beyondcode/laravel-query-detector](https://github.com/beyondcode/laravel-query-detector)
