# CERNIX Roles And Permissions Roadmap

This document records the planned administrative role model. Only the Super Admin foundation is implemented in this phase; the remaining roles are documented so later work can add permissions without changing verification logic.

## Implemented Foundation

### Super Admin

- Highest administrative role.
- Intended for full system control across sessions, timetable, students, payments, examiners, admin notes, audit trail, and settings.
- Seeded as a local/demo admin account through the examiner/admin user table.
- Can create other Super Admin accounts from the examiner management page.

## Planned Roles

### Exam Officer

- Exam operations control.
- Manages timetable, scan logs, attendance, review queue, and reports.
- No system settings authority.

### Department Admin

- Department-scoped visibility over students, payments, timetable, and scan history.
- No global control outside assigned department.

### Auditor

- Read-only access to verification logs, reports, payments, scan history, and audit trail.
- Cannot edit records, resolve actions, or create operational changes.

### Examiner

- Scanner-only portal access.
- Can verify QR codes through server-controlled verification.
- Can view own scan history and today’s exams.
- No admin panel access.

## Implementation Notes

- Role values stored in the `examiners.role` column use lowercase values such as `examiner`, `admin`, and `super_admin`.
- Application helpers normalize roles internally before comparison.
- Future permission checks should protect only sensitive actions first; they should not lock the whole admin panel behind unfinished role logic.
