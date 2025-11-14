# Victoria AI - Deployment Guide

## 📋 Overview
Victoria AI uses a **hybrid authentication system**:
- **Firebase Auth** → Authentication (Login/Register)
- **MySQL Database** → Data storage, analytics, activity logs

This setup provides:
- ✅ Firebase's reliable authentication
- ✅ Full ownership of user data
- ✅ Advanced analytics and reporting
- ✅ Unlimited storage capacity

---

## 🔧 VPS Configuration

### Server Details
- **URL**: https://bkuteam.site
- **OS**: Windows Server 2022
- **Web Server**: IIS 10.0
- **PHP**: 8.4.14
- **MySQL**: 8.0.44
- **phpMyAdmin**: https://pma.bkuteam.site

### Required PHP Extensions
```ini
extension=pdo_mysql
extension=mysqli
extension=openssl
```

---

## 🗄️ MySQL Database Setup

### Database: `victoria_ai`

**Tables:**
1. `users` - User profiles (firebase_uid, email, display_name, etc.)
2. `auth_tokens` - OAuth tokens for third-party services
3. `activity_logs` - User activity tracking (login, logout, actions)
4. `chat_history` - AI chat conversations
5. `user_preferences` - User settings and preferences

**SQL Schema**: See `php/database/schema.sql`

---

## 📁 File Structure

```
E:\project\AI-HACKATHON\
├── pages/
│   └── auth/
│       ├── signin.html          ✅ MySQL sync integrated
│       ├── register.html        ✅ MySQL sync integrated
│       └── forgot-password.html
├── js/
│   └── mysql-api-client.js      ✅ API client configured
├── php/
│   ├── config/
│   │   └── database.php         🔒 DB credentials
│   ├── helpers/
│   │   ├── response.php         📤 CORS, JSON responses
│   │   └── validator.php        ✅ Rate limiting
│   ├── api/
│   │   ├── test-connection.php  ✅ Health check
│   │   ├── auth/
│   │   │   ├── sync-user.php    ✅ Register/login sync
│   │   │   ├── verify-token.php ✅ Token verification
│   │   │   └── update-token.php ✅ OAuth token storage
│   │   └── logs/
│   │       └── activity-log.php ✅ Activity tracking
│   └── test/
│       └── test-api-dashboard.html  🧪 API testing
└── bat/
    └── *.bat                    🔧 Deployment scripts
```

---

## 🚀 Deployment Steps

### 1. Upload Files to VPS

**Upload entire `php/` folder to:**
```
C:\inetpub\wwwroot\php\
```

**Structure on VPS:**
```
C:\inetpub\wwwroot\
├── index.html (your main site)
├── php\
│   ├── config\database.php
│   ├── helpers\*.php
│   ├── api\*.php
│   └── test\*.html
```

### 2. Configure Database Connection

Edit `php/config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'victoria_ai');
define('DB_USER', 'root');
define('DB_PASS', 'YOUR_SECURE_PASSWORD'); // ⚠️ Change this!
```

### 3. Import Database Schema

```bash
mysql -u root -p victoria_ai < php/database/schema.sql
```

Or via phpMyAdmin:
1. Go to https://pma.bkuteam.site
2. Select `victoria_ai` database
3. Click "Import" → Choose `schema.sql`

### 4. Test API Endpoints

Open: https://bkuteam.site/php/test/test-api-dashboard.html

**Expected Results:**
- ✅ Test Connection: SUCCESS
- ✅ Sync User (Register): SUCCESS
- ✅ Sync User (Login): SUCCESS
- ✅ Activity Log: SUCCESS
- ✅ Update Token: SUCCESS
- ⚠️ Verify Token: FAIL (expected with mock token)

### 5. Update Frontend Configuration

Edit `js/mysql-api-client.js`:
```javascript
const MYSQL_API_BASE_URL = 'https://bkuteam.site/php/api';
const MYSQL_SYNC_ENABLED = true; // Set false to disable MySQL sync
```

---

## 🔒 Security Checklist

### Before Production:

1. **Change MySQL Password**
   ```sql
   ALTER USER 'root'@'localhost' IDENTIFIED BY 'strong_password_here';
   ```

2. **Update CORS Settings**
   
   Edit `php/helpers/response.php`:
   ```php
   // Change from:
   header('Access-Control-Allow-Origin: *');
   
   // To:
   header('Access-Control-Allow-Origin: https://bkuteam.site');
   ```

3. **Enable Rate Limiting**
   
   Ensure `php/cache/` folder exists and is writable:
   ```powershell
   mkdir C:\inetpub\wwwroot\php\cache
   icacls C:\inetpub\wwwroot\php\cache /grant "IIS AppPool\DefaultAppPool:(OI)(CI)F"
   ```

4. **Secure Firebase Config**
   
   Consider moving Firebase config to environment variables or secure backend endpoint.

5. **Enable HTTPS Only**
   
   In `php/helpers/response.php`:
   ```php
   // Force HTTPS
   if ($_SERVER['HTTPS'] !== 'on') {
       header('HTTP/1.1 403 Forbidden');
       exit('HTTPS required');
   }
   ```

---

## 🧪 Testing User Registration

### Test Flow:
1. Go to https://bkuteam.site/pages/auth/register.html
2. Register new user (email/password or Google OAuth)
3. Check logs in browser console:
   - ✅ "User profile saved to Firestore"
   - ✅ "User synced to MySQL"
4. Verify in phpMyAdmin:
   - Check `users` table for new record
   - Check `activity_logs` for 'register' action

### Expected Console Output:
```
✅ User profile saved to Firestore
✅ User synced to MySQL
✅ Activity logged: register
```

---

## 📊 API Endpoints Reference

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/php/api/test-connection.php` | GET | Health check |
| `/php/api/auth/sync-user.php` | POST | Register/login sync |
| `/php/api/auth/verify-token.php` | POST | Verify Firebase token |
| `/php/api/auth/update-token.php` | POST | Store OAuth tokens |
| `/php/api/logs/activity-log.php` | POST | Log user activities |

### Sample Request: Sync User
```javascript
POST https://bkuteam.site/php/api/auth/sync-user.php
Content-Type: application/json

{
  "firebaseUid": "abc123...",
  "email": "user@example.com",
  "displayName": "John Doe",
  "photoURL": "https://...",
  "idToken": "eyJhbGci..."
}
```

### Sample Response:
```json
{
  "success": true,
  "message": "User synced successfully",
  "data": {
    "user_id": 4,
    "firebase_uid": "abc123...",
    "email": "user@example.com",
    "is_new_user": true
  }
}
```

---

## 🐛 Troubleshooting

### Issue: "MySQL sync failed"
**Solution:** 
- Check database connection in `php/config/database.php`
- Test: https://bkuteam.site/php/api/test-connection.php
- Verify MySQL service is running

### Issue: "Rate limit exceeded"
**Solution:**
- Create `php/cache/` folder
- Set write permissions for IIS user
- Or disable rate limiting in `php/helpers/validator.php`

### Issue: 500 Internal Server Error
**Solution:**
1. Enable detailed errors in `php.ini`:
   ```ini
   display_errors = On
   error_reporting = E_ALL
   ```
2. Check IIS logs: `C:\inetpub\logs\LogFiles\`
3. Test via PHP CLI:
   ```bash
   php C:\inetpub\wwwroot\php\api\test-connection.php
   ```

### Issue: CORS errors in browser
**Solution:**
- Add your domain to `php/helpers/response.php`
- Ensure CORS headers are sent before any output
- Check browser console for specific error message

---

## 📝 Maintenance

### Daily Checks:
- Monitor `activity_logs` table for unusual activity
- Check disk space (chat history can grow)
- Review error logs

### Weekly Tasks:
- Backup MySQL database
- Clean old activity logs (>90 days)
- Update dependencies if needed

### Backup Command:
```bash
mysqldump -u root -p victoria_ai > backup_victoria_ai_$(date +%Y%m%d).sql
```

---

## 📧 Support

**Issues Found?**
- Check test dashboard: https://bkuteam.site/php/test/test-api-dashboard.html
- Review browser console for error messages
- Test individual API endpoints

**Database Access:**
- phpMyAdmin: https://pma.bkuteam.site
- User: root
- Password: [Your MySQL password]

---

## ✅ Deployment Checklist

- [ ] Upload `php/` folder to VPS
- [ ] Import database schema
- [ ] Update `php/config/database.php` with credentials
- [ ] Test all API endpoints (dashboard)
- [ ] Change MySQL root password
- [ ] Update CORS settings (production domain only)
- [ ] Set up `php/cache/` folder with permissions
- [ ] Enable HTTPS redirect
- [ ] Test user registration flow
- [ ] Test user login flow
- [ ] Verify MySQL sync in database
- [ ] Check activity logs
- [ ] Set up database backup schedule

---

**Last Updated:** January 2025
**Status:** ✅ All APIs tested and working
**Frontend Integration:** ✅ signin.html, register.html
