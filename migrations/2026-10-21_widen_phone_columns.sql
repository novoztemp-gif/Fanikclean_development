-- A worker's phone/guardian_phone, user's phone/guardian_phone, and client's
-- mobile were all VARCHAR(15). Production hit this directly: adding a worker
-- with a phone number over 15 characters threw an uncaught PDOException,
-- which crashes the whole request (display_errors is off, so it looked like
-- the app froze/"paused" rather than a specific form error). Widening to 25
-- gives real-world room (country code, spaces, dashes, a short extension)
-- without being unbounded. Non-destructive: ALTER COLUMN ... TYPE VARCHAR(n)
-- to a larger n never truncates or rejects existing data.
ALTER TABLE clients ALTER COLUMN mobile TYPE VARCHAR(25);
ALTER TABLE users ALTER COLUMN phone TYPE VARCHAR(25);
ALTER TABLE users ALTER COLUMN guardian_phone TYPE VARCHAR(25);
ALTER TABLE workers ALTER COLUMN mobile TYPE VARCHAR(25);
ALTER TABLE workers ALTER COLUMN guardian_phone TYPE VARCHAR(25);
