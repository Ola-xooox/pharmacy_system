-- Update login_approvals table to add 'no_response' status
ALTER TABLE login_approvals 
MODIFY COLUMN status ENUM('pending', 'approved', 'declined', 'no_response') DEFAULT 'pending';
