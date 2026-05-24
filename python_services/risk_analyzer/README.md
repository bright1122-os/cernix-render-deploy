# CERNIX Risk Analyzer

The CERNIX Risk Analyzer is a lightweight Python intelligence module for offline audit and verification risk analysis. Laravel remains the main CERNIX web system.

This module does not handle authentication, payment verification, QR generation, QR verification, cryptographic secrets, or token lifecycle logic. It analyzes exported operational logs only.

## Usage

From the Laravel project root:

```bash
python python_services/risk_analyzer/analyze.py
```

With explicit input and output files:

```bash
python python_services/risk_analyzer/analyze.py scan_logs.json risk_report.json
```

If no arguments are provided, the script reads:

- `sample_input.json`

and writes:

- `sample_output.json`

## Input

Input may be either a JSON array of scan log objects or an object with a `scan_logs` array.

Expected fields include:

- `student_id`
- `matric_no`
- `examiner_id`
- `examiner_name`
- `decision`
- `token_id`
- `device_fp`
- `ip_address`
- `timestamp`
- `payment_status`
- `rrr_number`
- `qr_status`
- `department`
- `level`

Missing fields are handled safely.

## Output

The analyzer writes a JSON report containing:

- `total_scans`
- `approved_count`
- `rejected_count`
- `duplicate_count`
- `high_risk_students`
- `suspicious_examiners`
- `suspicious_devices`
- `suspicious_ips`
- `risk_summary`
- `recommendations`

## Risk Rules

Student risk:

- `+30` if the same token has duplicate scans.
- `+25` if a student has more than two rejected scans.
- `+20` if the same student appears from multiple device fingerprints.
- `+15` if the same student appears from multiple IP addresses.
- `+20` if payment status is not verified.
- `+10` if QR status is not `UNUSED` or `USED`.

Examiner risk:

- Flags unusually high rejected scan volume.
- Flags too many scans within a short time window.
- Flags repeated duplicate scan attempts.

Device/IP risk:

- Flags one device fingerprint across many students.
- Flags one IP address with too many rejected or duplicate scans.

Risk levels:

- `0-30` = low
- `31-60` = medium
- `61+` = high

## Laravel Integration Plan

Option A:

1. Laravel exports scan logs to JSON.
2. Python analyzes the JSON.
3. Laravel imports or displays the generated `risk_report.json`.

Option B later:

1. This module becomes a FastAPI microservice.
2. Laravel sends safe exported logs to `/analyze`.
3. Python returns a risk report JSON response.

The first version is offline and deployment-friendly.
