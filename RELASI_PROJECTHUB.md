# Daftar Relasi Database ProjectHub

Daftar ini disusun dari foreign key pada migration tabel aplikasi ProjectHub.
Tabel `users` disertakan karena menjadi bagian dari relasi ProjectHub; tabel
infrastruktur Laravel seperti `sessions`, `jobs`, dan `cache` tidak dibahas.

## Relasi

Kardinalitas ditulis dari **Tabel A ke Tabel B**. Contoh `1:M` berarti satu
baris di Tabel A dapat berhubungan dengan banyak baris di Tabel B.

| Tabel A | Nama relasi | Tabel B | Kardinalitas | Atribut penghubung |
|---|---|---|---|---|
| `users` | membuat proyek | `projects` | 1:M | `projects.created_by` → `users.id` |
| `projects` | memiliki tahap | `project_stages` | 1:M | `project_stages.project_id` → `projects.id` |
| `project_stages` | menjadi tahap aktif | `projects` | 1:M* | `projects.current_stage_id` → `project_stages.id` |
| `projects` | memiliki anggota proyek | `project_members` | 1:M | `project_members.project_id` → `projects.id` |
| `users` | menjadi anggota proyek | `project_members` | 1:M | `project_members.user_id` → `users.id` |
| `projects` | memiliki tugas | `tasks` | 1:M | `tasks.project_id` → `projects.id` |
| `project_stages` | mengelompokkan tugas | `tasks` | 1:M | `tasks.project_stage_id` → `project_stages.id` |
| `users` | membuat / membatalkan tugas | `tasks` | 1:M untuk masing-masing atribut | `tasks.created_by` → `users.id`; `tasks.cancelled_by` → `users.id` |
| `tasks` | memiliki penugasan | `task_assignees` | 1:M | `task_assignees.task_id` → `tasks.id` |
| `users` | menerima tugas | `task_assignees` | 1:M | `task_assignees.user_id` → `users.id` |
| `tasks` | memiliki subtugas | `subtasks` | 1:M | `subtasks.task_id` → `tasks.id` |
| `users` | membuat / menyelesaikan subtugas | `subtasks` | 1:M untuk masing-masing atribut | `subtasks.created_by` → `users.id`; `subtasks.completed_by` → `users.id` |
| `subtasks` | memiliki lampiran | `subtask_attachments` | 1:M | `subtask_attachments.subtask_id` → `subtasks.id` |
| `users` | mengunggah lampiran subtugas | `subtask_attachments` | 1:M | `subtask_attachments.uploaded_by` → `users.id` |
| `tasks` | memiliki revisi | `revision_items` | 1:M | `revision_items.task_id` → `tasks.id` |
| `users` | membuat / menyelesaikan revisi | `revision_items` | 1:M untuk masing-masing atribut | `revision_items.created_by` → `users.id`; `revision_items.resolved_by` → `users.id` |
| `projects` | memiliki pengecualian penyelesaian | `project_completion_exceptions` | 1:M | `project_completion_exceptions.project_id` → `projects.id` |
| `tasks` | dicatat dalam pengecualian penyelesaian | `project_completion_exceptions` | 1:M* | `project_completion_exceptions.task_id` → `tasks.id` |
| `users` | memberi pengecualian penyelesaian | `project_completion_exceptions` | 1:M | `project_completion_exceptions.waived_by` → `users.id` |
| `revision_items` | memiliki lampiran | `revision_attachments` | 1:M | `revision_attachments.revision_item_id` → `revision_items.id` |
| `users` | mengunggah lampiran revisi | `revision_attachments` | 1:M | `revision_attachments.uploaded_by` → `users.id` |

Terdapat **21 kelompok relasi** pada tabel di atas dan **24 atribut foreign
key**. Tiga kelompok relasi pengguna ke tugas, subtugas, dan revisi masing-masing
memuat dua foreign key dengan peran berbeda.

## Relasi many-to-many

Relasi many-to-many dibentuk melalui tabel penghubung:

| Tabel yang berelasi | Kardinalitas | Tabel penghubung | Atribut penghubung |
|---|---|---|---|
| `projects` ↔ `users` | M:N | `project_members` | `project_members.project_id`, `project_members.user_id` |
| `tasks` ↔ `users` | M:N | `task_assignees` | `task_assignees.task_id`, `task_assignees.user_id` |

Migration memberi batas unik pada pasangan `project_id` dan `user_id` di
`project_members`, serta pasangan `task_id` dan `user_id` di `task_assignees`.
Artinya, pasangan proyek–pengguna atau tugas–pengguna yang sama tidak dapat
dicatat dua kali pada tabel penghubung tersebut.

## Catatan kardinalitas

- Setiap atribut foreign key menghubungkan baris pada tabel pemilik atribut ke
  paling banyak satu baris pada tabel yang dirujuk. Jika foreign key tidak unik,
  beberapa baris dapat merujuk ke baris yang sama, sehingga sisi sebaliknya
  umumnya `1:M`.
- `projects.current_stage_id` boleh kosong. Setiap proyek memiliki nol atau satu
  tahap aktif; migration tidak membatasi agar satu tahap hanya menjadi tahap
  aktif untuk satu proyek. Karena itu tabel menuliskan kardinalitas maksimum
  `1:M` dari `project_stages` ke `projects`.
- `project_completion_exceptions.task_id` boleh kosong. Migration membuat
  pasangan `project_id` dan `task_id` unik, tetapi tidak menetapkan foreign key
  gabungan yang memastikan tugas tersebut berasal dari proyek yang sama.
- Atribut berikut opsional karena kolomnya nullable: `tasks.cancelled_by`,
  `subtasks.completed_by`, `revision_items.resolved_by`,
  `projects.current_stage_id`, dan
  `project_completion_exceptions.task_id`.
- Nama relasi adalah label konseptual agar mudah digunakan pada ERD; nama
  kolom foreign key dan tabel tujuan mengikuti migration.
