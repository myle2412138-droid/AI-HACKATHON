# 🔧 Victoria AI - Quick Fix Guide

## 🔒 Mixed Content Error (HTTPS/HTTP)

### **Triệu chứng:**
```
Mixed Content: The page at 'https://...' was loaded over HTTPS, 
but requested an insecure resource 'http://...'. 
This request has been blocked
```

### **Nguyên nhân:**
Trang web dùng HTTPS nhưng API call dùng HTTP → Browser chặn vì bảo mật

### **Giải pháp:**
Đổi TẤT CẢ URL từ `http://` → `https://`

### **Files đã fix:**
- ✅ `pages/dashboard/settings.html` - API_BASE
- ✅ `pages/dashboard/index.html` - API_BASE  
- ✅ `php/test/test-profile-complete.html` - BASE

### **Check list:**
```javascript
// Trong mọi file JavaScript, đảm bảo dùng HTTPS:
const API_BASE = 'https://bkuteam.site/php/api/profile';
```

---

## 🧪 Test Sau Khi Fix

### Test trong Console:
```javascript
// Mở Console (F12), chạy:
fetch('https://bkuteam.site/php/api/profile/test-simple.php')
  .then(r => r.json())
  .then(d => console.log('✅', d))
  .catch(e => console.error('❌', e))
```

**Kỳ vọng**: 
```json
✅ {
  "success": true,
  "message": "PHP is working!",
  "php_version": "8.4.14"
}
```

---

## 🎯 Full Test Flow

### 1. Test Settings Page
```
URL: https://bkuteam.site/pages/dashboard/settings.html
Action: Đăng nhập → Điền form → Click "Lưu"
Expected: Toast "Cập nhật thành công!" + redirect Dashboard
```

### 2. Test Dashboard Banner
```
URL: https://bkuteam.site/pages/dashboard/index.html
Action: Đăng nhập với user chưa có profile đủ
Expected: Banner vàng xuất hiện "Hồ sơ chưa hoàn thiện"
Console log: "✅ Banner displayed"
```

### 3. Test Banner Disappears
```
Action: Hoàn thiện profile → Quay Dashboard
Expected: Banner KHÔNG xuất hiện
Console log: "✅ Profile is complete - no banner needed"
```

---

## 🐛 Common Issues

### Issue 1: CORS Error
**Triệu chứng**: `Access to fetch blocked by CORS policy`
**Fix**: Đảm bảo PHP files có header:
```php
header('Access-Control-Allow-Origin: *');
```

### Issue 2: 404 Not Found
**Triệu chứng**: API endpoint trả về 404
**Fix**: 
- Kiểm tra file đã upload đúng folder chưa
- URL phải đúng: `/php/api/profile/...`

### Issue 3: 500 Internal Server Error  
**Triệu chứng**: API crash
**Fix**: Check PHP error log hoặc thêm:
```php
ini_set('display_errors', 1);
error_reporting(E_ALL);
```

### Issue 4: Token Expired (401)
**Triệu chứng**: `Unauthorized` sau 1 tiếng
**Fix**: Logout và login lại để refresh token

---

## ✅ Final Checklist

- [x] Fix Mixed Content (HTTP → HTTPS)
- [ ] Upload tất cả files lên server
- [ ] Test đăng ký với role selection
- [ ] Test settings page lưu thành công
- [ ] Test banner xuất hiện khi chưa đủ info
- [ ] Test banner biến mất khi đã đủ info
- [ ] Test với cả Student và Lecturer roles

---

## 🚀 Ready to Deploy!

Sau khi upload files đã fix, system sẽ hoạt động như sau:

```
User Register (với role)
    ↓
Dashboard loads
    ↓
Check profile complete API
    ↓
If incomplete → Show banner ⚠️
If complete → No banner ✅
    ↓
User clicks "Hoàn thiện ngay"
    ↓
Settings page → Fill form → Save
    ↓
Update API → Database
    ↓
Redirect to Dashboard
    ↓
Check again → Banner disappears ✅
```

---

**Tất cả đã sẵn sàng! Upload files lên và test thôi!** 🎉
