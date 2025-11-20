# Flag for Someone Else (Flag FSE)

Flag for Someone Else extends the Flag module by allowing authorized users to flag content on behalf of other users.

## Features

- **Flag content for other users** - Users with appropriate permissions can create flaggings on behalf of any user
- **Flexible user selection** - Search for existing users by username or email address
- **Create new users** - Administrators can create new user accounts while flagging
- **Dual link display** - Shows both the "Flag for someone else" link and standard flag link
- **AJAX support** - Optionally open the flagging form in a dialog or modal window
- **Granular permissions** - Automatically generates a permission for each flag

## Requirements

- Drupal 10.2+ or Drupal 11+
- [Flag module](https://www.drupal.org/project/flag)

## Installation

Install using Composer (recommended):

```bash
composer require drupal/flag_fse
drush en flag_fse
drush cr
```

Or install manually:
1. Download and extract to `modules/contrib/flag_fse`
2. Enable via admin UI or `drush en flag_fse`

## Configuration

### 1. Configure a Flag

1. Go to **Structure > Flags** (`admin/structure/flags`)
2. Create a new flag or edit an existing one
3. Under **Link type**, select **"For someone else"**
4. Configure the FSE options:
   - **Fallback plugin**: Link type for users without FSE permission (e.g., "AJAX link")
   - **Flag fse link text**: Text for the FSE link (default: "Flag for someone else")
   - **Flag confirmation message**: Message shown in the confirmation form
   - **Create flagging button text**: Submit button text
   - **Form behavior**: Choose "New page", "Dialog", or "Modal dialog"
5. Save the flag

### 2. Set Permissions

1. Go to **People > Permissions** (`admin/people/permissions`)
2. Find permissions like `flag fse [flag_id]`
3. Grant to appropriate roles (e.g., moderators, administrators)
4. Save permissions

## Usage

### For Authorized Users

Users with the `flag fse [flag_id]` permission will see:
1. A "Flag for someone else" link next to the standard flag link
2. Clicking opens a confirmation form where they can:
   - **Select existing user**: Search by username or email using autocomplete
   - **Create new user** (admins only): Enter username, email, optional password
3. System validates no duplicate flaggings exist
4. Flagging is created for the selected/created user

### For Regular Users

Users without FSE permission only see the standard flag link and can flag/unflag for themselves.

## Use Cases

- **Content moderation**: Flag problematic content on behalf of users who reported it offline
- **Event management**: Register participants to events for users who signed up offline
- **Support tickets**: Assign tickets to specific users
- **Collaborative bookmarking**: Add content to specific users' bookmarks

## Development

### Running Tests

```bash
# Unit tests
./vendor/bin/phpunit -c web/core web/modules/contrib/flag_fse/tests/src/Unit

# Kernel tests
./vendor/bin/phpunit -c web/core web/modules/contrib/flag_fse/tests/src/Kernel

# Functional tests
./vendor/bin/phpunit -c web/core web/modules/contrib/flag_fse/tests/src/Functional

# All tests
./vendor/bin/phpunit -c web/core web/modules/contrib/flag_fse/tests
```

### Code Standards

```bash
# Check coding standards
phpcs --standard=Drupal,DrupalPractice web/modules/contrib/flag_fse

# Fix automatically
phpcbf --standard=Drupal,DrupalPractice web/modules/contrib/flag_fse
```

## Troubleshooting

### "Flag for someone else" link not appearing

- Check that the user has the `flag fse [flag_id]` permission
- Verify the flag is configured with link type "For someone else"
- Clear cache: `drush cr`

### Form doesn't show new user fields

The "Create new user" option uses AJAX. Ensure:
- JavaScript is enabled in the browser
- AJAX is working properly on your site

### Search not finding users by email

- Check that the flag is using the correct entity reference handler
- Verify users are not blocked (for non-admin users)

## Support

- **Issue queue**: https://www.drupal.org/project/issues/flag_fse
- **Documentation**: https://www.drupal.org/docs/contributed-modules/flag-for-someone-else
- **Source code**: https://git.drupalcode.org/project/flag_fse

## Maintainers

- [Your Name] - [Your Drupal.org profile]

## License

This project is licensed under GPL-2.0-or-later.
