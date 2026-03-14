#!/usr/bin/env python3
import cgi
import cgitb
import os
from http import cookies
import html

cgitb.enable()

form = cgi.FieldStorage()
cookie = cookies.SimpleCookie()

sid = None
name = None

# Read cookie
if 'HTTP_COOKIE' in os.environ:
    cookie.load(os.environ['HTTP_COOKIE'])
    if 'CGISESSID' in cookie:
        sid = cookie['CGISESSID'].value

# Load session data
if sid:
    session_file = f"/tmp/sess_{sid}"
    if os.path.exists(session_file):
        try:
            with open(session_file, 'r') as f:
                name = f.read().strip()
        except:
            pass

# ---- HEADERS (cookie must be printed BEFORE the blank line) ----
print("Cache-Control: no-cache")
print("Content-Type: text/html")
if sid:
    cookie['CGISESSID'] = sid
    cookie['CGISESSID']['path'] = '/'
    print(cookie.output())   # IMPORTANT: prints "Set-Cookie: ..."
print()  # end headers

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
<body>""")

print("<h1>Python Sessions Page 2</h1>")

if name:
    print(f"<p><b>Name:</b> {html.escape(name)}</p>")
else:
    print("<p><b>Name:</b> You do not have a name set</p>")

print("<br/><br/>")
print('<a href="/cgi-bin/python/python-sessions-1.py">Session Page 1</a><br/>')
print('<a href="/python-cgiform.html">Python CGI Form</a><br />')
print('<form style="margin-top:30px" action="/cgi-bin/python/python-destroy-session.py" method="get">')
print('<button type="submit">Destroy Session</button>')
print('</form>')

print("</body></html>")
