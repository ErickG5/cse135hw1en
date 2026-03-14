#!/usr/bin/env python3
import cgi
import cgitb
import os
from http import cookies
import uuid
import html

cgitb.enable()

form = cgi.FieldStorage()
cookie = cookies.SimpleCookie()

# Read existing session ID
sid = None
if 'HTTP_COOKIE' in os.environ:
    cookie.load(os.environ['HTTP_COOKIE'])
    if 'CGISESSID' in cookie:
        sid = cookie['CGISESSID'].value

# Create session ID if missing
if not sid:
    sid = str(uuid.uuid4())

session_file = f"/tmp/sess_{sid}"

# Load stored name
name = None
if os.path.exists(session_file):
    try:
        with open(session_file, "r") as f:
            name = f.read().strip()
    except:
        pass

# Override name if form submitted
new_name = form.getvalue("username")
if new_name and new_name.strip():
    name = new_name.strip()
    try:
        with open(session_file, "w") as f:
            f.write(name)
    except:
        pass

# ---- HEADERS (must come before blank line) ----
print("Cache-Control: no-cache")
print("Content-Type: text/html")
cookie['CGISESSID'] = sid
cookie['CGISESSID']['path'] = '/'
print(cookie.output())   # IMPORTANT: prints "Set-Cookie: ..."
print()

# ---- BODY ----
print("""<!DOCTYPE html>
<html>
<head>
  <title>Python Sessions</title>

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
  <h1>Python Sessions Page 1</h1>
""")

if name:
    print(f"<p><b>Name:</b> {html.escape(name)}</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print("""
  <br/><br/>
  <a href="/cgi-bin/python/python-sessions-2.py">Session Page 2</a><br/>
  <a href="/python-cgiform.html">Python CGI Form</a><br/>
  <form style="margin-top:30px" action="/cgi-bin/python/python-destroy-session.py" method="get">
    <button type="submit">Destroy Session</button>
  </form>
</body>
</html>
""")
