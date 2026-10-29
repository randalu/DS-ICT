-- Ensure secondary admin account exists.
-- Username: imran
-- Password: imran@123 (stored as bcrypt hash)

INSERT IGNORE INTO admins (name, email, username, password_hash, is_active)
VALUES (
    'Imran',
    'imran.admin@islandculturetravels.com',
    'imran',
    '$2y$10$rdX/3jowvni7NGH5pihAse7g/BfEA25/.WKkP7fvQNhkXDLCB1Tuu',
    1
);
