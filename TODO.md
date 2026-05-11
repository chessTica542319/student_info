# Task TODO - Fix add_student redirect + transition and error capture

- [ ] Update `add_student.php` so successful insert does a clean redirect to `dashboard.php` (use `header()` + `exit`) and avoid JS redirect after output.
- [ ] (Optional) Implement a flash message via `$_SESSION['flash']` and show it on `dashboard.php` after redirect.
- [ ] Retest: submit add student with valid/invalid inputs and confirm no 1-second error/flicker; confirm redirect works.

