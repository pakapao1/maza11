# Legislative Viewer v6

A PHP + MySQL website for storing, reading and comparing versions of Acts.

## Setup (XAMPP)

1. Copy this `legislative-viewer` folder into `C:\xampp\htdocs\`.
2. In the XAMPP Control Panel, start **Apache** and **MySQL**.
3. Open <http://localhost/legislative-viewer/install.php>. It creates the `legislative_viewer` database, its tables and the first account.
4. Sign in at <http://localhost/legislative-viewer/> with `admin` / `admin123`, then change the password in **My profile**.

Database settings are in `config.php` (MariaDB on port 3307, user `root`, no password).

## Pages

| Page | Who | What it does |
|---|---|---|
| Home (`index.php`) | All | Dashboard: totals, recently updated Acts, recent activity |
| Act Library (`library.php`) | All (upload and delete: admin) | Search Acts, upload XML versions, delete Acts |
| Act details (`act.php`) | All (delete: admin) | Versions of one Act, downloads, history |
| Viewer (`viewer.php`) | All | Timeline, bilingual reading view, search, compare |
| User Guide (`guide.php`) | All | Step-by-step help and FAQ |
| About (`about.php`) | All | Problem statement, objectives, scope, technology |
| Users (`users.php`) | Admin | Add users, change roles, reset passwords, delete |
| My profile (`profile.php`) | All | Change name and password, personal activity counts |

## Database

See `sql/schema.sql`. Four tables:

- `users`: accounts with bcrypt password hashes and a role (`admin` or `viewer`)
- `acts`: one row per Act, identified by its `<NUMBER>`
- `act_versions`: one row per uploaded XML file; the file itself is saved in `storage/acts/<act id>/`
- `activity_log`: sign-ins, uploads, views, comparisons and user changes

XML files are kept on disk rather than in the database because XAMPP's default `max_allowed_packet` (1 MB) is smaller than many Acts.
