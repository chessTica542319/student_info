# TODO_login_UI.md

## Goal
Update auth UI so **Login appears first** and **Register is on its own page**, with clean/simpler layout and exact required texts.

## Edit plan (implemented)
- [ ] Modify `login.php` to show only the login form by default (remove register/register-switch UI).
- [ ] Update login header/texts to match required texts:
  - Title: `login to your account`
  - Username label: `username`
  - Password label: `password`
  - Below inputs (exact): `default admin  admin123`
  - Footer (exact): `Dont have an account then Register` linking to `register.php`
  - Button text: `Login`
- [ ] Modify `register.php` to show only the register form:
  - Title (exact): `register to your Account`
  - Username label: `username`
  - Password label: `password`
  - No additional instruction text under password.
  - Footer (exact): `Already have an accout and Login` linking to `login.php`
  - Button text: `Register`
- [ ] Add JavaScript so when password is weak, show error text `Weak password` (only for register page), based on the same policy logic.

## Testing checklist
- [ ] Open `login.php` => only login form visible.
- [ ] Click `Register` => navigates to `register.php`.
- [ ] Open `register.php` => only register form visible.
- [ ] Enter weak password => `Weak password` shows via JS.
- [ ] Footer links work.

