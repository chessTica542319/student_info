# Sidebar Profile Settings Persistent Task

## Goal
Modify sidebar so that **Profile Settings** appears on pages: honor_students.php, fail_students.php, add_student.php, update_student.php (and any other non-dashboard sidebar pages), and the sidebar styling matches dashboard style. No backend changes.

## Information gathered
- `index.php` already includes a sidebar link to `profile_settings.php`.
- `honor_students.php`, `add_student.php`, `fail_students.php`, and `update_student.php` currently **do not** include the Profile Settings link.
- `profile_settings.php` already includes it.

## Plan (code updates)
1. Update sidebar markup in these files to include:
   - `<a href="profile_settings.php" class="nav-link ...">Profile Settings</a>`
2. Ensure the active class is applied correctly:
   - On `profile_settings.php` keep `active`.
   - On other pages, not `active`.
3. Make the sidebar look consistent with dashboard (`index.php`):
   - Use the same structure/order + ensure the `.main-content { margin-left: 260px; }` spacing remains consistent.
   - Only minimal CSS changes if needed to match the dashboard sidebar.
4. Validate the pages load without PHP errors.

## Dependent files to edit
- `honor_students.php`
- `add_student.php`
- `fail_students.php`
- `update_student.php`

## Testing / followup steps
- Open each page in browser: `index.php`, `add_student.php`, `honor_students.php`, `fail_students.php`, `update_student.php`, `profile_settings.php`.
- Verify sidebar is visible and the Profile Settings link stays visible while navigating.
- Confirm no backend logic changed (only HTML/CSS updates).

