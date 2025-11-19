# Flag FSE — Flag for Someone Else

**Flag FSE** is an extension to the Drupal **Flag** module that allows a user to flag an entity **on behalf of another user**.

This is useful for delegated actions such as assistants flagging items for supervisors, moderators acting for users, or any shared workflow scenario. It could be useful for a task/volonteer manager for example. 

---

## Features

- **Flag for Someone Else action**  
  Adds a new action (`flag_fse`) that lets users create flaggings for other users.

- **Dynamic permissions per flag**  
  The module provides permissions like: flag fse {flag_id}

Only users with this permission can flag for someone else.

- **Confirmation form**  
Instead of flagging directly, a confirmation screen opens where the user selects the target an other user or create a
new user direct

- **Custom access control**  
A route access checker validates that the current user is allowed to perform the FSE action.

- **Advanced user selection**  
Custom `EntityReferenceSelection` plugin allowing search by username or email, including optional role filtering.

- **Customizable link templates**  
The module overrides flag link templates to display:
- the FSE link  
- a fallback standard flag link  
Both are themeable and separate.

---

## Requirements

- Drupal 10 or 11  
- Flag module

---

## Installation

1. Enable the module: drush en flag_fse
2. Assign permissions such as: flag fse my_flag_id
3. Configure your flag to use the **Flag FSE link type** if needed.
4. Use the “Flag for someone else” link in your UI.

---

## How It Works

1. User clicks “Flag for someone else”.
2. The module opens a confirmation form.
3. User selects another user or create a new one if enought permission.
4. A flagging entity is created on behalf of that chosen user.

---

## Templates

You can override the following Twig templates:

- `flag-fse.html.twig`
- `flag-fse-link.html.twig`
- `flag-fse-fallback-link.html.twig`
