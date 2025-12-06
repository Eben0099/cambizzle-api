# Quick Reference - Réinitialisation de Mot de Passe

## 🔗 Endpoints

### Step 1: Forgot Password
```bash
POST /api/auth/forgot-password
{
  "phone": "+237677123456"
}

← Response:
{
  "success": true,
  "data": {
    "token": "abc123...",
    "expires_in": 86400
  }
}
```

### Step 2: Reset Password
```bash
POST /api/auth/reset-password
{
  "token": "abc123...",
  "password": "newPassword123"
}

← Response:
{
  "success": true,
  "message": "Mot de passe réinitialisé avec succès",
  "data": { "user": {...} }
}
```

---

## 📂 Key Files

| File | Purpose |
|------|---------|
| app/Services/AuthService.php | forgotPassword() + resetPassword() |
| app/Controllers/Api/AuthController.php | API endpoints |
| app/Config/Routes.php | Routes definition |
| app/Models/UserModel.php | Model allowedFields |
| app/Database/Migrations/ | DB migration |
| PASSWORD_RESET_DOCUMENTATION.md | Full documentation |
| PASSWORD_RESET_IMPLEMENTATION.md | Technical summary |
| postman/PASSWORD_RESET_COLLECTION.json | Postman tests |

---

## 🛠️ Installation

```bash
# Apply migration
php spark migrate

# Verify
php spark migrate:status
```

---

## ✅ Test

```bash
# Using cURL
curl -X POST http://localhost:8000/api/auth/forgot-password \
  -H "Content-Type: application/json" \
  -d '{"phone": "+237677123456"}'

# Using Postman
Import: postman/PASSWORD_RESET_COLLECTION.json

# Using Script
bash test_password_reset.sh
```

---

## 🔐 Security Features

- ✅ Cryptographic tokens (random_bytes(32))
- ✅ Auto-expiration (24 hours)
- ✅ Single-use tokens
- ✅ User enumeration protection
- ✅ Password validation (min 6 chars)
- ✅ Bcrypt hashing
- ✅ Complete logging

---

## 📝 Database Schema

```sql
ALTER TABLE users ADD COLUMN reset_token VARCHAR(255) NULL;
ALTER TABLE users ADD COLUMN reset_token_expires DATETIME NULL;
```

---

## 🚨 Error Codes

| Code | HTTP | Meaning |
|------|------|---------|
| VALIDATION_ERROR | 422 | Invalid params |
| RESET_TOKEN_INVALID | 400 | Invalid/expired token |
| INTERNAL_ERROR | 500 | Server error |

---

## 💡 Development Mode

In development, the 6-digit code is returned in response:
```json
{
  "data": {
    "code": "123456"  // Remove in production!
  }
}
```

---

## 🎯 Next Steps

1. ✅ Code implemented
2. ✅ Routes configured
3. ✅ Database migration ready
4. ✅ Documentation complete
5. ⏳ Integrate SMS provider
6. ⏳ Add rate limiting
7. ⏳ Deploy to production

---

## 📚 Full Documentation

- PASSWORD_RESET_DOCUMENTATION.md (detailed)
- PASSWORD_RESET_IMPLEMENTATION.md (technical)
- PASSWORD_RESET_SUMMARY.md (overview)
- API_DOCUMENTATION.md (other endpoints)

---

## 🆘 Troubleshooting

**Question:** Token says "invalid or expired"
**Answer:** Token expires after 24h, or already used once. Request new reset.

**Question:** SMS not received
**Answer:** SMS provider not configured. In dev, code returned in JSON response.

**Question:** Can't migrate
**Answer:** Check database permissions. Ensure you can execute ALTER TABLE.

**Question:** Still using old password after reset
**Answer:** Verify password was hashed. Check password_hash field in users table.

---

**Version:** 1.0
**Status:** ✅ Complete & Ready
**Last Updated:** 2024-01-15
