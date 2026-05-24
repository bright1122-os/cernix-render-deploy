#!/usr/bin/env python3
"""CERNIX Risk Analyzer.

Reads exported scan/audit style JSON and writes a rule-based risk report.
This module is intentionally offline-first and does not connect to the
production database.
"""

from __future__ import annotations

import json
import sys
from collections import Counter, defaultdict
from datetime import datetime, timezone
from pathlib import Path
from typing import Any


LOW_MAX = 30
MEDIUM_MAX = 60


def parse_timestamp(value: Any) -> datetime | None:
    if not value:
        return None

    text = str(value).strip()
    if text.endswith("Z"):
        text = text[:-1] + "+00:00"

    for candidate in (text, text.replace(" ", "T")):
        try:
            parsed = datetime.fromisoformat(candidate)
            if parsed.tzinfo is None:
                parsed = parsed.replace(tzinfo=timezone.utc)
            return parsed
        except ValueError:
            continue

    return None


def normalize_decision(value: Any) -> str:
    return str(value or "UNKNOWN").strip().upper()


def normalize_status(value: Any) -> str:
    return str(value or "unknown").strip().lower()


def risk_level(score: int) -> str:
    if score <= LOW_MAX:
        return "low"
    if score <= MEDIUM_MAX:
        return "medium"
    return "high"


def load_logs(path: Path) -> list[dict[str, Any]]:
    if not path.exists():
        raise FileNotFoundError(f"Input file not found: {path}")

    with path.open("r", encoding="utf-8") as handle:
        payload = json.load(handle)

    if isinstance(payload, list):
        logs = payload
    elif isinstance(payload, dict):
        logs = payload.get("scan_logs", [])
    else:
        logs = []

    return [row for row in logs if isinstance(row, dict)]


def analyze(logs: list[dict[str, Any]]) -> dict[str, Any]:
    decisions = Counter(normalize_decision(row.get("decision")) for row in logs)

    by_student: dict[str, list[dict[str, Any]]] = defaultdict(list)
    by_examiner: dict[str, list[dict[str, Any]]] = defaultdict(list)
    by_device: dict[str, list[dict[str, Any]]] = defaultdict(list)
    by_ip: dict[str, list[dict[str, Any]]] = defaultdict(list)

    for row in logs:
        student_key = str(row.get("matric_no") or row.get("student_id") or "unknown").strip()
        examiner_key = str(row.get("examiner_id") or "unknown").strip()
        device_key = str(row.get("device_fp") or "unknown").strip()
        ip_key = str(row.get("ip_address") or "unknown").strip()

        by_student[student_key].append(row)
        by_examiner[examiner_key].append(row)
        by_device[device_key].append(row)
        by_ip[ip_key].append(row)

    high_risk_students = analyze_students(by_student)
    suspicious_examiners = analyze_examiners(by_examiner)
    suspicious_devices = analyze_devices_or_ips(by_device, "device_fp")
    suspicious_ips = analyze_devices_or_ips(by_ip, "ip_address")

    risk_counts = Counter(item["risk_level"] for item in high_risk_students)
    risk_counts.update(item["risk_level"] for item in suspicious_examiners)
    risk_counts.update(item["risk_level"] for item in suspicious_devices)
    risk_counts.update(item["risk_level"] for item in suspicious_ips)

    report = {
        "generated_at": datetime.now(timezone.utc).isoformat(),
        "total_scans": len(logs),
        "approved_count": decisions.get("APPROVED", 0),
        "rejected_count": decisions.get("REJECTED", 0),
        "duplicate_count": decisions.get("DUPLICATE", 0),
        "high_risk_students": high_risk_students,
        "suspicious_examiners": suspicious_examiners,
        "suspicious_devices": suspicious_devices,
        "suspicious_ips": suspicious_ips,
        "risk_summary": {
            "low": risk_counts.get("low", 0),
            "medium": risk_counts.get("medium", 0),
            "high": risk_counts.get("high", 0),
            "overall_level": overall_level(high_risk_students, suspicious_examiners, suspicious_devices, suspicious_ips),
        },
        "recommendations": recommendations(high_risk_students, suspicious_examiners, suspicious_devices, suspicious_ips),
    }

    return report


def analyze_students(by_student: dict[str, list[dict[str, Any]]]) -> list[dict[str, Any]]:
    findings: list[dict[str, Any]] = []

    for matric_no, rows in by_student.items():
        score = 0
        reasons: list[str] = []
        token_counts = Counter(str(row.get("token_id") or "unknown") for row in rows)
        rejected_count = sum(1 for row in rows if normalize_decision(row.get("decision")) == "REJECTED")
        device_count = len({str(row.get("device_fp")) for row in rows if row.get("device_fp")})
        ip_count = len({str(row.get("ip_address")) for row in rows if row.get("ip_address")})
        payment_statuses = {normalize_status(row.get("payment_status")) for row in rows}
        qr_statuses = {str(row.get("qr_status") or "").strip().upper() for row in rows if row.get("qr_status")}

        if any(count > 1 for token, count in token_counts.items() if token != "unknown"):
            score += 30
            reasons.append("same token appears in duplicate scan activity")

        if rejected_count > 2:
            score += 25
            reasons.append("more than two rejected scans")

        if device_count > 1:
            score += 20
            reasons.append("student appears from multiple device fingerprints")

        if ip_count > 1:
            score += 15
            reasons.append("student appears from multiple IP addresses")

        if any(status not in {"verified", "verified demo payment"} for status in payment_statuses):
            score += 20
            reasons.append("payment status is not verified")

        unexpected_qr_statuses = {status for status in qr_statuses if status not in {"UNUSED", "USED"}}
        if unexpected_qr_statuses:
            score += 10
            reasons.append("QR status is outside expected UNUSED/USED states")

        if score > 0:
            example = rows[0]
            findings.append({
                "matric_no": matric_no,
                "student_id": example.get("student_id"),
                "department": example.get("department"),
                "level": example.get("level"),
                "score": score,
                "risk_level": risk_level(score),
                "reasons": reasons,
                "scan_count": len(rows),
                "rejected_count": rejected_count,
                "device_count": device_count,
                "ip_count": ip_count,
            })

    return sorted(findings, key=lambda item: item["score"], reverse=True)


def analyze_examiners(by_examiner: dict[str, list[dict[str, Any]]]) -> list[dict[str, Any]]:
    findings: list[dict[str, Any]] = []

    for examiner_id, rows in by_examiner.items():
        decisions = Counter(normalize_decision(row.get("decision")) for row in rows)
        rejected = decisions.get("REJECTED", 0)
        duplicate = decisions.get("DUPLICATE", 0)
        score = 0
        reasons: list[str] = []

        if rejected >= 5 or (len(rows) >= 4 and rejected / max(len(rows), 1) >= 0.5):
            score += 35
            reasons.append("unusually high rejected scan volume")

        if duplicate >= 3:
            score += 25
            reasons.append("many duplicate scan attempts")

        burst_count = max_scans_in_window(rows, seconds=120)
        if burst_count >= 8:
            score += 25
            reasons.append("too many scans in a very short time")

        if score > 0:
            findings.append({
                "examiner_id": examiner_id,
                "examiner_name": first_non_empty(rows, "examiner_name"),
                "score": score,
                "risk_level": risk_level(score),
                "reasons": reasons,
                "scan_count": len(rows),
                "rejected_count": rejected,
                "duplicate_count": duplicate,
                "max_two_minute_burst": burst_count,
            })

    return sorted(findings, key=lambda item: item["score"], reverse=True)


def analyze_devices_or_ips(grouped: dict[str, list[dict[str, Any]]], key_name: str) -> list[dict[str, Any]]:
    findings: list[dict[str, Any]] = []

    for key, rows in grouped.items():
        if key == "unknown":
            continue

        student_count = len({str(row.get("matric_no") or row.get("student_id")) for row in rows})
        rejected_or_duplicate = sum(
            1 for row in rows if normalize_decision(row.get("decision")) in {"REJECTED", "DUPLICATE"}
        )
        score = 0
        reasons: list[str] = []

        if student_count >= 3:
            score += 30
            reasons.append("appears across many students")

        if rejected_or_duplicate >= 3:
            score += 25
            reasons.append("has too many rejected or duplicate scans")

        if score > 0:
            findings.append({
                key_name: key,
                "score": score,
                "risk_level": risk_level(score),
                "reasons": reasons,
                "student_count": student_count,
                "scan_count": len(rows),
                "rejected_or_duplicate_count": rejected_or_duplicate,
            })

    return sorted(findings, key=lambda item: item["score"], reverse=True)


def max_scans_in_window(rows: list[dict[str, Any]], seconds: int) -> int:
    timestamps = sorted(ts for ts in (parse_timestamp(row.get("timestamp")) for row in rows) if ts)
    if not timestamps:
        return 0

    max_count = 1
    left = 0
    for right, current in enumerate(timestamps):
        while (current - timestamps[left]).total_seconds() > seconds:
            left += 1
        max_count = max(max_count, right - left + 1)
    return max_count


def first_non_empty(rows: list[dict[str, Any]], key: str) -> Any:
    for row in rows:
        value = row.get(key)
        if value:
            return value
    return None


def overall_level(*groups: list[dict[str, Any]]) -> str:
    highest = 0
    for group in groups:
        for item in group:
            highest = max(highest, int(item.get("score", 0)))
    return risk_level(highest)


def recommendations(*groups: list[dict[str, Any]]) -> list[str]:
    flat = [item for group in groups for item in group]
    if not flat:
        return ["No suspicious activity detected in the supplied scan data."]

    tips = [
        "Review high-risk students before issuing replacement or reprint access passes.",
        "Inspect suspicious examiner activity against hall assignment and scan timing.",
        "Check repeated device fingerprints or IP addresses for shared-phone or proxy patterns.",
    ]

    if any(item.get("risk_level") == "high" for item in flat):
        tips.insert(0, "Prioritize high-risk findings for manual admin review.")

    return tips


def write_json(path: Path, payload: dict[str, Any]) -> None:
    path.parent.mkdir(parents=True, exist_ok=True)
    with path.open("w", encoding="utf-8") as handle:
        json.dump(payload, handle, indent=2, ensure_ascii=False)
        handle.write("\n")


def main(argv: list[str]) -> int:
    base_dir = Path(__file__).resolve().parent
    input_path = Path(argv[1]) if len(argv) > 1 else base_dir / "sample_input.json"
    output_path = Path(argv[2]) if len(argv) > 2 else base_dir / "sample_output.json"

    try:
        logs = load_logs(input_path)
    except (FileNotFoundError, json.JSONDecodeError) as exc:
        print(f"CERNIX Risk Analyzer error: {exc}", file=sys.stderr)
        return 1

    report = analyze(logs)
    write_json(output_path, report)

    print(
        "CERNIX Risk Analyzer: "
        f"{report['total_scans']} scans, "
        f"{report['approved_count']} approved, "
        f"{report['rejected_count']} rejected, "
        f"{report['duplicate_count']} duplicate, "
        f"overall risk {report['risk_summary']['overall_level']}."
    )
    print(f"Wrote report to {output_path}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))
