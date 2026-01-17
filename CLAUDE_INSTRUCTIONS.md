# Instructions for Claude Code

**Copy this entire file content when starting a Claude Code session that involves modifying this Voyager fork.**

---

## Context

This is a custom Laravel 12 compatible Voyager fork at `https://github.com/frank-rachel/voyager-l12`.

**CRITICAL: This fork is used by multiple production applications. Any changes must be made carefully to avoid breaking existing projects.**

**Repository:** https://github.com/frank-rachel/voyager-l12
**Branch:** 1.7

---

## When Adding a New Feature or Fix to Voyager:

1. **Clone the fork to a temp directory:**
   ```bash
   git clone https://github.com/frank-rachel/voyager-l12.git /tmp/voyager
   cd /tmp/voyager
   ```

2. **Create a feature branch (NEVER work directly on 1.7):**
   ```bash
   git checkout 1.7
   git checkout -b feature/description-of-change
   ```

3. **Make your changes** - Keep backwards compatibility in mind

4. **Test locally** by temporarily pointing the application's composer.json to the local path:
   ```json
   "repositories": {
       "voyager-local": {
           "type": "path",
           "url": "/tmp/voyager",
           "options": {"symlink": true}
       }
   }
   ```

5. **Once tested, push the feature branch:**
   ```bash
   git push origin feature/description-of-change
   ```

6. **Merge to main branch and create a new version tag:**
   ```bash
   git checkout 1.7
   git merge feature/description-of-change
   git tag -a v2.0.X -m "Description of changes"
   git push origin 1.7 --tags
   ```

7. **Update the application's composer.json** to use the new version:
   ```json
   "frank-rachel/voyager-l12": "^2.0"
   ```

8. **Run composer update** in the application

---

## CRITICAL RULES:

- **NEVER push untested changes directly to branch 1.7**
- **NEVER modify existing version tags** - they are immutable
- **NEVER remove or rename public methods** - other projects depend on them
- **ALWAYS test in a local application first**
- **ALWAYS create a new version tag after merging changes**
- **ALWAYS update the README if adding significant features**

**This fork serves multiple production applications. Breaking changes = production downtime.**

---

## Common Customization Locations:

| Purpose | Location |
|---------|----------|
| Controllers | `src/Http/Controllers/` |
| Views | `resources/views/` |
| Models | `src/Models/` |
| Middleware | `src/Http/Middleware/` |
| Traits | `src/Traits/` |
| Database/Schema | `src/Database/` |
| Config | `publishable/config/voyager.php` |
| Routes | `routes/voyager.php` |
| Assets | `publishable/assets/` |
| Translations | `publishable/lang/` |

---

## Projects Using This Fork

- TPC Restaurants (restaurants.theplusclub.com)
- *(Add other projects here as they adopt this fork)*

---

## Version History

| Version | Date | Changes |
|---------|------|---------|
| v2.0.0 | 2026-01 | Initial Laravel 12 compatible release, DBAL 4.0 support, PHP 8.2+ |

---

## How to Use This Fork in a New Application

Add to `composer.json`:

```json
{
    "require": {
        "frank-rachel/voyager-l12": "^2.0"
    },
    "repositories": {
        "voyager-l12": {
            "type": "vcs",
            "url": "https://github.com/frank-rachel/voyager-l12.git"
        }
    },
    "config": {
        "github-protocols": ["https"]
    }
}
```

Then run:
```bash
composer update
php artisan voyager:install
```
