# GRADER.md

## Login Credentials

**Super Admin**
- Username: `grader`
- Password: `cse135`

**Analyst**
- Username: `analyst1`
- Password: `Analysis123`

- Username: `erick`
- Password: `erick1234`

- Username: `John`
- Password: `Smithy2`

**Viewer**
- Username: `viewer1`
- Password: `Viewer456`

---

## Guided Test Scenario

**Step 1: Login as Super Admin**
- Username: `grader`
- Password: `cse135`

**Step 2: Create and Modify a Report**
- Navigate to **Traffic Reports**
- Prepare a report snapshot (select any date range)
- Add a comment
- Generate Report
- Go to **Saved Reports**
- Find your saved report
- Edit the comment and **Save**

**Step 3: Create a New Analyst User**
- Go to **Manage Users**
- Click **Add New User**
- Select Role: **Analyst**
- Choose any username and password
- Set allowed section to: `traffic`
- Click **Create**
- Logout

**Step 4: Test Analyst Permissions**
- Login with the new analyst account you just created
- Go to **Saved Reports**
- Find the report created by the Super Admin
- Edit the comment
- Attempt to delete the report
- Logout

---

## Known Issues and Concerns

- **Inconsistent loading times**: Page load times vary randomly. Some pages load quickly while others take significantly longer without clear pattern.

- **Disorganized file structure**: Files are not properly organized into feature-based folders, making navigation difficult. We should have planned the architecture better from the start.

- **Limited error chart data**: The error charts show basic metrics but don't provide enough detail about specific error types, frequencies, or patterns. We would have liked to include more granular data like error status codes, timestamps, and user impact.