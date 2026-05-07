# TODO - Enhanced dashboard search (no UI change)

## Info gathered
- `index.php` dashboard search currently applies `LIKE` against: `f_name`, `m_name`, `l_name`, `course`, `address`, and `gwa` (CAST to CHAR).
- Requirement update: search should support *full name combinations* and *unstructured name queries* (e.g. `first+middle`, `last+middle`, `last+first`, `last middle`, `complete name`, etc.) while keeping UI unchanged.

## Plan
1. In `index.php`, extend the search SQL to also match against concatenations representing common name orderings:
   - `CONCAT_WS(' ', f_name, m_name)`
   - `CONCAT_WS(' ', l_name, m_name)`
   - `CONCAT_WS(' ', l_name, f_name)`
   - `CONCAT_WS(' ', l_name, m_name, f_name)`
   - `CONCAT_WS(' ', f_name, m_name, l_name)`
   - Also keep existing per-column `LIKE` checks.
2. Keep search behavior as plain text:
   - Continue using `LIKE LOWER(?)` and bind the same user search string.
3. Ensure prepared statement parameter count and `bind_param` signature are updated correctly.
4. Verify other functionality is unchanged (table rendering, honor/fail counts based on `gwa`).

## Done
- (Previously) Added `gwa` decimal matching via `CAST(gwa AS CHAR) LIKE ?`.

