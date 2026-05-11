# TODO: Login password strength indicator (front-end only)

- [ ] Update `login.php` UI to include a password strength text element under the password input.
- [ ] Add JavaScript on `login.php` to compute password strength based on the same policy used by backend/register:
  - Requirements:
    - >= 6 characters
    - at least 1 lowercase [a-z]
    - at least 1 uppercase [A-Z]
    - at least 1 digit [0-9]
    - at least 1 special (non alnum)
- [ ] Strength mapping per typed input:
  - If user matches 1 requirement only => show **Weak password**
  - If matches 2-4 requirements but not all 5 => show **Moderate password**
  - If matches all requirements => show **Strong password**
  - If no typing yet => hide message
- [ ] Do not change backend hashing/verification logic.
- [ ] Keep existing eye toggle behavior intact.
- [ ] Manual test: type password with different patterns and verify message updates live.

