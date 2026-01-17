# Voyager - Laravel 12 Compatible Fork

<p align="center"><a href="https://voyager.devdojo.com" target="_blank"><img width="400" src="https://s3.amazonaws.com/thecontrolgroup/voyager.png"></a></p>

This is a **Laravel 12 compatible fork** of the original [Voyager Admin Panel](https://github.com/thedevdojo/voyager).

---

## IMPORTANT: Production Fork

> **WARNING: This fork is used by multiple production applications.**
>
> Before making ANY changes, please read the guidelines below carefully.

### Projects Using This Fork
- TPC Restaurants (restaurants.theplusclub.com)
- *(Add other projects here as they adopt this fork)*

### Development Guidelines

1. **Never push directly to the `1.7` branch** - Always create a feature branch and test thoroughly
2. **Test changes locally first** - Run `composer install` and verify in a test application
3. **Backwards compatibility is critical** - Do not remove or rename public methods/classes
4. **Version tags are immutable** - Once a version (e.g., `v2.0.0`) is released, never modify it
5. **Document all changes** - Update this README for any significant modifications
6. **Consider all dependents** - A breaking change affects ALL projects using this fork

### How to Make Changes Safely

```bash
# 1. Create a feature branch
git checkout -b feature/my-change

# 2. Make your changes and test locally

# 3. Push feature branch
git push origin feature/my-change

# 4. Test in ONE application first before merging

# 5. Only after testing, merge to 1.7 and create a new version tag
git checkout 1.7
git merge feature/my-change
git tag -a v2.0.1 -m "Description of changes"
git push origin 1.7 --tags
```

---

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
