#!/usr/bin/env python3
import os
import sys
import urllib.parse
from datetime import datetime

print("Cache-Control: no-cache")
print("Content-type: text/html")
print()

print("""<!DOCTYPE html>
<html>
<head>
  <title>GET Request Echo</title>

  <!-- Google tag (gtag.js) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-G3WM8DBKPE"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-G3WM8DBKPE');
  </script>

<!-- Matomo -->
<script>
  var _paq = window._paq = window._paq || [];
  /* tracker methods like "setCustomDimension" should be called before "trackPageView" */
  _paq.push(['trackPageView']);
  _paq.push(['enableLinkTracking']);
  (function() {
    var u="https://cse135hw1ensite.matomo.cloud/";
    _paq.push(['setTrackerUrl', u+'matomo.php']);
    _paq.push(['setSiteId', '1']);
    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
    g.async=true; g.src='https://cdn.matomo.cloud/cse135hw1ensite.matomo.cloud/matomo.js'; s.parentNode.insertBefore(g,s);
  })();
</script>
<!-- End Matomo Code -->

<script src="https://cdn.logr-in.com/LogRocket.min.js" crossorigin="anonymous"></script>
<script>window.LogRocket && window.LogRocket.init('w8qefj/cse135hw1en');</script>

</head>
<body>
  <h1 align="center">GET Request Echo</h1>
  <hr>
""")

# Enforce GET
if os.environ.get("REQUEST_METHOD", "") != "GET":
    print("Status: 405 Method Not Allowed")
    print("Allow: GET")
    print("Content-Type: text/html\r\n")
    print("<p><b>405 Method Not Allowed</b> (expected GET)</p>")
    sys.exit(0)


# Request info
hostname = os.environ.get("SERVER_NAME", "")
method = os.environ.get("REQUEST_METHOD", "")
client_ip = os.environ.get("REMOTE_ADDR", "")
user_agent = os.environ.get("HTTP_USER_AGENT", "")
server_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")

query_string = os.environ.get("QUERY_STRING", "")

print(f"""
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Python GET Echo</title>
</head>
<body>
  <h1 align="center">GET Request Echo (Python)</h1>
  <hr>

  <h3>Request Info</h3>
  <ul>
    <li><b>Hostname:</b> {hostname}</li>
    <li><b>Server Date/Time:</b> {server_time}</li>
    <li><b>Method:</b> {method}</li>
    <li><b>Client IP:</b> {client_ip}</li>
    <li><b>User-Agent:</b> {user_agent}</li>
  </ul>

  <h3>Received Query String</h3>
  <pre>{query_string}</pre>

  <h3>Parsed Parameters</h3>
  <ul>
""")

# Parse query string safely
parsed = urllib.parse.parse_qs(query_string)

if not parsed:
    print("<li>(none)</li>")
else:
    for key in sorted(parsed.keys()):
        value = parsed[key][0] if parsed[key] else ""
        print(f"<li>{key} = {value}</li>")

print("""
  </ul>
</body>
</html>
""")
