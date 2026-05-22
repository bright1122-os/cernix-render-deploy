# CERNIX Local Demo Data

For demo/testing purposes, use the sample pattern below. In production, students enter their real faculty, department, matric number, and Remita RRR.

The registration screen intentionally shows only three collapsible examples. All 20 records below remain available for manual local testing.

## Demo Matric Pattern

Format: `YYDDFFNNN`

Example: `220404008`

- `22` = admission year/session code
- `04` = department code
- `04` = Faculty of Computing code
- `008` = student number

Faculty code:

- Faculty of Computing = `04`

Department codes and fixed school fees:

- Computer Science = `04` = `₦100,000`
- Software Engineering = `05` = `₦120,000`
- Information Technology = `06` = `₦110,000`
- Cyber Security = `07` = `₦140,000`
- Data Science = `08` = `₦150,000`

Demo RRR values run from `TEST-0001` to `TEST-0020`. Each RRR is tied to the department school fee shown below.

## Demo Students

| Name | Faculty | Department | Level | Matric Number | RRR | School Fee | Photo Path |
| --- | --- | --- | ---: | --- | --- | ---: | --- |
| Chidera Favour Nnamdi | Faculty of Computing | Computer Science | 300 | `220404001` | `TEST-0001` | `₦100,000` | `demo-passports/student-001.jpg` |
| Chukwuemeka Daniel Nwosu | Faculty of Computing | Computer Science | 400 | `220404008` | `TEST-0002` | `₦100,000` | `demo-passports/student-002.jpg` |
| Ifeoma Grace Okafor | Faculty of Computing | Software Engineering | 300 | `220504001` | `TEST-0003` | `₦120,000` | `demo-passports/student-003.jpg` |
| Adaeze Jennifer Obi | Faculty of Computing | Software Engineering | 400 | `220504008` | `TEST-0004` | `₦120,000` | `demo-passports/student-004.jpg` |
| Tunde Michael Bello | Faculty of Computing | Information Technology | 300 | `220604001` | `TEST-0005` | `₦110,000` | `demo-passports/student-005.jpg` |
| Chiamaka Ruth Eze | Faculty of Computing | Information Technology | 400 | `220604008` | `TEST-0006` | `₦110,000` | `demo-passports/student-006.jpg` |
| Toluwani Deborah Akinola | Faculty of Computing | Cyber Security | 300 | `220704001` | `TEST-0007` | `₦140,000` | `demo-passports/student-007.jpg` |
| Somtochukwu David Okafor | Faculty of Computing | Cyber Security | 400 | `220704008` | `TEST-0008` | `₦140,000` | `demo-passports/student-008.jpg` |
| Ayomide Samuel Adeyemi | Faculty of Computing | Data Science | 300 | `220804001` | `TEST-0009` | `₦150,000` | `demo-passports/student-009.jpg` |
| Amara Blessing Nwankwo | Faculty of Computing | Data Science | 400 | `220804008` | `TEST-0010` | `₦150,000` | `demo-passports/student-010.jpg` |
| Femi Joshua Akinola | Faculty of Computing | Computer Science | 100 | `230404011` | `TEST-0011` | `₦100,000` | `demo-passports/student-011.jpg` |
| Zainab Maryam Bello | Faculty of Computing | Computer Science | 200 | `230404012` | `TEST-0012` | `₦100,000` | `demo-passports/student-012.jpg` |
| Kemi Victoria Adeyemi | Faculty of Computing | Software Engineering | 100 | `230504011` | `TEST-0013` | `₦120,000` | `demo-passports/student-013.jpg` |
| Ibrahim Musa Adamu | Faculty of Computing | Software Engineering | 200 | `230504012` | `TEST-0014` | `₦120,000` | `demo-passports/student-014.jpg` |
| Chinedu Victor Eze | Faculty of Computing | Information Technology | 100 | `230604011` | `TEST-0015` | `₦110,000` | `demo-passports/student-015.jpg` |
| Ngozi Esther Chukwu | Faculty of Computing | Information Technology | 200 | `230604012` | `TEST-0016` | `₦110,000` | `demo-passports/student-016.jpg` |
| Temilade Sarah Ogunleye | Faculty of Computing | Cyber Security | 100 | `230704011` | `TEST-0017` | `₦140,000` | `demo-passports/student-017.jpg` |
| Uche David Nnamdi | Faculty of Computing | Cyber Security | 200 | `230704012` | `TEST-0018` | `₦140,000` | `demo-passports/student-018.jpg` |
| Emeka Kingsley Obi | Faculty of Computing | Data Science | 100 | `230804011` | `TEST-0019` | `₦150,000` | `demo-passports/student-019.jpg` |
| Adebayo Oluwaseun Emmanuel | Faculty of Computing | Data Science | 200 | `230804012` | `TEST-0020` | `₦150,000` | `demo-passports/student-020.jpg` |

Demo passport photos are supplied local mock portraits stored in `public/demo-passports/`. They are not real AAUA student photos, and the application renders local `public/demo-passports/*.jpg` files without hotlinking external portrait URLs at runtime.
