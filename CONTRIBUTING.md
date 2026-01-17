# Contributing to Voyager-L12

## Critical Warning

**This fork is used by multiple production applications.** Breaking changes can cause downtime for real businesses. Please follow these guidelines carefully.

## Before You Start

1. **Understand the impact** - Changes to this repository affect all applications using it
2. **Check existing issues** - Someone may already be working on the same thing
3. **Discuss major changes first** - Open an issue before making significant modifications

## Development Workflow

### Setting Up Local Development

```bash
# Clone the repository
git clone https://github.com/frank-rachel/voyager-l12.git
cd voyager-l12

# Install dependencies
composer install

# Create a test Laravel application to verify changes
```

### Making Changes

1. **Always create a feature branch:**
   ```bash
   git checkout 1.7
   git pull origin 1.7
   git checkout -b feature/your-feature-name
   ```

2. **Make your changes**

3. **Test in a real Laravel application:**
   - Link your local fork using composer's path repository
   - Verify all existing functionality still works
   - Test your new feature/fix

4. **Push your feature branch:**
   ```bash
   git push origin feature/your-feature-name
   ```

5. **Create a Pull Request** targeting the `1.7` branch

### Testing with a Local Application

In your test application's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "/path/to/your/local/voyager-l12"
        }
    ],
    "require": {
        "frank-rachel/voyager-l12": "*"
    }
}
```

Then run:
```bash
composer update frank-rachel/voyager-l12
```

## What NOT To Do

- **Never force push to `1.7`** - This is the main branch used by production
- **Never modify existing version tags** - Tags like `v2.0.0` are immutable
- **Never remove public methods** without deprecation period
- **Never change method signatures** of public APIs
- **Never push untested code** directly to main branch

## Versioning

We follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (v3.0.0): Breaking changes (requires updating dependent code)
- **MINOR** (v2.1.0): New features, backwards compatible
- **PATCH** (v2.0.1): Bug fixes, backwards compatible

### Creating a New Release

Only maintainers should create releases:

```bash
# Ensure you're on the latest 1.7
git checkout 1.7
git pull origin 1.7

# Create annotated tag
git tag -a v2.0.1 -m "Brief description of changes"

# Push tag
git push origin v2.0.1
```

## Code Style

- Follow PSR-12 coding standards
- Use meaningful variable and method names
- Add comments for complex logic
- Keep backwards compatibility in mind

## Reporting Issues

When reporting issues, please include:

1. Laravel version
2. PHP version
3. Voyager-L12 version
4. Steps to reproduce
5. Expected vs actual behavior
6. Error messages/stack traces

## Questions?

Open an issue with the "question" label if you need clarification on anything.
