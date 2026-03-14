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

## HW3

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