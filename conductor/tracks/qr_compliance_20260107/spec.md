# Specification: Verify Swiss QR-bill 2026 Compliance

## Overview
Verify the existing implementation of the Swiss QR-bill generation to ensure it meets the 2026 standards, specifically the use of structured addresses. Write comprehensive tests to maintain this compliance.

## Functional Requirements
- **Verification of Structured Addresses:** Confirm that `InvoiceController::generateQrBill` uses the structured address format.
- **Testing Suite:** Create tests to validate QR code content and invoice generation workflow.

## Acceptance Criteria
- [ ] Automated tests confirm QR codes use Structured Address (Type S).
- [ ] Tests verify that missing structured address data prevents invoice readiness.
