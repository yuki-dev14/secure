# SECURE 4Ps - Database Setup Instructions
# Run these commands one by one in PowerShell

# Step 1: Add PostgreSQL to PATH (adjust version if needed)
$env:PATH += ";C:\Program Files\PostgreSQL\18\bin"

# Step 2: Create the database (enter your postgres password when prompted)
psql -U postgres -c "CREATE DATABASE secure_4ps;"

# Step 3: Update your .env with the correct password
# Edit: c:\Users\Euclid\Desktop\Secure\.env
# Change: DB_PASSWORD=your_actual_password

# Step 4: Run migrations and seed
# cd c:\Users\Euclid\Desktop\Secure
# php artisan migrate:fresh --seed

# Default Staff Accounts after seeding:
# Superadmin: superadmin@secure4ps.dswd.gov.ph / Admin@1234!
# Admin:      admin@secure4ps.dswd.gov.ph      / Admin@1234!
# Verifier:   verifier@secure4ps.dswd.gov.ph   / Verify@1234!
# Officer:    officer@secure4ps.dswd.gov.ph    / Officer@1234!
