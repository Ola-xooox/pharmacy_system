-- Add your email to an existing user for OTP testing
-- Choose one of these options:

-- Option 1: Update admin user with your email
UPDATE users SET email = 'unstoppabegaming@gmail.com' WHERE username = 'admin';

-- Option 2: Update Customer Manager with your email  
-- UPDATE users SET email = 'unstoppabegaming@gmail.com' WHERE username = 'Customer Manager';

-- Option 3: Update POS user with your email
-- UPDATE users SET email = 'unstoppabegaming@gmail.com' WHERE username = 'POS';

-- Verify the update
SELECT username, email, role FROM users WHERE email = 'unstoppabegaming@gmail.com';
