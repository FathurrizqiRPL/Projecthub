# ProjectHub — Handoff Teknis & Rancangan yang Sesuai Source

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

**Penting:** source saat ini **belum memiliki alur lengkap Employee mengerjakan task → submit review → PM approve/revision**. Status task untuk alur tersebut sudah tersedia di database, tetapi endpoint/action-nya belum ada.

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

Dashboard PM saat ini menampilkan:

- Total proyek yang dibuat PM.
- Proyek aktif.
- Proyek terbaru.
- Placeholder untuk Tugas Berjalan.
- Placeholder untuk Tugas Selesai.

Pada source view, dua statistik task masih menampilkan:

```text
—
Modul tugas belum tersedia
```

Jadi statistik task dashboard PM **BELUM TERINTEGRASI** dengan data task.

---

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

`ProjectProgressController::next()` **tidak mengecek task pada stage**.

Artinya saat ini source mengizinkan:

```text
Current Stage
    ↓
Masih ada task belum selesai
    ↓
PM tetap dapat menekan Next Stage
```

Tidak ada validasi:

```text
Semua task stage selesai?
```

Selain itu, ketika tidak ada next stage, controller hanya mengembalikan error bahwa project berada di stage terakhir.

**Tidak ada logika otomatis yang mengubah project menjadi `completed`.**

### Status

Integrasi stage ↔ task: **BELUM TERCAPAI**.

---

# 11. Workflow Editing

PM dapat membuka halaman `Atur Workflow`.

Pada Draft:

- tambah stage;
- rename stage;
- hapus stage;
- ubah urutan stage.

Pada Active, UI menginformasikan:

> Stage lama tidak dapat dihapus, tetapi dapat diganti nama atau ditambahkan stage baru.

### Perbedaan UI vs Controller

Controller sebenarnya hanya memblokir penghapusan jika:

```text
project.status === completed
```

dan juga memblokir penghapusan current stage.

Controller **tidak memiliki pengecekan `status === active` untuk semua stage lain**.

Jadi aturan di UI dan aturan server belum sepenuhnya sinkron.

### Status

**PERLU VERIFIKASI / PERBAIKAN RULE SERVER**.

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

PM dapat membuka detail task.

Yang terlihat:

- judul;
- status;
- project;
- stage;
- deadline;
- deskripsi;
- anggota task;
- progress;
- subtask;
- attachment pada subtask yang tersedia.

### Status

**SUDAH ADA sebagai READ-ONLY DETAIL**.

Tidak ada endpoint `edit/update task` pada route PM saat ini.

---

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

## 18.3 Yang Belum Ada

Tidak ditemukan route/controller untuk:

- membuat subtask;
- mengedit subtask;
- menandai subtask completed;
- mengembalikan subtask ke pending;
- mengisi completion note;
- submit subtask sebagai hasil kerja.

Di UI sekarang subtask hanya tampil sebagai informasi.

### Status

**STRUKTUR SUDAH ADA, AKSI SUBTASK BELUM ADA**.

---

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

Attachment dapat ditampilkan pada detail task karena subtask di-load bersama relation `attachments`.

### Status

**STRUKTUR + DISPLAY SUDAH ADA**.

## 19.3 Yang Belum Ada

Tidak ditemukan endpoint untuk:

```text
upload attachment
replace attachment
remove attachment
download/stream attachment melalui route aplikasi
```

Validasi tipe file, ukuran file, dan policy akses juga belum terlihat dalam source controller yang ditinjau.

### Status

**UPLOAD/CONTROL BELUM ADA**.

---

# 20. Revision Item

## 20.1 Kondisi Sebenarnya pada Source Sekarang

**Revision Item belum ada sebagai entity/record khusus.**

Tidak ditemukan:

```text
RevisionItem model
revision_items migration/table
RevisionItem controller
route Revision Item
view khusus Revision Item
```

Yang ada baru:

```text
Task.status = revision
```

dan label `Revisi` pada view.

## 20.2 Konsep yang Perlu Dibangun

Kalau rancangan final memang menggunakan Revision Item, struktur target yang disarankan:

```text
Task
 ↓
Revision Items
 ├── catatan revisi
 ├── dibuat oleh PM
 ├── assigned employee
 ├── status item
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

## 20.3 Flow Target

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

**BELUM ADA**.

---

# 21. Submit for Review

## Kondisi sekarang

Tidak ditemukan endpoint untuk aksi:

```text
POST submit review
```

Tidak ada route yang mengubah:

```text
in_progress → waiting_review
```

### Status

**BELUM ADA**.

## Target

Employee harus memiliki tombol/aksi:

```text
Submit for Review
```

dengan aturan yang akan ditetapkan, misalnya:

- task harus benar-benar milik employee;
- task tidak completed;
- pekerjaan/subtask yang diwajibkan sudah selesai;
- attachment yang diwajibkan tersedia jika ada;
- status menjadi `waiting_review`.

Aturan final tersebut belum ada di source dan harus diputuskan sebelum implementasi.

---

# 22. PM Review

## Kondisi sekarang

Tidak ditemukan route/controller khusus review.

Tidak ada implementasi:

```text
Review Queue
Approve
Request Revision
Review Note
```

### Status

**BELUM ADA**.

## Target

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

Project memiliki status `completed`, tetapi belum ada alur otomatis/terkontrol untuk mencapainya.

PM dapat memilih status project melalui form edit.

`ProjectProgressController::next()` tidak melakukan:

```text
Jika stage terakhir selesai:
→ semua task selesai?
→ project = completed
```

Jadi saat ini:

```text
Stage terakhir
  ↓
Tidak ada Next Stage
  ↓
Controller berhenti
  ↓
Project tetap active
```

### Status

**PROJECT COMPLETION FLOW BELUM TERCAPAI**.

## Target

Rancangan yang perlu diputuskan:

```text
Semua task stage selesai
        ↓
Stage completed
        ↓
Jika ada next stage → lanjut
        ↓
Jika stage terakhir:
        ↓
Semua task project selesai
        ↓
Project completed
```

Apakah final `completed` otomatis atau manual tetap harus diputuskan.

---

# 24. Project Overall Progress

## Kondisi Sekarang

Tidak ditemukan accessor/service khusus untuk `Project.progress`.

Task sudah memiliki progress berdasarkan subtask.

Dashboard PM belum menampilkan overall project progress.

### Status

**BELUM ADA SEBAGAI FITUR PENUH**.

## Target yang Mungkin

```text
Task A 100%
Task B 50%
Task C 0%
```

kemudian:

```text
Project progress = rata-rata / bobot task
```

Namun rumus final belum ditetapkan dalam source.

---

# 25. Notification

Tidak ditemukan implementasi notification khusus ProjectHub pada route/controller/model yang ditinjau.

Event yang seharusnya nanti dapat memicu notification:

```text
PM assign task
Employee submit review
PM request revision
PM approve task
Task deadline mendekat
```

### Status

**BELUM ADA**.

---

# 26. Activity History / Audit Log

Tidak ditemukan table/model/controller khusus activity log pada source yang ditinjau.

Target:

```text
10:00 PM membuat task
11:00 Employee mulai
13:00 Employee menyelesaikan subtask
14:00 Employee submit review
15:00 PM request revision
16:00 Employee resolve revision
17:00 Submit ulang
18:00 PM approve
```

### Status

**BELUM ADA**.

---

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
Attachment upload
Attachment delete/download
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

# 30. Fitur yang Masih Belum Tercapai

## Prioritas Paling Tinggi

### 1. Employee Task Actions

Belum ada action untuk:

- mulai task;
- ubah status ke `in_progress`;
- mengelola subtask;
- menyelesaikan subtask;
- menambahkan completion note.

### 2. Attachment Actions

Belum ada:

- upload;
- delete;
- download/stream aman;
- validasi file.

### 3. Submit for Review

Belum ada:

```text
Employee → Waiting Review
```

### 4. PM Review

Belum ada:

```text
Waiting Review
 ↓
Approve / Revision
```

### 5. Revision Item

Belum ada entity khusus untuk mencatat item revisi.

### 6. Revision Cycle

Belum ada flow:

```text
Revision
 ↓
Fix
 ↓
Submit Again
 ↓
Review Again
```

---

# 31. Prioritas Menengah

- [ ] Integrasi otomatis stage dengan status task.
- [ ] Block Next Stage jika task stage belum selesai.
- [ ] Finalisasi stage terakhir.
- [ ] Project auto/manual completion rule.
- [ ] Overall project progress.
- [ ] Dashboard PM real statistics task.
- [ ] Notification.
- [ ] Activity history.

---

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

# 34. Rancangan Revision Item yang Konsisten dengan Source

Karena source saat ini belum memiliki Revision Item, jangan langsung mengasumsikan struktur lama.

Struktur target yang kompatibel dengan model Task saat ini dapat dibuat dengan hubungan:

```text
Task
  └── hasMany RevisionItem
```

Contoh kolom minimal:

```text
id
 task_id
created_by        → PM/user pembuat
assigned_to       → employee/user pelaksana
item              / title
notes             / description
status            → open / in_progress / resolved
resolved_at
created_at
updated_at
```

Jika ingin menyimpan siklus revisi secara eksplisit, bisa ditambah:

```text
revision_cycle
```

atau parent/review reference.

**Jangan membuat struktur ini sebelum aturan bisnis final disepakati.**

---

# 35. Rekomendasi Urutan Implementasi Berikutnya

Urutan yang paling aman berdasarkan dependency source sekarang:

```text
1. Subtask actions
   ↓
2. Attachment upload/actions
   ↓
3. Employee task status actions
   ↓
4. Submit for Review
   ↓
5. PM Review
   ↓
6. Revision Item
   ↓
7. Revision cycle
   ↓
8. Stage completion rule
   ↓
9. Project completion rule
   ↓
10. Dashboard metrics
   ↓
11. Notification
   ↓
12. Activity history
   ↓
13. End-to-end testing
```

Alasannya:

```text
Subtask
  ↓
Progress
  ↓
Submit Review
  ↓
PM Review
  ↓
Revision
  ↓
Task Completed
  ↓
Stage Completed
  ↓
Project Completed
```

Jadi jangan mengerjakan notification/history dulu sebelum lifecycle task benar-benar selesai.

---

# 36. Definition of Done ProjectHub

Project baru dapat dianggap menyelesaikan rancangan inti jika flow berikut benar-benar berjalan:

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
MEMBERS
 ✓
 ↓
TASK
 ✓
 ↓
ASSIGNMENT
 ✓
 ↓
SUBTASK ACTION
 ✓
 ↓
ATTACHMENT ACTION
 ✓
 ↓
EMPLOYEE WORK
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
REVISION LOOP
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

Semua langkah di atas juga harus lolos authorization check.

---

# 37. Instruksi untuk AI/Developer Berikutnya

Gunakan dokumen ini bersama source code.

## Jangan lakukan

- Jangan menganggap `task.status = revision` berarti Revision Item sudah ada.
- Jangan menganggap subtask sudah bisa diedit hanya karena model/table sudah ada.
- Jangan menganggap attachment sudah bisa upload hanya karena relation sudah ada.
- Jangan menganggap status `waiting_review` berarti submit-review sudah bekerja.
- Jangan menganggap project otomatis completed.
- Jangan mengubah flow project yang sudah ada tanpa mengecek controller dan migration.
- Jangan membuat ulang project dari nol.

## Lakukan

- Pertahankan CRUD yang sudah berjalan.
- Pertahankan ownership check PM terhadap project.
- Pertahankan assignee restriction Employee.
- Gunakan `task_assignees` karena task memang sudah multi-assignee.
- Gunakan progress subtask yang sudah menjadi accessor task.
- Tambahkan feature secara bertahap sesuai dependency.
- Setelah setiap feature, uji role dan authorization.

---

# 38. Ringkasan Akhir

ProjectHub **sudah memiliki fondasi yang cukup lengkap untuk project, workflow, member, task creation, multi-assignee, employee task viewing, subtask data model, dan attachment data model**.

Namun project **belum memiliki inti workflow pengerjaan sampai selesai**.

Bagian yang paling penting untuk dilanjutkan adalah:

```text
EMPLOYEE ACTIONS
      ↓
SUBTASK MANAGEMENT
      ↓
ATTACHMENT UPLOAD
      ↓
TASK STATUS TRANSITION
      ↓
SUBMIT FOR REVIEW
      ↓
PM REVIEW
      ↓
REVISION ITEM
      ↓
REVISION LOOP
      ↓
TASK COMPLETED
      ↓
STAGE COMPLETED
      ↓
PROJECT COMPLETED
```

Status `revision` pada database saat ini **bukan bukti bahwa fitur Revision Item sudah selesai**.

Demikian juga keberadaan tabel `subtasks` dan `subtask_attachments` **belum berarti Employee sudah bisa mengelolanya**.

Dokumen ini sengaja membedakan antara **schema**, **display**, dan **business action** agar AI berikutnya tidak salah menganggap data model sebagai fitur yang sudah selesai.
