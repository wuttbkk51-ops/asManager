# asManager (assManager)

> **Modern Multi-Tenant Repair Shop Management & ERP System**  
> ขับเคลื่อนด้วย **CodeIgniter 4.7.4**, **Bootstrap 5**, **AdminLTE 4**, **Tabulator Table** และระบบควบคุม **Serial / IMEI** ครบวงจร

---

## 🚀 ฟีเจอร์หลักของระบบ (Core Features)

1. **สถาปัตยกรรม Multi-Tenant & RBAC เต็มรูปแบบ**:
   - รองรับหลายร้านค้าแยกอิสระในระบบเดียว (Tenant Isolation)
   - สิทธิ์แบบลำดับชั้น: `Platform Superadmin`, `Shop Owner`, `Manager`, `Technician`, `Cashier`
   - ระบบ **Impersonation Mode** สลับบทบาทร้านค้าได้อย่างปลอดภัย
2. **ส่วนติดต่อผู้ใช้สไตล์ Minimalist (Native AdminLTE 4)**:
   - ออกแบบ UI เรียบง่าย สบายตา โปร่งโล่ง ไม่รกสายตา
   - รองรับ Dark Mode และ Light Mode อย่างสมบูรณ์
3. **จัดการสินค้าและอะไหล่ (Products & Inventory CRUD)**:
   - ตาราง **Tabulator Table** แบบ Single-line row พร้อม Live Search & Filter
   - **Reusable Offcanvas Drawer Form** เพิ่ม/แก้ไขสินค้ารวดเร็ว
   - รองรับ Auto-SKU Generation, Barcode Scanner, และ Progressive Disclosure
4. **ศูนย์กลางควบคุม Serial Number / IMEI (Serial Hub)**:
   - สร้างบาร์โค้ดรายชุด (Batch Auto-Gen)
   - สแกนรับสินค้าเข้าคลังต่อเนื่อง (Continuous Scanner Inbound)
   - ไทม์ไลน์ประวัติการใช้งานและงานซ่อม (Audit Trail Timeline)
   - พิมพ์สติกเกอร์บาร์โค้ด Code 128
5. **การตั้งค่าร้านค้า 4 ด้าน (Shop Settings Reorganization)**:
   - ข้อมูลทั่วไป & หัว/ท้ายเอกสาร
   - นโยบายงานซ่อม & ค่าบริการตรวจเช็ค
   - จุดขาย POS & กะเงินสดแคชเชียร์
   - คลังสินค้า & วิธีคิดต้นทุน (Moving Average, Latest, Highest, Manual)
6. **ระบบ 2 ภาษา (CodeIgniter 4 Localization)**:
   - รองรับ **ภาษาไทย (`th`)** และ **ภาษาอังกฤษ (`en`)**
   - ตัวเลือกภาษาเริ่มต้นของร้านค้าใน Settings และสลับภาษาได้ทันทีผ่าน Navbar

---

## 💻 วิธีติดตั้งเพื่อเปิดทำงานบนเครื่องอื่น / VS Code

### 1. โคลน Repository
```bash
git clone https://github.com/wuttbkk51-ops/asManager.git
cd asManager
```

### 2. ติดตั้ง Dependencies
```bash
composer install
```

### 3. ตั้งค่าสภาพแวดล้อม (Environment)
คัดลอกไฟล์ `env` เป็น `.env` (หากต้องการปรับแต่งพอร์ตหรือฐานข้อมูล):
```bash
cp env .env
```

### 4. รัน Database Migrations & Seeders
สร้างตารางและข้อมูลเริ่มต้นสำหรับทดสอบระบบ:
```bash
php spark migrate
php spark db:seed AuthSeeder
php spark db:seed ErpSeeder
```

### 5. เริ่มต้นเซิร์ฟเวอร์จำลอง (Local Server)
```bash
php spark serve --port 8080
```
เปิดบราวเซอร์ไปที่: [http://localhost:8080](http://localhost:8080)

---

## 🔑 ข้อมูลเข้าสู่ระบบเริ่มต้น (Default Credentials)

| บทบาท (Role) | Username | Password | ขอบเขตการใช้งาน |
|---|---|---|---|
| **Superadmin** | `superadmin` | `password` | จัดการทุกร้านค้าและแพลตฟอร์มส่วนกลาง |
| **Shop Owner** | `owner` | `password` | จัดการร้าน Demo Shop, ตั้งค่า, สินค้า, งานซ่อม |
| **Technician** | `tech` | `password` | งานซ่อมบำรุง, อะไหล่, Serial / IMEI |
| **Sales/Cashier**| `sales` | `password` | แคชเชียร์หน้าร้าน POS, เปิด-ปิดกะ |

---

## 🧪 การทดสอบระบบ (Automated Tests)

```bash
# ทดสอบโมดูล ERP ครบวงจร 19 หมวด
php spark test:erp

# ทดสอบระบบ Authentication & Multi-Tenant 6 หมวด
php spark test:auth
```

---

## 📄 License
MIT License
