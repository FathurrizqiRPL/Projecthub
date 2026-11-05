# ProjectHub — Handoff Teknis & Rancangan yang Sesuai Source

## Scope penyelesaian terkini (5 November 2026)

Dokumen ini adalah snapshot teknis lama. Untuk backlog saat ini, hanya tiga fitur berikut yang perlu diselesaikan:

1. PM dapat mengedit task.
2. Employee dapat mengedit judul dan deskripsi subtask.
3. Employee dapat mengganti lampiran secara langsung.

Notifikasi dan activity history tidak diperlukan. Employee melihat task baru melalui bagian Tugas Baru.

**ERD:** tidak perlu tabel atau kolom baru. Fitur ini memakai `tasks`, `task_assignees`, `subtasks`, dan `subtask_attachments`.

---



> Dokumen ini dibuat sebagai **single source of truth untuk melanjutkan ProjectHub** berdasarkan bundle source yang tersedia di percakapan. Dokumen memisahkan dengan jelas antara **yang benar-benar sudah ada di source**, **yang ada secara struktur tetapi belum punya alur aksi lengkap**, dan **yang belum ada sama sekali**.
>
> Snapshot source yang dipakai untuk dokumen ini berasal dari bundle `app`, `database`, `resources`, dan `public` yang tersedia di percakapan. File source yang terlihat bertanggal sampai sekitar **28 September 2026**, sedangkan dokumen ini dibuat pada **2 Oktober 2026**.

---

# 1. Tujuan Dokumen

Dokumen ini dipakai sebagai handoff untuk AI/developer lain agar tidak menebak-nebak kondisi project.

Aturan membaca dokumen:

- **SUDAH ADA** = terdapat route/controller/model/view/logic yang mendukung fitur tersebut pada source yang ditinjau.
- **STRUKTUR SUDAH ADA** = database/model/view sudah memiliki bagian terkait, tetapi belum ada alur aksi lengkap.
- **BELUM ADA** = tidak ditemukan implementasi route/controller/model/view untuk fitur tersebut pada source yang ditinjau.
- **PERLU VERIFIKASI** = implementasi ada, tetapi masih ada aturan bisnis/security/edge case yang belum dipastikan.

Dokumen ini **tidak menganggap fitur masa depan sebagai fitur yang sudah selesai**.

---

# 2. Gambaran Project Saat Ini

ProjectHub adalah aplikasi manajemen project berbasis role.

Role yang tersedia:

```text
ADMIN
  ↓
Kelola user dan akses dasar

PROJECT MANAGER
  ↓
Kelola project, workflow, member, task

EMPLOYEE
  ↓
Melihat task yang ditugaskan kepadanya
```

Flow yang saat ini benar-benar terlihat pada source:

```text
LOGIN
  ↓
ROLE REDIRECT
  ├── ADMIN → Admin Dashboard
  ├── PM → Project Manager Dashboard
  └── EMPLOYEE → Tugas Saya
```

Flow project yang sudah tersedia:

```text
PM
 ↓
Create Project
 ↓
Pilih Status Project
 ↓
Buat Workflow Default / Custom
 ↓
Kelola Member
 ↓
Atur Workflow
 ↓
Create Task
 ↓
Assign 1 atau beberapa Member
 ↓
Employee melihat Task
 ↓
Employee melihat Subtask dan Attachment yang sudah ada
```

**Penting:** source saat ini memiliki alur Employee mengerjakan task → submit review → PM approve/revision → tindak lanjut Revision Item. Upload attachment dan penyelesaian stage/project masih belum tercakup dalam alur tersebut.

---

# 3. Struktur Teknologi yang Terlihat dari Source

Teknologi yang terkonfirmasi dari struktur source:

- Laravel / PHP application structure.
- Blade views.
- Livewire digunakan untuk form/login/navigation tertentu.
- Alpine.js digunakan pada pengelolaan workflow/project stage di Blade.
- Eloquent ORM.
- Database migration + SQLite file tersedia pada bundle database.
- Tailwind-style utility classes digunakan pada view.

Tidak semua file root project tersedia dalam bundle yang ditinjau, sehingga versi framework/package **tidak dicantumkan di dokumen ini** agar tidak mengarang.

---

# 4. Struktur Source Penting

## 4.1 Controller

```text
app/Http/Controllers/
├── Admin/
│   ├── DashboardController.php
│   └── UserController.php
│
├── Employee/
│   └── TaskController.php
│
└── ProjectManager/
    ├── DashboardController.php
    ├── ProjectController.php
    ├── ProjectMemberController.php
    ├── ProjectProgressController.php
    ├── ProjectStageController.php
    └── ProjectTaskController.php
```

## 4.2 Model

```text
app/Models/
├── User.php
├── Project.php
├── ProjectStage.php
├── Task.php
├── Subtask.php
└── SubtaskAttachment.php
```

## 4.3 Middleware

```text
RoleMiddleware.php
EnsurePasswordIsChanged.php
```

## 4.4 Livewire

```text
app/Livewire/
├── Actions/Logout.php
├── Forms/LoginForm.php
└── Admin/Users/UserTable.php
```

## 4.5 View Area

```text
resources/views/
├── admin/
├── employee/
├── project-manager/
├── layouts/
└── livewire/
```

---

# 5. Authentication & Access Control

## 5.1 Login

Login menggunakan `username` dan password.

Flow:

```text
User membuka Login
  ↓
Masukkan Username + Password
  ↓
Rate limit check
  ↓
Auth attempt
  ↓
Cek status user
  ├── inactive → logout + ditolak
  └── active → lanjut
  ↓
Cek must_change_password
  ├── true → Change Password
  └── false → Dashboard sesuai role
```

### Status

- Login: **SUDAH ADA**
- Logout: **SUDAH ADA**
- Rate limiting: **SUDAH ADA**
- User inactive ditolak saat login: **SUDAH ADA**
- Redirect berdasarkan role: **SUDAH ADA**
- Wajib ganti password sementara: **SUDAH ADA**

## 5.2 Role Middleware

Middleware `role:*` memastikan route hanya bisa diakses role yang sesuai.

Contoh:

```text
/admin/*
  → role:admin

/project-manager/*
  → role:project_manager

/employee/*
  → role:employee
```

### Status

**SUDAH ADA**.

### Catatan

Security tetap perlu diuji dengan mencoba membuka URL role lain secara langsung.

---

# 6. Admin

## 6.1 Dashboard Admin

Dashboard menampilkan:

- Total User.
- Total Project Manager.
- Total Employee.
- Total User aktif.
- User terbaru.

### Status

**SUDAH ADA**.

## 6.2 User Management

Admin dapat:

```text
Daftar User
  ├── Detail
  ├── Tambah User
  ├── Edit User
  ├── Aktif / Nonaktif
  └── Reset Password
```

Data user yang dikelola mencakup:

```text
name
username
email
role
department
skills
phone
joined_at
status
must_change_password
```

## 6.3 Buat User

Admin dapat membuat role:

- `project_manager`
- `employee`

Saat user dibuat:

```text
Generate temporary password
        ↓
User status = active
        ↓
must_change_password = true
        ↓
Credentials ditampilkan sekali melalui flash session
```

### Catatan

Tidak ada form Admin untuk membuat Admin baru pada `UserController::store()`.

## 6.4 Proteksi Admin

Source saat ini melindungi akun Admin dari:

- perubahan role menjadi role lain melalui edit user;
- penonaktifan akun Admin;
- reset password Admin melalui User Management.

---

# 7. Project Manager Dashboard

Dashboard PM menampilkan jumlah total project, project aktif, task yang belum selesai, task selesai, dan daftar project terbaru.

### Status

**SUDAH TERINTEGRASI** dengan data project dan task.

# 8. Project

## 8.1 Project Data

Model `Project` memiliki:

```text
id
created_by
name
description
start_date
deadline
status
current_stage_id
created_at
updated_at
```

Relasi:

```text
Project
├── creator
├── stages
├── currentStage
├── members
└── tasks
```

## 8.2 Status Project

Database menggunakan:

```text
draft
active
completed
cancelled
```

## 8.3 Create Project

PM dapat membuat project dengan:

- nama;
- deskripsi;
- tanggal mulai;
- deadline;
- status;
- workflow default atau custom.

Workflow default yang diberikan source:

```text
Perencanaan
↓
Development
↓
Testing
↓
Deployment
```

Workflow custom dapat diisi manual.

### Status

**SUDAH ADA**.

## 8.4 Update Project

PM dapat mengubah:

- nama;
- deskripsi;
- start date;
- deadline;
- status.

### Catatan Penting

Controller menerima status:

```text
draft / active / completed / cancelled
```

Artinya saat ini status project masih dapat dipilih langsung melalui form edit.

Belum ada state machine ketat seperti:

```text
DRAFT → ACTIVE → COMPLETED
```

atau validasi bahwa project baru boleh `completed` setelah semua task selesai.

### Status

CRUD: **SUDAH ADA**.

Business rule lifecycle: **BELUM LENGKAP**.

## 8.5 Delete Project

Project dapat dihapus oleh PM yang membuatnya.

Delete menggunakan cascade pada sebagian relasi database terkait project.

### Status

**SUDAH ADA**.

---

# 9. Project Member

PM dapat memilih employee aktif sebagai member project.

Flow:

```text
Project
 ↓
Kelola Anggota
 ↓
Pilih Employee aktif
 ↓
Sync project_members
```

Validasi member memastikan:

- user benar-benar ada;
- role = employee;
- status = active.

Relasi:

```text
Project ⇄ User
       project_members
```

### Status

**SUDAH ADA**.

### Batasan

Belum ada histori perubahan member.

---

# 10. Workflow / Project Stage

## 10.1 Database

`project_stages` memiliki:

```text
id
project_id
name
position
completed_at
```

Setiap posisi unik per project.

## 10.2 Current Stage

Project memiliki:

```text
current_stage_id
```

yang menunjuk stage aktif.

## 10.3 Start Stage

PM hanya dapat memulai project stage jika:

```text
Project status = active
AND
current_stage_id = null
AND
Project memiliki stage
```

Stage pertama dijadikan current stage.

### Status

**SUDAH ADA**.

## 10.4 Next Stage

PM dapat menekan `Lanjut ke ...` jika current stage memiliki stage berikutnya.

Ketika berpindah:

```text
current stage.completed_at = now()
        ↓
project.current_stage_id = next stage
```

### Status

**SUDAH ADA**.

## 10.5 Batasan Penting

`ProjectProgressController::next()` memastikan stage memiliki task dan seluruh task pada stage sudah selesai sebelum melanjutkan.

Server memblokir perpindahan stage jika:

```text
Current Stage
    ↓
Masih ada task belum selesai
    ↓
            PM tidak dapat menekan Next Stage
```

Untuk melanjutkan ke stage berikutnya, stage aktif harus memiliki minimal satu task dan semua task-nya berstatus completed. Dari stage terakhir, PM dapat menutup project dengan semua task selesai atau mencatat pengecualian untuk task yang tidak lagi diperlukan.

### Status

Integrasi stage dan penyelesaian project: **SUDAH ADA**.

---

# 11. Workflow Editing

PM dapat membuka halaman `Atur Workflow`.

Pada Draft:

- tambah stage;
- rename stage;
- hapus stage yang belum memiliki task;
- ubah urutan stage.

Pada Active, UI menginformasikan:

> Stage lama tidak dapat dihapus, tetapi dapat diganti nama atau ditambahkan stage baru.

### Aturan Server

Project completed tidak dapat mengubah workflow. Setelah workflow dimulai, server menolak penghapusan dan perubahan urutan stage yang sudah ada. Stage yang sudah memiliki task juga tidak dapat dihapus.

### Status

**ATURAN SERVER SUDAH DISELARASKAN DENGAN UI**.

---

# 12. Task

## 12.1 Database Task

Table `tasks` memiliki:

```text
id
project_id
project_stage_id
created_by
title
description
deadline
status
created_at
updated_at
```

Status database:

```text
pending
in_progress
waiting_review
revision
completed
```

## 12.2 Task Assignees

Task menggunakan pivot:

```text
task_assignees
```

Artinya **satu task dapat memiliki banyak assignee**.

Relasi:

```text
Task ⇄ User
     task_assignees
```

Ini bukan lagi sistem single assignee.

## 12.3 Create Task

PM dapat membuat task jika project bukan:

```text
completed
cancelled
```

Form task berisi:

- judul;
- deskripsi;
- stage project;
- satu atau beberapa anggota task;
- deadline.

Validasi penting:

### Stage

Stage harus berasal dari project yang sama.

### Assignee

User yang dipilih harus terdaftar sebagai member project yang sama.

### Deadline

Deadline task:

```text
>= project.start_date
<= project.deadline
```

jika tanggal project tersedia.

Task baru selalu dibuat dengan:

```text
status = pending
```

### Status

**SUDAH ADA**.

---

# 13. Task List PM

PM dapat melihat task berdasarkan stage project.

Flow:

```text
Project
 ↓
Task Project
 ↓
Pilih Stage
 ↓
Task pada stage tersebut
```

Task menampilkan antara lain:

- status;
- progress;
- deadline;
- jumlah anggota;
- jumlah subtask selesai.

### Status

**SUDAH ADA**.

---

# 14. Task Detail PM

PM dapat membuka detail task untuk melihat judul, status, project, stage, deadline, deskripsi, anggota, progress, subtask, dan lampiran.

Saat ini detail task bersifat read-only. **Edit task oleh PM menjadi salah satu dari tiga fitur yang masih perlu diselesaikan.** Data tetap memakai tabel `tasks` dan pivot `task_assignees`; tidak perlu menambah tabel.

# 15. Delete Task

PM dapat menghapus task **hanya ketika status task masih `pending`**.

Jika status sudah berubah:

```text
in_progress
waiting_review
revision
completed
```

delete ditolak.

### Status

**SUDAH ADA**.

---

# 16. Task Status

Database sudah menyiapkan lima status:

```text
PENDING
   ↓
IN PROGRESS
   ↓
WAITING REVIEW
   ↓
COMPLETED
```

dan alternatif:

```text
WAITING REVIEW
   ↓
REVISION
   ↓
IN PROGRESS
```

Namun ini baru **status yang tersedia pada schema dan label view**.

## Yang belum ada

Tidak ditemukan route/controller untuk:

```text
Employee mulai task
Employee mengubah task menjadi in_progress
Employee submit for review
PM approve task
PM request revision
Employee submit ulang setelah revision
```

Jadi lifecycle status task **BELUM BERFUNGSI END-TO-END**.

---

# 17. Employee

## 17.1 Daftar Task

Employee hanya melihat task yang memiliki dirinya pada pivot `task_assignees`.

Query menggunakan:

```text
whereHas('assignees', users.id = Auth::id())
```

Jadi satu employee dapat melihat task yang ditugaskan kepadanya meskipun ada assignee lain pada task yang sama.

### Status

**SUDAH ADA**.

## 17.2 Employee Task Detail

Employee dapat melihat:

- nama task;
- status;
- project;
- stage;
- deadline;
- deskripsi;
- anggota task;
- progress;
- subtask;
- informasi pembuat/completer subtask;
- attachment yang sudah tersedia.

### Status

**SUDAH ADA sebagai READ-ONLY DETAIL**.

---

# 18. Subtask

## 18.1 Database

Table `subtasks` memiliki:

```text
id
task_id
created_by
completed_by
title
description
status
completion_note
completed_at
created_at
updated_at
```

Status subtask:

```text
pending
completed
```

Relasi:

```text
Task
 ↓
Subtasks
```

Subtask juga memiliki attachment.

## 18.2 Progress Task dari Subtask

Model `Task` memiliki accessor `progress`.

Rumus:

```text
jumlah subtask selesai
---------------------- × 100
jumlah seluruh subtask
```

Jika tidak ada subtask:

```text
progress = 0
```

View PM dan Employee juga menghitung progress dengan rumus yang sama.

### Status

**SUDAH ADA**.

### 18.3 Fitur Subtask yang Masih Perlu Diselesaikan

Employee sudah dapat membuat subtask, mengubah status, dan mengisi catatan penyelesaian. **Edit judul dan deskripsi subtask oleh Employee masih perlu dibuat.** Kolom `title` dan `description` sudah tersedia pada tabel `subtasks`, jadi tidak perlu perubahan ERD.

# 19. Subtask Attachment

## 19.1 Database

Table `subtask_attachments`:

```text
id
subtask_id
uploaded_by
original_name
file_path
mime_type
file_size
created_at
updated_at
```

Relasi:

```text
Subtask
  ↓
Attachments
```

## 19.2 Current UI

Attachment dapat diunggah dari detail task Employee, diunduh oleh Employee yang ditugaskan maupun PM pemilik project, dan dihapus oleh uploader selama task masih dapat diedit. File disimpan pada local disk privat; validasi membatasi ukuran hingga 10 MB dan tipe file yang diizinkan.

### Status

**UPLOAD, DISPLAY, DOWNLOAD TEROTORISASI, DAN DELETE TERBATAS SUDAH ADA**.

### 19.3 Fitur Lampiran yang Masih Perlu Diselesaikan

Employee sudah dapat mengunggah, mengunduh, dan menghapus lampiran dengan pemeriksaan akses. **Penggantian lampiran secara langsung masih perlu dibuat.** Tabel `subtask_attachments` sudah menyimpan file path dan metadata yang diperlukan, jadi tidak perlu tabel baru.

# 20. Revision Item

## 20.1 Kondisi Sebenarnya pada Source Sekarang

Revision Item sekarang tersedia sebagai entity/record khusus.

Source sekarang memiliki:

```text
RevisionItem model
revision_items migration/table
Relasi Task -> Revision Items
Employee follow-up routes and view
PM review actions and revision history
```

## 20.2 Data yang Disimpan

Setiap item revisi menyimpan task, nomor putaran, catatan, pembuat PM, status, penyelesai, dan waktu selesai. Pelaksana mengikuti assignee task, sehingga satu item dapat ditindaklanjuti anggota task yang ditugaskan:

```text
Task
 ↓
Revision Items
 ├── catatan revisi
 ├── dibuat oleh PM
 ├── status item
 ├── pelaksana melalui assignee task
 ├── created_at
 ├── resolved_at
 └── cycle/history
```

Satu task dapat memiliki beberapa item:

```text
Task #10
├── Revision Item #1: Perbaiki validasi
├── Revision Item #2: Perbaiki responsive mobile
└── Revision Item #3: Ubah teks error
```

## 20.3 Flow yang Tersedia

```text
Employee
  ↓
Submit for Review
  ↓
WAITING REVIEW
  ↓
PM Review
  ↓
Request Revision
  ↓
Create Revision Item(s)
  ↓
REVISION
  ↓
Employee memperbaiki item
  ↓
Item resolved
  ↓
Submit ulang
  ↓
PM Review lagi
  ├── Approve → COMPLETED
  └── Revision → cycle berikutnya
```

### Status

**SUDAH ADA**, termasuk beberapa item dalam satu putaran dan history lintas putaran.

---

# 21. Submit for Review

## Kondisi sekarang

Employee memiliki aksi untuk memulai task dan mengajukannya:

```text
POST submit review
```

Alur status yang tersedia:

```text
pending → in_progress → waiting_review
```

### Status

**SUDAH ADA**.

## Aturan Submit yang Diterapkan

Employee harus memiliki tombol/aksi:

```text
Submit for Review
```

Aturan yang diterapkan:

- task harus benar-benar milik employee;
- task sedang berstatus `in_progress`;
- task memiliki minimal satu subtask dan semua subtask berstatus `completed`;
- status menjadi `waiting_review`, dengan nomor putaran review bertambah.

---

# 22. PM Review

## Kondisi sekarang

PM dapat membuka satu halaman Task berisi task lintas project yang dikelompokkan berdasarkan project, mencari berdasarkan nama project, memfilter task yang perlu direview, lalu melakukan:

```text
Review Queue
Approve
Request Revision
Revision Notes
```

### Status

**SUDAH ADA** untuk daftar lintas project dan filter review dalam satu halaman, approve, dan request revision.

## Alur Review yang Tersedia

```text
Waiting Review
      ↓
PM membuka task
      ↓
Lihat:
├── task detail
├── progress
├── subtask
├── attachment
└── revision item
      ↓
┌─────────────┴─────────────┐
↓                           ↓
APPROVE                  REVISION
↓                           ↓
COMPLETED               Revision Item
```

---

# 23. Project Completion

## Kondisi Sekarang

PM hanya dapat melanjutkan ke stage berikutnya setelah semua task pada stage aktif selesai. Dari stage terakhir, PM dapat menutup project dengan pengecualian task yang belum selesai setelah konfirmasi dan alasan.

Project dapat ditutup dari stage terakhir. Jika masih ada task belum selesai, PM melihat daftar yang dikelompokkan per stage, wajib mengonfirmasi task tersebut memang tidak diperlukan, dan mengisi alasan. Snapshot task, status, stage, alasan, dan PM pencatat disimpan sebagai completion exception. Task yang dikecualikan tidak dapat dilanjutkan setelah project ditutup.

Jika semua task sudah selesai, PM dapat menutup project tanpa pengecualian. PM juga dapat menambahkan stage `Maintenance` secara opsional di akhir workflow sebelum penutupan.

`ProjectProgressController::next()` mengatur perpindahan stage, sedangkan `complete()` menutup project dari stage terakhir:

```text
Jika stage terakhir selesai:
→ semua task selesai?
→ project = completed
```

Flow sekarang:

```text
Project berada di stage terakhir
  ↓
Semua task selesai atau dikecualikan PM dengan alasan
  ↓
Project berstatus completed
```

### Status

**PROJECT COMPLETION FLOW SUDAH ADA**.

## Aturan yang Diterapkan

```text
Semua task di stage aktif selesai untuk lanjut
        ↓
Stage completed
        ↓
Jika ada next stage → lanjut
        ↓
Jika stage terakhir:
        ↓
Task selesai atau dikecualikan PM dengan alasan
        ↓
Project completed
```

Penyelesaian project dilakukan melalui aksi PM pada stage terakhir; status `completed` tidak dapat dipilih langsung pada create/edit project. Tahap Maintenance dapat ditambahkan sebelum penutupan.

---

# 24. Project Overall Progress

## Kondisi Sekarang

Halaman detail project menampilkan overall progress sebagai rata-rata progress setiap task. Progress task dihitung dari subtask selesai; task completed dihitung 100%, task tanpa subtask yang belum completed dihitung 0%, dan project tanpa task memiliki progress 0%.

### Status

**SUDAH ADA PADA DETAIL PROJECT**.

## Rumus

```text
Task A 100%
Task B 50%
Task C 0%
```

kemudian:

```text
Project progress = rata-rata progress task (bobot sama untuk setiap task)
```

---

# 25. Notification

Notifikasi bukan kebutuhan dalam scope saat ini. Employee melihat tugas yang belum dimulai melalui bagian **Tugas Baru** pada daftar task.

# 26. Activity History / Audit Log

Activity history atau audit log tidak diperlukan dalam scope saat ini.

# 27. Route Map Saat Ini

## Admin

```text
GET    /admin/dashboard
GET    /admin/users
GET    /admin/users/create
POST   /admin/users
GET    /admin/users/{user}
GET    /admin/users/{user}/edit
PATCH  /admin/users/{user}
PATCH  /admin/users/{user}/status
PATCH  /admin/users/{user}/reset-password
```

## Project Manager

```text
GET    /project-manager/dashboard

Resource Project:
GET     /project-manager/projects
GET     /project-manager/projects/create
POST    /project-manager/projects
GET     /project-manager/projects/{project}
GET     /project-manager/projects/{project}/edit
PUT/PATCH /project-manager/projects/{project}
DELETE  /project-manager/projects/{project}

Workflow:
GET    /project-manager/projects/{project}/workflow
PUT    /project-manager/projects/{project}/workflow

Members:
GET    /project-manager/projects/{project}/members
PUT    /project-manager/projects/{project}/members

Progress:
POST   /project-manager/projects/{project}/progress/start
POST   /project-manager/projects/{project}/progress/next

Tasks:
GET    /project-manager/projects/{project}/tasks
GET    /project-manager/projects/{project}/tasks/create
POST   /project-manager/projects/{project}/tasks
GET    /project-manager/projects/{project}/tasks/{task}
DELETE /project-manager/projects/{project}/tasks/{task}
```

## Employee

```text
GET    /employee/tasks
GET    /employee/tasks/{task}
```

## Profile

```text
GET    /profile
```

## Yang BELUM ADA di route

```text
Task update/status
Subtask CRUD
Subtask complete
Attachment replace
Submit review
PM review queue
Approve task
Request revision
Revision item CRUD
Notification
Activity history
```

---

# 28. Relationship Database Saat Ini

```text
User
 │
 ├── hasMany created Project
 ├── belongsToMany Project via project_members
 ├── belongsToMany Task via task_assignees
 ├── creates Subtask
 └── uploads SubtaskAttachment

Project
 │
 ├── belongsTo User (creator)
 ├── hasMany ProjectStage
 ├── belongsTo current ProjectStage
 ├── belongsToMany User (members)
 └── hasMany Task

ProjectStage
 │
 ├── belongsTo Project
 └── hasMany Task (melalui project_stage_id)

Task
 │
 ├── belongsTo Project
 ├── belongsTo ProjectStage
 ├── belongsTo User (creator)
 ├── belongsToMany User (assignees)
 └── hasMany Subtask

Subtask
 │
 ├── belongsTo Task
 ├── belongsTo User (creator)
 ├── belongsTo User (completer)
 └── hasMany SubtaskAttachment

SubtaskAttachment
 │
 ├── belongsTo Subtask
 └── belongsTo User (uploader)
```

---

# 29. Fitur yang Benar-benar Sudah Tercapai

Checklist berdasarkan source yang ditinjau:

## Authentication

- [x] Login.
- [x] Logout.
- [x] Rate limit login.
- [x] Cek user active/inactive saat login.
- [x] Forced password change.
- [x] Role middleware.

## Admin

- [x] Dashboard.
- [x] User list.
- [x] Create user.
- [x] Edit user.
- [x] Detail user.
- [x] Toggle active/inactive.
- [x] Reset password.
- [x] Proteksi perubahan role Admin.
- [x] Proteksi nonaktif/reset Admin.

## Project Manager

- [x] Dashboard dasar.
- [x] Project list.
- [x] Create project.
- [x] Edit project.
- [x] Detail project.
- [x] Delete project.
- [x] Default workflow.
- [x] Custom workflow saat create.
- [x] Edit workflow.
- [x] Project members.
- [x] Start stage.
- [x] Next stage.
- [x] Create task.
- [x] Multi-assignee task.
- [x] Task list per stage.
- [x] Task detail.
- [x] Delete pending task.

## Employee

- [x] Employee task list.
- [x] Employee task detail.
- [x] Akses task dibatasi berdasarkan task assignee.
- [x] Menampilkan task progress.
- [x] Menampilkan subtask.
- [x] Menampilkan attachment yang sudah ada.
- [x] Subtask actions (create, edit, complete, revert, completion_note).

## Data Layer

- [x] Project relation.
- [x] Project stage relation.
- [x] Project members relation.
- [x] Task relation.
- [x] Task assignees pivot.
- [x] Subtask relation.
- [x] Subtask attachment relation.
- [x] Task progress accessor.

---

# 30. Fitur yang Masih Perlu Diselesaikan

Backlog aktif hanya terdiri dari:

1. **Edit task oleh PM** — menggunakan `tasks` dan pivot `task_assignees`.
2. **Edit judul/deskripsi subtask oleh Employee** — menggunakan kolom yang sudah ada di `subtasks`.
3. **Penggantian lampiran langsung oleh Employee** — menggunakan `subtask_attachments` yang sudah ada.

Tidak ada tabel atau kolom database baru yang diperlukan untuk tiga fitur tersebut.

# 31. Scope Lain

Dashboard PM sudah menampilkan statistik task. Notifikasi dan activity history tidak diperlukan. Keduanya bukan backlog aktif.

# 32. Security / Business Rule yang Harus Diperiksa

## 32.1 Project Ownership

Saat ini PM hanya boleh mengakses project jika:

```text
project.created_by == Auth::id()
```

Ini sudah diterapkan pada controller PM project/task/member/stage/progress.

## 32.2 Employee Task Ownership

Employee hanya boleh membuka task yang punya dirinya pada `task_assignees`.

Ini sudah diterapkan pada employee controller.

## 32.3 Task Assignee harus Member Project

Saat create task sudah divalidasi melalui `project_members`.

## 32.4 Cross Project Task Access

PM task detail sudah mengecek task tersebut memang milik project yang sama.

## 32.5 Workflow Server Rule

Perlu diperbaiki agar UI rule dan server rule sama.

## 32.6 Project Completion

Perlu diperketat agar `completed` tidak bisa dipilih sembarangan jika aturan bisnis yang dipakai adalah completion berdasarkan task.

---

# 33. Flow Saat Ini vs Flow Target

## 33.1 Flow Saat Ini

```text
PM Login
  ↓
Create Project
  ↓
Draft / Active / Completed / Cancelled
  ↓
Create Workflow
  ↓
Add Members
  ↓
Create Task
  ↓
Assign Members
  ↓
Employee sees Task
  ↓
Employee sees existing Subtasks
  ↓
Employee sees existing Attachments
```

Flow berhenti karena belum ada mutation endpoint untuk Employee.

## 33.2 Flow Target

```text
PM Login
  ↓
Create Project
  ↓
Set Workflow
  ↓
Add Members
  ↓
Create Task
  ↓
Assign Employee(s)
  ↓
Employee Login
  ↓
Start Task
  ↓
Create / Complete Subtask
  ↓
Upload Attachment
  ↓
Task Progress 100%
  ↓
Submit for Review
  ↓
WAITING REVIEW
  ↓
PM Review
  ├── APPROVE
  │     ↓
  │  COMPLETED
  │
  └── REVISION
        ↓
      Revision Item
        ↓
      Employee Fix
        ↓
      Submit Again
        ↓
      PM Review Lagi
```

---

# 34. Implementasi Revision Item

Revision Item memiliki relasi:

```text
Task
  └── hasMany RevisionItem
```

Kolom pada implementasi:

```text
id
task_id
cycle
created_by
note
status              → open / in_progress / resolved
resolved_by
resolved_at
created_at
updated_at
```

Task menyimpan `review_cycle`; setiap submit baru menaikkan nomor putaran dan item revisi lama tetap tersimpan.

---

# 35. Urutan Implementasi Berikutnya

Selesaikan tiga backlog aktif dengan urutan berikut:

1. Edit task oleh PM.
2. Edit judul dan deskripsi subtask oleh Employee.
3. Ganti lampiran secara langsung.

Ketiganya memakai tabel yang sudah ada; tidak perlu tabel atau kolom baru untuk ERD.

# 36. Definition of Done ProjectHub

ProjectHub memenuhi scope saat ini setelah tiga fitur pada bagian 30 selesai:

- PM dapat mengedit task.
- Employee dapat mengedit judul dan deskripsi subtask.
- Employee dapat mengganti lampiran secara langsung.

Fitur notifikasi dan activity history tidak termasuk definisi selesai.

# 37. Instruksi untuk AI/Developer Berikutnya

Pertahankan otorisasi project PM, pembatasan akses task berdasarkan assignee, dan riwayat revision cycle yang sudah ada.

Fokus backlog hanya pada tiga fitur di bagian 30:

- Edit task oleh PM.
- Edit judul dan deskripsi subtask oleh Employee.
- Penggantian lampiran secara langsung.

Gunakan tabel dan kolom yang sudah ada. Notifikasi dan activity history tidak termasuk scope saat ini.

# 38. Ringkasan Akhir

Alur inti ProjectHub sudah tersedia. Tiga pekerjaan yang tersisa adalah edit task oleh PM, edit judul/deskripsi subtask oleh Employee, dan penggantian lampiran secara langsung.

Ketiganya menggunakan tabel yang sudah ada (`tasks`, `task_assignees`, `subtasks`, dan `subtask_attachments`). ERD tidak memerlukan tabel atau kolom baru untuk scope ini.
