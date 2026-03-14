# cse135hw1en

**Team Members:** 
- Erick Garcia Dealba
- Noe Arguello Soto

---

## Grader Account
**Username:** grader  
**Password:** grader  

### SSH Private Key for grader:

-----BEGIN OPENSSH PRIVATE KEY-----

b3BlbnNzaC1rZXktdjEAAAAACmFlczI1Ni1jdHIAAAAGYmNyeXB0AAAAGAAAABD3UKA52p
0kgvVXxjcPqHdFAAAAGAAAAAEAAAAzAAAAC3NzaC1lZDI1NTE5AAAAIPQbuajROStIEgVE
f7MRcUU3+ND46lwrpcg4wkSaVymBAAAAoP9yuRC+ypZjFImTPueC/Nzlj/XaYbc5AgU7sm
Kdw+qEJ+L1vMz6eD+iIkUnZsM7kQxuNWFyV2xMLmsrCU0amfBHLJqZcGjtDKaEwcRJw6SV
/PBb9I/GN+rlqhXTGuHjJz3yqIq3Qdi7l6JT/s9Nis5f12D919IPVRxyeXPBcM2JqdxB4I
eiIB5184Rw3aqmUFou25u1+63+m+nqNjzdbH0=

-----END OPENSSH PRIVATE KEY-----



**Passphrase:** grader  

**Website Link:** [https://cse135hw1en.site/](https://cse135hw1en.site/)

---

## HW1

### GitHub Auto Deploy
The way that this site is deployed is using a Git post-receive hook on the server. Any changes pushed onto the main branch are checked into the web root, meaning that updates are immediate after pushing it through. An example of this is provided in the video.

### Website Logins
- **user:** erick  
  **Password:** Sentros2@
- **user:** noe  
  **Password:** narguello2

### Compression
After deploying the compression, it was noticed that in the network tab of DevTools a new response header was seen: `content-encoding: gzip`. This indicates that the files were compressed before being sent to the browser, which in turn improved performance.

### Server
We saw that Apache generates the server header internally and does not allow a simple rename to be executed. To combat this, we had to employ a reverse proxy. This ensured that when we rewrote the server response header, it would display in browser responses.

---

## HW2

### Our Third Approach: Matomo Analytics
We chose Matomo as our third analytics platform. It's a self-hosted alternative to Google Analytics that you run on your own server. We picked it because:
- It's open-source and free
- It gives us complete data ownership
- It has privacy built-in by default
- It lets us learn how analytics actually work behind the scenes

#### What We Discovered

**Pros:**
- **Real-time data:** See visitors immediately vs Google's 24-hour delay
- **Lightweight:** 15KB script
- **Full control:** We can customize anything since we have the source code

**Cons:**
- **Maintenance:** We're responsible for updates and security
- **Server load:** Uses our server's CPU and memory

---

# HW3

### What we added to collector.js:
- Cookie-based sessions that persist across tabs and browser restarts
- Tracks mouse moves, clicks, scrolls, keypresses
- Detects when user is idle for 2+ seconds
- Records when user enters and leaves the page
- Detects if images, CSS, and JavaScript are enabled

### Quick Test URLs (paste in browser)
- [https://reporting.cse135hw1en.site/api/events](https://reporting.cse135hw1en.site/api/events)
- [https://reporting.cse135hw1en.site/api/static](https://reporting.cse135hw1en.site/api/static)
- [https://reporting.cse135hw1en.site/api/performance](https://reporting.cse135hw1en.site/api/performance)
- [https://reporting.cse135hw1en.site/api/activity](https://reporting.cse135hw1en.site/api/activity)
- [https://reporting.cse135hw1en.site/api/sessions](https://reporting.cse135hw1en.site/api/sessions)



---

# HW4

1st requirement: MVC style app with authentication and navigation

The dashboard was made using a simple MVC architecture design. The models handle the queries in the database, the controllers process all requests and pass the data to views, and the views render the dashboard pages. We made sure that only by logging in that the user is able to see this data. This means that typing in the url will not allow you access and redirect you to the login page. 

2nd requirement: Connecting our Datastore to a Data Table/ Grid

The data collected from the collector is stored in the database made using MySQL. The reports page connects to the database and displays this information in a table. 

3rd requirement: Connecting our Datastore to a Chart

In the charts page we have visualizations of sessions, browser distribution and page load times. This is achieved by the charts page reading data from the database and using ChartJS as our visualizer.


URL for testing:
https://reporting.cse135hw1en.site/login.php

Login Credentials:
Username: grader
Password: cse135

---

# HW5 


### Links
- **GitHub Repository**: [https://github.com/ErickG5/cse135hw1en.git](https://github.com/ErickG5/cse135hw1en.git)
- **Deployed Application**: [https://reporting.cse135hw1en.site/login.php](https://reporting.cse135hw1en.site/login.php)
- **Collector Endpoint**: [https://collector.cse135hw1en.site/endpoint.js](https://collector.cse135hw1en.site/endpoint.js)

### Tech Stack
- Frontend: PHP, serverside rendering
- Backend: Node.js, PHP
- Database: MySQL
- Deployment: Vercel, Heroku

### Key Features Implemented
- Role-based access control (super_admin, analyst, viewer)
- Event analytics tracking (pageviews, errors, activity, exits)
- Responsive dashboard UI

### AI Usage
Used AI tools (ChatGPT and GitHub Copilot) extensively for:
- **UI generation**: AI helped create the CSS styles, card layouts, and responsive table designs
- **Debugging**: AI assisted in fixing authentication issues and role-check middleware bugs
- **Code boilerplate**: Generated repetitive PHP and SQL query patterns

**Observation**: AI was extremely helpful for frontend styling and debugging sessions where we were stuck. It saved hours of time on CSS and catching syntax errors. However, AI-generated code sometimes needed manual tweaks to fit our specific database schema. Overall, it was a net positive - we'd definitely use it again for UI work and troubleshooting.

### Future Roadmap
With more time, we would:

- **Reorganize architecture**: Restructure files into feature-based folders for better navigation
- **Implement proper MVC**: Separate models/views/controllers instead of mixing logic
- **Standardize APIs**: Pick one language (PHP or Node.js) instead of hybrid approach
