Contain Docker image that is ready to be use in Docker container

Application Bulk Submission to Channel Partner

To submit multiple applications at once

Rules

•	Login: email+password.

•	Any logged-in user can create another user.

•	User cannot be deleted or have any status.

•	User cannot change password. Password is encoded, can’t be change even on DB.

•	Applications + attachments are editable only when application.status = draft.

•	A submission is editable (partner and its applications) only when submission.status = draft.

•	Submitting a submission sets submissions.status = submitted, stamps submitted_by/at, and sets all its applications to status = submitted.

•	A submission cannot be submitted if it has no attached applications.

•	Any field update (in any table), will be logged. No UI, accessible by direct access to DB.

•	No record in any table can be deleted. Any unused record will stay unchanged.

Login: admin@example.com

Password: secret

Other files:

ERD.pdf - ERD diagram for system

required_APIs.pdf - list of APIs end point
