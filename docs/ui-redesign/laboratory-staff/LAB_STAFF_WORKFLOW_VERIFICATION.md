# Workflow Verification

Preserved verbatim: sample receive/match (`lab.samples.receive`), result save draft/submit (`lab.results.save`), start analysis (`lab.results.start_analysis`), approve/return/reject gated by `results.approve` permission, critical acknowledge, attachment upload/download ACL, order status filter param (`status=new` etc.).

Phase2AcceptanceTest lab-portal tests pass: `test_lab_portal_screens_render`, `ac07 result entry draft and submit`, `ac13 approve via permission not lab tech`, `return for correction and reedit`, `server validation rejects invalid submit`, `attachments upload download acl and audit`, `reject mime not allowed` — all green.
