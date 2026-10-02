# Rancangan ProjectHub

## Flow Sistem, Fitur, Status Implementasi, dan Fitur yang Masih Belum Tercapai

Dokumen ini menjadi blueprint pengembangan ProjectHub berdasarkan rancangan flow yang sudah dibahas dan kondisi fitur yang sudah terlihat pada source project.

> **Catatan:** status "sudah ada" berarti konsep/fiturnya sudah terlihat pada implementasi yang ditinjau. Status "perlu verifikasi" berarti belum cukup bukti untuk menyatakan seluruh aturan bisnisnya sudah benar. Status "belum tercapai" berarti fitur tersebut belum terlihat sebagai alur lengkap pada implementasi yang ditinjau.

---

# 1. Gambaran Besar Sistem

ProjectHub adalah aplikasi manajemen project dengan tiga role utama:

- **Admin**: mengelola pengguna dan akses dasar sistem.
- **Project Manager (PM)**: membuat dan mengelola project, workflow, anggota, task, serta melakukan review hasil kerja.
- **Employee**: mengerjakan task yang ditugaskan, mengelola subtask, mengunggah hasil kerja, dan mengirim task untuk direview.

Flow utamanya:

```text
ADMIN
  │
  └── Kelola User

PROJECT MANAGER
  │
  ├── Buat Project
  ├── Kelola Anggota
  ├── Atur Workflow
  ├── Buat Task
  ├── Assign Task ke Employee
  └── Review Hasil Kerja
           │
           ▼
EMPLOYEE
  │
  ├── Mengerjakan Task
  ├── Mengelola Subtask
  ├── Upload Attachment
  └── Submit for Review
           │
           ▼
     PM Review
       ┌───┴────┐
       ▼        ▼
    APPROVE   REVISION
       │        │
       ▼        └──→ Employee Mengerjakan Ulang
    COMPLETED
```

---

# 2. Role dan Tanggung Jawab

## 2.1 Admin

Flow:

```text
Login
  ↓
Dashboard Admin
  ↓
Kelola User
  ├── Tambah User
  ├── Edit User
  ├── Aktifkan / Nonaktifkan User
  └── Reset Password
```

Fokus Admin bukan mengerjakan project, tetapi menjaga user dan akses sistem.

### Status

- Login/logout: **sudah ada**
- Role middleware: **sudah ada**
- Dashboard Admin: **sudah ada**
- User CRUD: **sudah ada**
- Status user aktif/nonaktif: **sudah ada**
- Reset password: **sudah ada**
- Verifikasi seluruh security rule antar-role: **perlu verifikasi**

---

# 3. Project Manager

PM adalah pusat pengelolaan pekerjaan project.

Flow utama:

```text
Login PM
   ↓
Dashboard PM
   ↓
Daftar Project
   ↓
Pilih / Buat Project
   ↓
Detail Project
   ├── Anggota Project
   ├── Workflow / Tahapan
   ├── Tasks
   └── Progress Project
```

### Status

- Dashboard PM: **sudah ada**
- Project CRUD: **sudah ada**
- Detail project: **sudah ada**
- Kelola anggota project: **sudah ada**
- Workflow project: **sudah ada**
- Progress stage project: **sudah ada**
- Task management: **sudah ada sebagian**
- Review flow lengkap: **belum tercapai / perlu verifikasi**

---

# 4. Lifecycle Project

Project menggunakan status utama:

```text
DRAFT
  ↓
ACTIVE
  ↓
COMPLETED
```

Dengan jalur pembatalan:

```text
DRAFT ─────→ CANCELLED
ACTIVE ─────→ CANCELLED
```

## 4.1 Draft

Pada fase Draft, PM menyiapkan project:

- nama project
- deskripsi
- tanggal mulai
- deadline
- anggota project
- workflow
- task

Flow:

```text
Buat Project
    ↓
DRAFT
    ↓
Atur Project
    ├── Members
    ├── Workflow
    └── Tasks
```

## 4.2 Active

Project diaktifkan setelah siap.

```text
DRAFT
  ↓
ACTIVE
  ↓
Mulai Stage Pertama
```

## 4.3 Completed

Project selesai setelah pekerjaan yang menjadi bagian dari project sudah selesai sesuai aturan finalisasi.

```text
ACTIVE
  ↓
Stage Terakhir
  ↓
Semua Task Selesai
  ↓
PROJECT COMPLETED
```

## 4.4 Cancelled

Project yang dibatalkan tidak lagi melanjutkan pekerjaan operasional.

---

# 5. Workflow / Tahapan Project

PM dapat membuat tahapan project yang berurutan.

Contoh:

```text
1. Planning
2. UI/UX Design
3. Development
4. Testing
5. Deployment
```

Flow:

```text
Planning
   ↓
UI/UX Design
   ↓
Development
   ↓
Testing
   ↓
Deployment
```

Konsep status tampilan stage:

```text
✓ Stage sebelumnya   = Selesai
● Current Stage      = Sedang berjalan
○ Stage berikutnya   = Belum dimulai
```

Project memiliki satu **current stage**.

### Status

- Membuat stage: **sudah ada**
- Menentukan urutan stage: **sudah ada**
- Menentukan current stage: **sudah ada**
- Start stage: **sudah ada**
- Next stage: **sudah ada**
- Integrasi otomatis stage dengan status task: **perlu verifikasi**
- Rule bahwa stage hanya dapat maju ketika task terkait sudah selesai: **belum dipastikan / perlu dibangun bila belum ada**

---

# 6. Task Management

Setiap project dapat memiliki banyak task.

Contoh:

```text
PROJECT: Website Company

├── Membuat Landing Page
├── Membuat Login
├── Membuat Dashboard
├── Integrasi API
└── Testing
```

Konsep data task:

```text
Task
├── title
├── description
├── project
├── assignee
├── deadline
├── status
├── progress
├── subtasks
└── attachments
```

### Status Task

Rancangan status:

```text
PENDING
   ↓
IN PROGRESS
   ↓
WAITING REVIEW
   ├───────────────┐
   ↓               ↓
COMPLETED       REVISION
                   ↓
              IN PROGRESS
```

### Status saat ini

- Task list: **sudah ada**
- Task detail: **sudah ada**
- Assign task ke Employee: **ada konsep, perlu verifikasi penuh**
- Status task: **sebagian sudah ada / perlu verifikasi**
- Progress task: **perlu verifikasi**
- Submit for review: **belum terlihat sebagai flow lengkap**
- Approve/revision: **belum terlihat sebagai flow lengkap**

---

# 7. Employee Flow

Employee hanya fokus pada task yang ditugaskan kepadanya.

```text
Login
  ↓
Dashboard Employee
  ↓
My Tasks
  ↓
Pilih Task
  ↓
Task Detail
  ├── Informasi Task
  ├── Progress
  ├── Subtask
  ├── Attachment
  └── Submit for Review
```

Employee idealnya tidak dapat mengubah task milik employee lain.

### Status

- Dashboard/akses Employee: **sudah ada**
- Task list Employee: **sudah ada**
- Task detail Employee: **sudah ada**
- Batas akses hanya ke task sendiri: **perlu verifikasi**
- Flow pengerjaan sampai submit review: **belum lengkap**

---

# 8. Subtask

Subtask memecah task menjadi pekerjaan-pekerjaan kecil.

Contoh:

```text
TASK: Membuat Landing Page

├── Buat Navbar
├── Buat Hero Section
├── Buat Section Produk
├── Buat Footer
└── Responsive Mobile
```

Flow Employee:

```text
Task
 ↓
Subtask
 ↓
Update Status / Progress
 ↓
Semua pekerjaan siap
 ↓
Submit Review
```

### Status

- Relasi subtask pada database: **sudah terlihat**
- UI/detail subtask: **perlu verifikasi**
- Update subtask oleh Employee: **belum tercapai / perlu dibangun**
- Progress task berdasarkan subtask: **belum ditetapkan secara final**
- Validasi semua subtask sebelum submit: **belum ditetapkan**

---

# 9. Attachment

Attachment digunakan untuk menyertakan file hasil pekerjaan atau file pendukung.

Contoh:

```text
Task
├── Requirement.pdf
├── Design.png
├── Screenshot.png
└── hasil.zip
```

Flow:

```text
Employee
   ↓
Kerjakan Task
   ↓
Upload Attachment
   ↓
Submit for Review
```

### Status

- Relasi attachment pada database: **sudah terlihat**
- Upload attachment oleh Employee: **belum diverifikasi sebagai flow lengkap**
- Attachment pada review PM: **belum diverifikasi**
- Validasi file/ukuran/jenis file: **perlu verifikasi**

---

# 10. Revision Item

## 10.1 Konsep

**Revision Item harus diperlakukan sebagai entitas/record revisi yang jelas**, bukan hanya status `revision` pada task.

Tujuannya agar setiap putaran revisi dapat tercatat.

Contoh:

```text
Task
 ↓
PM Review
 ↓
Revision Item dibuat
 ├── Catatan revisi
 ├── Dibuat oleh PM
 ├── Tanggal revisi
 ├── Status revisi
 ├── Dikerjakan oleh Employee
 └── Selesai / belum selesai
```

## 10.2 Contoh Revision Item

```text
Revision Item #1
- Catatan: "Perbaiki validasi form login."
- Dibuat oleh: Project Manager
- Assigned to: Employee A
- Status: Open
```

Setelah Employee memperbaiki:

```text
Revision Item #1
- Status: Resolved
```

## 10.3 Beberapa Revision Item dalam satu Task

Satu task dapat memiliki lebih dari satu catatan revisi:

```text
TASK
 │
 ├── Revision Item #1
 │     └── Perbaiki validasi login
 │
 ├── Revision Item #2
 │     └── Sesuaikan tampilan mobile
 │
 └── Revision Item #3
       └── Tambahkan pesan error
```

## 10.4 Flow Revision Item

```text
Employee Submit
      ↓
WAITING REVIEW
      ↓
PM Review
      ↓
Request Revision
      ↓
Buat Revision Item
      ↓
REVISION
      ↓
Employee membaca Revision Item
      ↓
Employee memperbaiki
      ↓
Revision Item diselesaikan
      ↓
Submit Again
      ↓
PM Review Lagi
```

## 10.5 Revision Berulang

Revision dapat terjadi beberapa kali:

```text
WAITING REVIEW
      ↓
REVISION
      ↓
IN PROGRESS
      ↓
WAITING REVIEW
      ↓
REVISION
      ↓
IN PROGRESS
      ↓
WAITING REVIEW
      ↓
APPROVED
      ↓
COMPLETED
```

## 10.6 Fitur Revision Item yang Masih Perlu Dicapai

Bagian ini **belum boleh dianggap selesai hanya karena status task `revision` tersedia**.

Yang perlu dipastikan/dibangun:

- Model/table `revision_items` bila memang dipakai sebagai entitas tersendiri.
- Relasi `Task -> Revision Items`.
- PM dapat membuat Revision Item.
- PM dapat memberi catatan revisi yang jelas.
- Revision Item memiliki status, misalnya `open`, `in_progress`, `resolved`.
- Employee dapat melihat Revision Item miliknya.
- Employee dapat menandai item yang sudah diperbaiki.
- PM dapat memeriksa kembali hasil perbaikan.
- Satu task dapat memiliki banyak Revision Item.
- Riwayat revisi tetap tersimpan.
- Submit ulang menghubungkan kembali task dengan revision cycle yang sedang berjalan.
- Revision tidak langsung menghapus history revisi sebelumnya.

---

# 11. Review PM

Review adalah inti kontrol kualitas pekerjaan.

Flow:

```text
Employee
   ↓
Submit for Review
   ↓
WAITING REVIEW
   ↓
PM membuka Task
   ↓
PM melihat:
├── Detail Task
├── Progress
├── Subtask
├── Attachment
└── Revision Item
   ↓
PM memilih:
├── APPROVE
└── REQUEST REVISION
```

## Approve

```text
WAITING REVIEW
      ↓
APPROVE
      ↓
COMPLETED
```

## Request Revision

```text
WAITING REVIEW
      ↓
REQUEST REVISION
      ↓
Buat Revision Item
      ↓
REVISION
      ↓
Employee mengerjakan ulang
```

### Status

- Review queue: **belum diverifikasi**
- Approve action: **belum terlihat sebagai flow lengkap**
- Request revision: **belum terlihat sebagai flow lengkap**
- Revision Item: **belum tercapai sebagai fitur khusus**

---

# 12. Progress Task

Progress dan status harus dipisahkan.

Contoh:

```text
Progress = 100%
Status   = WAITING REVIEW
```

Artinya Employee sudah menyelesaikan pekerjaan menurut dirinya, tetapi PM belum menyetujuinya.

Setelah PM approve:

```text
Progress = 100%
Status   = COMPLETED
```

Progress idealnya tidak boleh dipakai untuk menggantikan status workflow.

### Yang masih perlu dipastikan

- Bagaimana progress dihitung.
- Apakah progress berdasarkan subtask.
- Apakah Employee dapat menginput progress manual.
- Apa yang terjadi jika progress 100% tetapi task belum direview.
- Apakah revision menurunkan progress atau hanya mengubah status.

---

# 13. Progress Project

Project dapat memiliki overall progress berdasarkan task.

Contoh:

```text
Task A = 100%
Task B = 100%
Task C = 50%
Task D = 0%
```

Project kemudian dapat menampilkan:

```text
Overall Progress = 62.5%
```

Namun aturan perhitungannya perlu dikunci supaya seluruh halaman memakai sumber nilai yang sama.

### Yang masih perlu diverifikasi

- Rumus overall progress.
- Apakah semua task memiliki bobot sama.
- Apakah task tertentu dapat memiliki bobot berbeda.
- Apakah Waiting Review dihitung selesai secara progress.
- Apakah Revision memengaruhi progress project.

---

# 14. Hubungan Stage dan Task

Idealnya setiap task berkaitan dengan stage project.

Contoh:

```text
DEVELOPMENT
├── Membuat Login
├── Membuat Dashboard
└── Integrasi API
```

Ketika task yang relevan sudah selesai:

```text
Development
    ↓
Task selesai
    ↓
Stage selesai
    ↓
Lanjut ke Testing
```

### Yang perlu diverifikasi

- Apakah task memiliki relasi ke stage.
- Apakah stage boleh dilanjutkan sebelum semua task selesai.
- Apakah PM dapat memaksa stage berpindah.
- Apakah stage terakhir otomatis membuat project completed atau tetap membutuhkan aksi PM.

---

# 15. Notification

Notification merupakan fitur pendukung untuk event penting.

Contoh event:

```text
PM assign task
   ↓
Employee mendapatkan notification
```

```text
Employee submit review
   ↓
PM mendapatkan notification
```

```text
PM request revision
   ↓
Employee mendapatkan notification
```

```text
PM approve task
   ↓
Employee mendapatkan notification
```

### Status

**Belum terlihat sebagai fitur lengkap / perlu dibangun.**

---

# 16. Activity History

History mencatat aktivitas penting.

Contoh:

```text
10:00 PM membuat task
10:30 Employee memulai task
13:00 Employee upload attachment
14:00 Employee submit review
15:00 PM membuat Revision Item
16:00 Employee menyelesaikan revision item
17:00 Employee submit ulang
18:00 PM approve task
```

Tujuan:

- mengetahui siapa melakukan apa
- mengetahui kapan aktivitas terjadi
- menyimpan riwayat revisi
- mempermudah audit pekerjaan

### Status

**Belum terlihat sebagai fitur lengkap / perlu dibangun.**

---

# 17. Aturan Bisnis Utama

Aturan ini harus menjadi acuan ketika coding.

### Rule 1 — Role

```text
Admin ≠ PM ≠ Employee
```

Setiap role hanya mengakses area yang menjadi tanggung jawabnya.

### Rule 2 — Employee hanya mengerjakan task yang ditugaskan kepadanya.

### Rule 3 — Employee tidak langsung menentukan task menjadi Completed.

### Rule 4 — Submit for Review mengubah task menjadi Waiting Review.

### Rule 5 — PM yang melakukan Approve atau Request Revision.

### Rule 6 — Request Revision harus dapat menghasilkan Revision Item.

### Rule 7 — Revision Item memiliki history dan statusnya sendiri.

### Rule 8 — Revision dapat berulang tanpa menghapus history sebelumnya.

### Rule 9 — Completed task tidak boleh diperlakukan seperti task yang masih aktif.

### Rule 10 — Project mengikuti workflow stage.

### Rule 11 — Project Completed hanya ketika syarat penyelesaian terpenuhi.

### Rule 12 — Project Completed/Cancelled tidak lagi menerima perubahan operasional biasa.

---

# 18. Flow Lengkap End-to-End

```text
LOGIN
  │
  ├── ADMIN
  │    └── Kelola User
  │
  ├── PM
  │    └── Kelola Project
  │
  └── EMPLOYEE
       └── Kelola Task sendiri

PM
 ↓
Create Project
 ↓
DRAFT
 ↓
Add Members
 ↓
Create Workflow
 ↓
Create Tasks
 ↓
Assign Tasks
 ↓
ACTIVE
 ↓
Start Stage
 ↓
Employee mulai bekerja
 ↓
Update Subtask
 ↓
Upload Attachment
 ↓
Submit for Review
 ↓
WAITING REVIEW
 ↓
PM Review
 ├───────────────┐
 ↓               ↓
APPROVE       REQUEST REVISION
 ↓               ↓
COMPLETED      Revision Item
                 ↓
              REVISION
                 ↓
             Employee Fix
                 ↓
             Submit Again
                 ↓
             PM Review Lagi
                 │
                 └────────→ APPROVE
                               ↓
                            COMPLETED
                               ↓
                        Stage dapat selesai
                               ↓
                         Next Stage
                               ↓
                           ...ulang...
                               ↓
                        Stage terakhir
                               ↓
                     Semua pekerjaan selesai
                               ↓
                      PROJECT COMPLETED
```

---

# 19. Fitur yang Sudah Terlihat Tercapai

Berdasarkan source yang sudah ditinjau:

- Authentication dasar.
- Login/logout.
- Password change.
- Role Admin / Project Manager / Employee.
- Middleware role/access dasar.
- Admin dashboard.
- User CRUD.
- Aktivasi/nonaktif user.
- Reset password.
- PM dashboard.
- Project CRUD.
- Detail project.
- Project members.
- Workflow/project stages.
- Current stage.
- Start stage.
- Next stage.
- Task area.
- Employee task area.
- Relasi project-member-task-subtask-attachment sudah terlihat pada struktur aplikasi.

---

# 20. Fitur yang Belum Tercapai atau Masih Perlu Diverifikasi

Urutan berikut dapat dijadikan backlog utama.

## Prioritas 1 — Task Lifecycle

- Pastikan status task lengkap.
- Pastikan assignment Employee benar.
- Pastikan Employee hanya melihat task yang menjadi tanggung jawabnya.
- Pastikan progress task konsisten.

## Prioritas 2 — Subtask

- UI subtask Employee.
- Update status/progress subtask.
- Hubungkan subtask dengan progress task jika aturan tersebut dipakai.

## Prioritas 3 — Attachment

- Upload file.
- Validasi file.
- Daftar file pada detail task.
- Akses file yang aman.

## Prioritas 4 — Submit for Review

- Tombol submit.
- Validasi task sebelum submit.
- Perubahan status menjadi Waiting Review.
- Lock/unlock bagian task sesuai status.

## Prioritas 5 — PM Review

- Review queue.
- Halaman review task.
- Approve.
- Request Revision.
- Catatan review.

## Prioritas 6 — Revision Item

- Entitas/record Revision Item.
- Catatan revisi.
- Status revision item.
- Employee dapat melihat dan mengerjakan item revisi.
- PM dapat memeriksa hasil revisi.
- History setiap revisi.
- Support multiple revision cycle.

## Prioritas 7 — Sinkronisasi Project

- Task selesai → stage dapat dianggap selesai.
- Stage selesai → project maju ke stage berikutnya.
- Stage terakhir selesai → project dapat diselesaikan.
- Perhitungan overall progress konsisten.

## Prioritas 8 — Notification & History

- Notification event penting.
- Activity history.
- Riwayat revisi.

## Prioritas 9 — Testing

Test minimal end-to-end:

```text
Admin
  ↓
Buat Employee
  ↓
PM membuat Project
  ↓
PM membuat Workflow
  ↓
PM membuat Task
  ↓
Assign Employee
  ↓
Employee mengerjakan
  ↓
Employee upload hasil
  ↓
Employee submit review
  ↓
PM request revision
  ↓
Revision Item dibuat
  ↓
Employee menyelesaikan revision
  ↓
Submit ulang
  ↓
PM approve
  ↓
Task Completed
  ↓
Stage selesai
  ↓
Project selesai
```

---

# 21. Definisi Project Dianggap Selesai

Project dapat dianggap benar-benar selesai ketika seluruh flow inti sudah berjalan tanpa celah:

```text
AUTH
 ✓
   ↓
ROLE & ACCESS
 ✓
   ↓
PROJECT
 ✓
   ↓
WORKFLOW
 ✓
   ↓
TASK
 ✓
   ↓
ASSIGNMENT
 ✓
   ↓
SUBTASK
 ✓
   ↓
ATTACHMENT
 ✓
   ↓
SUBMIT REVIEW
 ✓
   ↓
PM REVIEW
 ✓
   ↓
REVISION ITEM
 ✓
   ↓
APPROVE / REVISION LOOP
 ✓
   ↓
TASK COMPLETED
 ✓
   ↓
STAGE COMPLETED
 ✓
   ↓
PROJECT COMPLETED
 ✓
```

Selain itu, seluruh akses harus aman berdasarkan role, dan tidak boleh ada user yang dapat mengakses task/project di luar kewenangannya.

---

# 22. Kesimpulan Rancangan

Inti ProjectHub bukan hanya CRUD project dan task. Inti sistem adalah **workflow pekerjaan yang terkontrol**:

```text
PLAN
 ↓
CREATE PROJECT
 ↓
SET WORKFLOW
 ↓
ASSIGN TEAM
 ↓
CREATE TASK
 ↓
EMPLOYEE WORK
 ↓
SUBMIT
 ↓
PM REVIEW
 ↓
┌──────────────┐
│              │
APPROVE      REVISION
│              │
│              └──→ REVISION ITEM → WORK AGAIN
│
↓
TASK COMPLETED
 ↓
STAGE COMPLETED
 ↓
NEXT STAGE
 ↓
SEMUA STAGE SELESAI
 ↓
PROJECT COMPLETED
```

**Revision Item menjadi bagian penting dari rancangan**, karena revision bukan hanya perubahan status task menjadi `revision`, tetapi harus memiliki catatan, status, history, dan hubungan yang jelas dengan proses review.
