# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - 2025-XX-XX

### Added
- Initial release of Flag for Someone Else module
- "Flag for someone else" link type for Flag module
- User selection via autocomplete (search by username or email)
- New user creation directly from flagging form (admin only)
- Optional password generation for new users
- Email notification option for newly created users
- Configurable form behavior (new page, dialog, modal)
- Dynamic permission generation per flag (`flag fse [flag_id]`)
- Fallback link for users without FSE permission
- Duplicate flagging prevention
- Full test coverage (unit, kernel, and functional tests)
- Complete hook_help() documentation
- User search by email address in addition to username

### Security
- Permission-based access control
- Validation of duplicate flaggings
- User creation restricted to administrators
- CSRF protection via Drupal Form API

[Unreleased]: https://git.drupalcode.org/project/flag_fse/-/compare/1.0.0...HEAD
[1.0.0]: https://git.drupalcode.org/project/flag_fse/-/tags/1.0.0
