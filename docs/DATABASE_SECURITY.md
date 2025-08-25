# 🔒 Database Security Implementation

## Overview

This document describes the comprehensive database security implementation for the EarningsTable project, including minimal database permissions, encrypted backups, and connection pooling.

## 🛡️ Security Features Implemented

### 1. Minimal Database Permissions

#### Database Users Created
- **`earnings_app`** - Main application user with minimal required permissions
- **`earnings_readonly`** - Read-only user for reporting
- **`earnings_backup`** - Backup user with specific backup permissions

#### Permissions Matrix

| User | SELECT | INSERT | UPDATE | DELETE | CREATE | DROP | GRANT | LOCK TABLES |
|------|--------|--------|--------|--------|--------|------|-------|-------------|
| `earnings_app` | ✅ | ✅ | ✅ | ✅ | ❌ | ❌ | ❌ | ❌ |
| `earnings_readonly` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ |
| `earnings_backup` | ✅ | ❌ | ❌ | ❌ | ❌ | ❌ | ❌ | ✅ |

#### Security Restrictions
- **No DDL permissions** (CREATE, DROP, ALTER)
- **No administrative permissions** (GRANT, SHOW PROCESSLIST)
- **Connection limits** (max 10 concurrent connections)
- **Query limits** (max 1000 queries per hour)
- **SSL/TLS support** (when available)

### 2. Encrypted Backup System

#### Encryption Features
- **Algorithm**: AES-256-CBC
- **Key Management**: Secure key storage in `config/backup_key.php`
- **Compression**: Gzip compression before encryption
- **Integrity Verification**: SHA-256 checksums
- **Automatic Rotation**: Keeps last 10 backups

#### Backup Process
1. **SQL Dump**: `mysqldump` with transaction consistency
2. **Compression**: Gzip compression to reduce size
3. **Encryption**: AES-256-CBC with random IV
4. **Verification**: Decryption test to ensure integrity
5. **Rotation**: Automatic cleanup of old backups

#### File Structure
```
storage/backups/
├── backup_2024-01-01_02-00-00.sql.gz.enc  # Encrypted backup
├── backup_log.json                         # Backup history
└── backup_errors.log                       # Error log
```

### 3. Connection Pooling

#### Pool Configuration
- **Minimum Connections**: 2 (always available)
- **Maximum Connections**: 10 (prevents overload)
- **Connection Timeout**: 5 minutes (inactive connections)
- **Wait Timeout**: 30 seconds (wait for available connection)

#### Benefits
- **Performance**: Reuses connections instead of creating new ones
- **Resource Management**: Limits concurrent connections
- **Reliability**: Automatic cleanup of dead connections
- **Monitoring**: Detailed connection statistics

#### Usage Example
```php
// Automatic connection management
$autoConn = new AutoReleaseConnection();
$pdo = $autoConn->getConnection();

// Use connection
$stmt = $pdo->prepare("SELECT * FROM earnings_today");
$stmt->execute();

// Connection automatically released when script ends
```

## 🔧 Installation & Setup

### 1. Database User Setup

```bash
# Run as MySQL root user
mysql -u root -p < sql/create_secure_user.sql
```

**Important**: Change the default passwords in the SQL script before running!

### 2. Secure Backup Setup

```bash
# Run the setup script (as Administrator on Windows)
scripts\setup_secure_backup.bat
```

This will:
- Create necessary directories
- Test the backup system
- Set up Windows Task Scheduler
- Create monitoring scripts
- Generate documentation

### 3. Connection Pool Integration

Update your `config.php`:
```php
// Include connection pool
require_once __DIR__ . '/connection_pool.php';

// Use pooled connection
$pdo = DatabaseConnection::getConnection();
```

## 📊 Monitoring & Maintenance

### Backup Monitoring

```powershell
# Check backup health
powershell -ExecutionPolicy Bypass -File scripts\monitor_backups.ps1
```

### Connection Pool Statistics

```php
// Get pool statistics
$stats = DatabaseConnection::getPoolStats();
print_r($stats);
```

### Database User Monitoring

```sql
-- Check active connections
SHOW PROCESSLIST;

-- Check user privileges
SHOW GRANTS FOR 'earnings_app'@'localhost';

-- Monitor connection usage
SELECT 
    user,
    host,
    count(*) as connections
FROM information_schema.processlist 
WHERE user LIKE 'earnings_%'
GROUP BY user, host;
```

## 🔐 Security Best Practices

### 1. Password Management
- Use strong, unique passwords for each database user
- Rotate passwords regularly (every 90 days)
- Store passwords securely (not in version control)

### 2. Network Security
- Restrict database access to localhost only
- Use SSL/TLS connections when possible
- Implement firewall rules to block external access

### 3. File Permissions
```bash
# Secure file permissions
chmod 600 config/backup_key.php
chmod 750 storage/backups/
chmod 750 logs/
chmod 640 config/config.php
```

### 4. Regular Security Audits
- Review database user permissions monthly
- Monitor backup integrity
- Check connection pool statistics
- Review error logs for suspicious activity

## 🚨 Incident Response

### Backup Recovery
```bash
# List available backups
php scripts/secure_backup.php list

# Restore from backup
php scripts/secure_backup.php restore backup_2024-01-01_02-00-00.sql.gz.enc
```

### Connection Pool Issues
```php
// Force cleanup of all connections
ConnectionPool::getInstance()->closeAllConnections();

// Check pool health
$stats = DatabaseConnection::getPoolStats();
if ($stats['total_connections'] == 0) {
    // Pool is empty, restart application
}
```

### Database User Issues
```sql
-- Reset user password
ALTER USER 'earnings_app'@'localhost' IDENTIFIED BY 'new_strong_password';

-- Revoke and re-grant permissions
REVOKE ALL PRIVILEGES ON earnings_table.* FROM 'earnings_app'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON earnings_table.* TO 'earnings_app'@'localhost';
FLUSH PRIVILEGES;
```

## 📋 Compliance Checklist

- [ ] Database users have minimal required permissions
- [ ] Backup encryption is enabled and tested
- [ ] Connection pooling is implemented and monitored
- [ ] File permissions are properly set
- [ ] Monitoring scripts are configured
- [ ] Documentation is complete and up-to-date
- [ ] Security audit has been performed
- [ ] Incident response procedures are documented

## 🔄 Maintenance Schedule

### Daily
- Monitor backup completion
- Check connection pool statistics
- Review error logs

### Weekly
- Verify backup integrity
- Review database user activity
- Update security documentation

### Monthly
- Rotate database passwords
- Perform security audit
- Test disaster recovery procedures

### Quarterly
- Review and update security policies
- Conduct penetration testing
- Update security documentation

## 📞 Support

For security-related issues:
1. Check the logs in `logs/` directory
2. Review this documentation
3. Contact system administrator
4. Follow incident response procedures

---

**Last Updated**: January 2024  
**Version**: 1.0  
**Author**: EarningsTable Security Team
