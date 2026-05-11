- [x] Update login.php registration to insert into userAccount(username, hashpassword)
- [x] Update login.php login verification to select hashpassword by username from userAccount
- [x] Add password policy validation for registration (>=6, >=1 special, >=1 upper, >=1 lower, >=1 number)
- [x] Keep hardcoded admin/admin123 login working without DB
- [ ] Ensure session handling remains compatible with auth.php/dashboard pages (already likely OK; will confirm via run)
- [ ] Manual test checklist (register invalid/valid, login with DB user, login with admin/admin123)


