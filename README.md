# cse135hw1en

Team Members: 
Erick Garcia Dealba
Noe Arguello Soto


Grader Account:
Username: grader
Password: grader

SSH Private Key for grader: 
Private key: 
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAACmFlczI1Ni1jdHIAAAAGYmNyeXB0AAAAGAAAABD3UKA52p
0kgvVXxjcPqHdFAAAAGAAAAAEAAAAzAAAAC3NzaC1lZDI1NTE5AAAAIPQbuajROStIEgVE
f7MRcUU3+ND46lwrpcg4wkSaVymBAAAAoP9yuRC+ypZjFImTPueC/Nzlj/XaYbc5AgU7sm
Kdw+qEJ+L1vMz6eD+iIkUnZsM7kQxuNWFyV2xMLmsrCU0amfBHLJqZcGjtDKaEwcRJw6SV
/PBb9I/GN+rlqhXTGuHjJz3yqIq3Qdi7l6JT/s9Nis5f12D919IPVRxyeXPBcM2JqdxB4I
eiIB5184Rw3aqmUFou25u1+63+m+nqNjzdbH0=
-----END OPENSSH PRIVATE KEY-----

Passphrase: grader

Website Link: https://cse135hw1en.site/


Github Auto Deploy:
The way that this site is deployed is using a Git post-receive hook on the server. Any changes pushed onto the main branch are checked into the web root, meaning that updates are immediate after pushing it through. An example of this is provided in the video.

Website Logins:
user: erick Password: Sentros2@
user: noe Password: narguello2

Compression:
After deploying the compression it was noticed that in the network tab of DevTools a new response header was seen. This being content-encoding: gzip which indicates to us that the fiels were compressed before being sent to the browser. This in turn improved performance. 

Server:
We saw that Apache generates the header for server internally and does not allow a simple rename to be executed. To combat this we had to employ a reverse proxy. This ensured that when we rewrote the server response header we would be sure that it would display in browser responses. 