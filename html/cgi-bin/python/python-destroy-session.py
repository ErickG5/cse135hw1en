#!/usr/bin/env python3
import cgi
import cgitb
import os
from http import cookies

cgitb.enable()

form = cgi.FieldStorage()
cookie = cookies.SimpleCookie()
sid = None

COOKIE_NAME = "CGISESSID"

# Read cookie if present
if 'HTTP_COOKIE' in os.environ:
    cookie.load(os.environ['HTTP_COOKIE'])
    if COOKIE_NAME in cookie:
        sid = cookie[COOKIE_NAME].value

# Fallback to sid parameter
if not sid and form.getvalue('sid'):
    sid = form.getvalue('sid')

# Delete session file
if sid:
    session_file = f"/tmp/sess_{sid}"
    if os.path.exists(session_file):
        try:
            os.remove(session_file)
        except:
            pass

# Expire cookie (path MUST match what you set when creating it)
cookie[COOKIE_NAME] = ""
cookie[COOKIE_NAME]["path"] = "/"
cookie[COOKIE_NAME]["expires"] = "Thu, 01 Jan 1970 00:00:00 GMT"
cookie[COOKIE_NAME]["max-age"] = 0

# ---- HTTP HEADERS ----
print("Cache-Control: no-cache")
print("Content-Type: text/html")
print(cookie.output())  # Set-Cookie: CGISESSID=...; expires=...; Max-Age=0; Path=/
print()                 # end headers

# ---- HTML BODY ----
print("""<!DOCTYPE html>
<html>
<head>
  <title>Python Session Destroyed</title>

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
  <h1>Session Destroyed</h1>
  <a href="/python-cgiform.html">Back to the Python CGI Form</a><br />
  <a href="/cgi-bin/python/python-sessions-1.py">Back to Page 1</a><br />
  <a href="/cgi-bin/python/python-sessions-2.py">Back to Page 2</a>
</body>
</html>""")
