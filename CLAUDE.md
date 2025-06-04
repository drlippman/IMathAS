# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What is IMathAS

IMathAS (Internet Mathematics Assessment System) is a web-based mathematics assessment and learning management system. It provides algorithmically generated math questions with automatic grading, course management tools, and LTI integration. The system powers platforms like MyOpenMath.com and WAMAP.org.

## Tech Stack

- **Backend**: PHP 7.4+ with PDO for database access
- **Frontend**: Vue.js 3 (assess2), jQuery (legacy), server-side rendered PHP
- **Database**: MySQL 5.6+
- **Math Rendering**: MathJax/KaTeX with ASCIIMath input and MathQuill editor
- **Testing**: Codeception (unit, functional, acceptance tests)

## Common Commands

### Setup and Configuration
```bash
# Copy and configure the main config file
cp config.php.dist config.php
# Edit config.php with database credentials and settings

# Install PHP dependencies
composer install

# Install migrations
php migrator.php
```

### Frontend Development (assess2)
```bash
# Navigate to Vue source directory
cd assess2/vue-src

# Install dependencies
npm install

# Development server (hot reload)
npm run serve

# Production build
npm run build

# Legacy browser support build
npm run build-legacy
```

### Testing
```bash
# Run all tests
vendor/bin/codecept run

# Run unit tests only
vendor/bin/codecept run unit

# Run a specific test
vendor/bin/codecept run unit sanitizeTest

# Run with coverage
vendor/bin/codecept run --coverage --coverage-html
```

### JavaScript Minification
```bash
# Rebuild minified JavaScript files
cd util
php makeminjs.php
```

## Architecture Overview

### Core Architecture Pattern
IMathAS uses a traditional PHP architecture with modern Vue.js components:

1. **Entry Points**: Direct PHP files serve as controllers (e.g., `course/addassessment.php`, `admin/admin2.php`)
2. **Shared Logic**: `/includes/` contains utilities, database functions, and business logic
3. **Database**: PDO-based with custom query builders, migrations in `/migrations/`
4. **Sessions**: Custom database session handler for scalability

### Assessment System (assess2)
The modern assessment system uses a factory pattern for extensibility:

- **Question Generation**: `/assess2/questions/QuestionGenerator.php` orchestrates question creation
- **Answer Boxes**: Factory pattern in `/assess2/questions/answerboxes/` for different input types
- **Scoring**: Matching factory pattern in `/assess2/questions/scorepart/` for grading logic
- **Frontend**: Vue.js SPA in `/assess2/vue-src/` with Vuex state management

### Key Integration Points

1. **LTI Integration** (`/lti/`):
   - Supports both LTI 1.1 and 1.3
   - Uses JWT for LTI 1.3 authentication
   - Grade passback through `/lti/LTI_Grade_Update.php`

2. **File Storage**:
   - Local filesystem by default in `/filestore/`
   - AWS S3 support via `includes/filehandler.php`

3. **Math Processing**:
   - Math parser in `/assessment/mathparser.php`
   - Macro system in `/assessment/macros.php`
   - Library functions in `/assessment/libs/`

### Extension Points
The system provides hooks for customization without modifying core:

- Authentication hooks for SSO integration
- Course creation hooks for custom workflows
- User management hooks for external systems
- See `hooks.md` for full documentation

### Database Migration System
Located in `/migrations/`, uses timestamp-prefixed files:
```php
// migrations/20240709_add_showworkcutoff.php
$DBH->query("ALTER TABLE imas_assessments ADD showwork_after_cutoff TINYINT(1) NOT NULL DEFAULT 0");
```

### Security Considerations
- CSRF protection via `/csrfp/` (configurable)
- Input sanitization in `/includes/sanitize.php`
- Two-factor authentication support
- Database-driven session management for security and scalability

## Important Development Notes

1. **Question Code Execution**: Question code runs in an isolated context with restricted functions. See `/assessment/interpret5.php` for the sandbox implementation.

2. **Frontend State Management**: The assess2 Vue app uses a custom store pattern in `/assess2/vue-src/src/basicstore.js` rather than Vuex.

3. **Database Queries**: Use prepared statements via PDO. The codebase is transitioning from string concatenation to parameterized queries.

4. **File Uploads**: Handled through `/includes/filehandler.php` with support for local and S3 storage.

5. **Internationalization**: Frontend uses Vue i18n. Backend i18n is partial - see `/i18n/` directory.