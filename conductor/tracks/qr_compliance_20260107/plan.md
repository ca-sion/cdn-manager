# Plan: Verify Swiss QR-bill 2026 Compliance

## Phase 1: Code Verification and Testing
- [ ] Task: Audit Existing Implementation
    - [ ] Sub-task: Review `InvoiceController::generateQrBill` to confirm `StructuredAddress` usage.
    - [ ] Sub-task: Verify `Client` model has necessary fields (street, house_number, etc.) and they are correctly used.
- [ ] Task: Create Compliance Tests
    - [ ] Sub-task: Write Tests: Create a Pest test to mock an invoice and verify the `QrBill` object structure (specifically `StructuredAddress`).
    - [ ] Sub-task: Write Tests: Create a feature test to verify the full PDF generation path via `invoices.show`.
- [ ] Task: Conductor - User Manual Verification 'Phase 1: Code Verification and Testing' (Protocol in workflow.md)
