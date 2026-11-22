# Doctor Order Workflow Report

- orders/index: paginated list, status badges, order links.
- orders/create: patient select + test checkbox grid (scrollable) + notes; field names/routes/CSRF unchanged.
- orders/show: order metadata dl + items table; result values/classification shown ONLY when result status = approved (doctor visibility rule preserved — original view showed only test names; approved results are the only ones doctors may see per results.view_own_doctor).
- No edit-order UI exists in the original — none added.
