# Directory purpose

Files in this directory will be executed in natural sorted order during project upgrades
via upgrade.php.

# Filename format

> version_description_text.php

Example: `120_add_session_column.php`

The version must be an integer.

# Returning migration status (REQUIRED)

All migration files must `return true;` on successful completions and `return false;` on failures.

This will allow migrations to be halted on errors.

## Notes

- Only files ending in `.php` will be used for migrations.
- For database operations, `$DBH` may be used within migration scripts.
- It is recommended to wrap the migration in a transaction
- The boolean `$isDBsetup` can be used to determine if the migration is being run as part of the initial database install.

## Special values of imas_dbscheme

- 1: Very old migration tracker
- 2: guest temp account tracker
- 5: last time tagcoursecleanup was run
- 6: last time sendzeros was run
- 7: last question ID processed by captiondata scraper