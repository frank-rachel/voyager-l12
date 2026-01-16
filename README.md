# Voyager - Laravel 12 Compatible Fork

<p align="center"><a href="https://voyager.devdojo.com" target="_blank"><img width="400" src="https://s3.amazonaws.com/thecontrolgroup/voyager.png"></a></p>

This is a **Laravel 12 compatible fork** of the original [Voyager Admin Panel](https://github.com/thedevdojo/voyager).

## About This Fork

The original Voyager package was archived and did not support Laravel 11+. This fork adds compatibility for:

- **Laravel 10.x, 11.x, and 12.x**
- **PHP 8.2, 8.3, and 8.4**
- **Doctrine DBAL 3.5+ and 4.0**

## Installation

```bash
composer require frank-rachel/voyager-l12
```

Then run:

```bash
php artisan voyager:install
```

Or with dummy data:

```bash
php artisan voyager:install --with-dummy
```

## Changes from Original

### Laravel 12 Compatibility
- Updated `illuminate/support` constraint to support `~12.0`
- Updated all dev dependencies for Laravel 12 compatibility
- Updated `laravel/ui` requirement to `^4.0`

### Doctrine DBAL 4.0 Compatibility

The following changes were made to support DBAL 4.0, which has significant breaking changes from DBAL 3.x:

#### SchemaManager (`src/Database/Schema/SchemaManager.php`)
- **Connection handling**: DBAL 4.0 no longer accepts PDO connections directly. `getDatabaseConnection()` now builds connection parameters from Laravel's database config and passes them to `DriverManager::getConnection()`
- **Platform detection**: Added `getPlatformName()` helper since `Platform::getName()` was removed in DBAL 4.0

#### Type System (`src/Database/Types/Type.php`)
- **Type name resolution**: Added `getTypeName()` helper that uses the TypeRegistry or extracts from class name, since `Type::getName()` was removed
- **Platform name resolution**: Added `getPlatformName()` helper with version number stripping (e.g., `Postgresql120Platform` → `postgresql`)
- **Type mapping extraction**: `extractPlatformTypeMapping()` now uses reflection to access internal mapping, with comprehensive fallback defaults for MySQL and PostgreSQL types
- **PHP 8.2+ compatibility**: Replaced dynamic `$customOptions` property with static `$typeOptionsMap` to avoid deprecation warnings

#### Type Classes (`src/Database/Types/Common/*.php`, `src/Database/Types/Postgresql/*.php`)
- Added `: string` return type to `getSQLDeclaration()` methods for DBAL 4.0 interface compliance

#### Database Controller (`src/Http/Controllers/VoyagerDatabaseController.php`)
- Added `getPlatformName()` helper for DBAL 4.0 compatibility in the database management UI

### Code Improvements
- Replaced deprecated `Auth::guest()` with `Auth::check()`
- Updated `VoyagerUser` trait to use `loadMissing()` for eager loading
- Removed support for PHP < 8.2 and Laravel < 10

### Minimum Requirements
- PHP 8.2+
- Laravel 10.0+

## Creating an Admin User

```bash
php artisan voyager:admin your@email.com --create
```

## Original Documentation

For original documentation, visit [Voyager Documentation](https://voyager-docs.devdojo.com/).

Video Tutorial: https://voyager.devdojo.com/academy/

## Features

Voyager is a Laravel Admin & BREAD System (Browse, Read, Edit, Add, & Delete):

- **Media Manager** - Upload, organize and share media files
- **Menu Builder** - Easily build menus with drag-and-drop interface
- **Database Manager** - View and modify your database schema
- **BREAD Builder** - Build CRUD interfaces for any database table
- **Settings** - Key/value settings management
- **And much more...**

![Voyager Screenshot](https://s3.amazonaws.com/thecontrolgroup/voyager-screenshot.png)

## License

MIT License - see the original [Voyager](https://github.com/thedevdojo/voyager) repository for full license details.

## Credits

- Original package by [The Control Group](https://www.thecontrolgroup.com) and [DevDojo](https://github.com/thedevdojo)
- Laravel 12 compatibility by [Frank Rachel](https://github.com/frank-rachel)
