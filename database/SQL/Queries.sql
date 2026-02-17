-- A set of queries for managing the Jaws database.

----------------------------------------------------------------------------
-- WARNING: ALWAYS BACKUP THE DATABASE BEFORE RUNNING ANY OF THESE QUERIES.

-- WARNING: Run the corresponding SELECT before executing,
-- in order to check that the intended records will be affected.
----------------------------------------------------------------------------

-- MANAGE ADMIN RIGHTS.

-- Select records in the users table whose display_name in the crews table
-- matches the given admin_display_name.

SELECT email, is_admin
FROM users
WHERE email IN (
    SELECT email FROM crews WHERE display_name = 'admin_display_name'
);

-- Set the selected users' is_admin field to 1.

UPDATE users
SET is_admin = 1
WHERE email IN (
    SELECT email FROM crews WHERE display_name = 'admin_display_name'
);

-----------------------

-- MANAGE CREW SKILL VALUE.

-- Select records in the crews table whose display_name
-- matches the given display_name.

SELECT email, skill
FROM crews
WHERE display_name = 'crew_display_name';

-- Set the selected crews' skill level;
-- 0 = inexperienced
-- 1 = competent crew
-- 2 = competent first mate

UPDATE crews
SET skill = 'level'
WHERE display_name = 'crew_display_name';

-----------------------
