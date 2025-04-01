# TRON Wallets System

## Overview
The TRON Wallets System allows administrators to manage TRON wallets for users, with automatic fund transfer to a master wallet. The system supports both TRX and USDT (TRC20) tokens.

## Features
- Master wallet management
- Automatic generation of derived wallets
- Real-time balance monitoring
- Automatic fund transfer to master wallet
- Support for TRX and USDT (TRC20)
- Transaction monitoring and logging

## Setup

### 1. Environment Configuration
Add the following variables to your `.env` file:
```env
TATUM_API_KEY=your_api_key_here
TATUM_BASE_URL=https://api.tatum.io/v3
TATUM_USDT_CONTRACT_ADDRESS=TR7NHqjeKQxGTCi8q8ZY4pL8otSzgjLj6t
```

### 2. Database Migration
Run the migration to create the necessary tables:
```bash
php artisan migrate
```

## Usage

### Creating Master Wallet
```bash
php artisan tron:master-wallet create
```
This command creates a master wallet that will be used to generate derived wallets.

### Generating Derived Wallets
```bash
# Generate 1000 wallets (default)
php artisan tron:master-wallet generate

# Generate specific number of wallets
php artisan tron:master-wallet generate --count=500
```

### Monitoring Transactions
```bash
# Start monitoring with default interval (60 seconds)
php artisan tron:monitor

# Start monitoring with custom interval
php artisan tron:monitor --interval=30
```

### Checking Status
```bash
php artisan tron:master-wallet status
```
This command shows:
- Total number of wallets
- Available wallets
- Assigned wallets
- Master wallet information

## System Behavior

### Automatic Fund Transfer
When funds are received on any derived wallet:
1. System detects the incoming transaction
2. Automatically transfers funds to master wallet
3. Leaves 1 TRX for transaction fees
4. Logs all operations

### Supported Tokens
- TRX (native token)
- USDT (TRC20)

### Security Features
- Private keys are stored securely in the database
- Transactions are signed automatically
- System maintains transaction history
- Prevents duplicate transaction processing

## Server Deployment

### 1. Supervisor Configuration
Create file `/etc/supervisor/conf.d/tron-monitor.conf`:
```ini
[program:tron-monitor]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/q1f.laravel/artisan tron:monitor
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/q1f.laravel/storage/logs/tron-monitor.log
```

### 2. Activate Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start tron-monitor:*
```

### 3. Monitoring Logs
```bash
# Application logs
tail -f /var/www/q1f.laravel/storage/logs/laravel.log

# Transaction monitor logs
tail -f /var/www/q1f.laravel/storage/logs/tron-monitor.log
```

## Troubleshooting

### Common Issues

1. **Migration Errors**
If you encounter migration errors:
```bash
# Check migration status
php artisan migrate:status

# Run specific migration
php artisan migrate --path=database/migrations/2025_03_31_175259_create_tron_wallets_table.php
```

2. **Supervisor Issues**
```bash
# Check supervisor status
sudo supervisorctl status

# Restart supervisor
sudo supervisorctl restart all
```

3. **Permission Issues**
```bash
# Fix permissions
sudo chown -R www-data:www-data /var/www/q1f.laravel
sudo chmod -R 775 /var/www/q1f.laravel/storage /var/www/q1f.laravel/bootstrap/cache
```

### Logging
All operations are logged in:
- `storage/logs/laravel.log` - General application logs
- `storage/logs/tron-monitor.log` - Transaction monitoring logs

## Best Practices

1. **Regular Monitoring**
- Check logs daily
- Monitor wallet balances
- Verify transaction processing

2. **Security**
- Keep master wallet private key secure
- Regularly backup database
- Monitor for suspicious activities

3. **Performance**
- Adjust monitoring interval based on needs
- Monitor system resources
- Keep logs clean

4. **Maintenance**
- Regular database backups
- Monitor disk space
- Check for failed transactions

## API Integration

### Assigning Wallet to User
```php
$wallet = $tatumService->getAvailableWallet();
if ($wallet) {
    $tatumService->assignWalletToUser($wallet, $user);
}
```

### Getting Wallet Balance
```php
$balance = $tatumService->getBalance($wallet->address);
$usdtBalance = $tatumService->getUsdtBalance($wallet->address);
```

## Support
For technical support or questions, please contact the development team. 