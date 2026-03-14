#!/usr/bin/env python3

print("Cache-Control: no-cache")
print("Content-Type: text/html")
print()  # Empty line to separate headers from content

print("<!DOCTYPE html>")
print("<html>")
print("<head>")
print("""
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-G3WM8DBKPE"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'G-G3WM8DBKPE');
</script>
""")
print("""<!-- Matomo -->
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
""")

print('''<script src="https://cdn.logr-in.com/LogRocket.min.js" crossorigin="anonymous"></script>
<script>window.LogRocket && window.LogRocket.init('w8qefj/cse135hw1en');</script>''')

print("<title>Hello CGI World</title>")
print("</head>")
print("<body>")

print("<h1 align=center>Hello HTML World</h1><hr/>")
print("<p>Hello World, This is team EN</p>")
print("<p>This page was generated with the Python programming language</p>")

# Import needed modules
import time
import os

# Get current date and time similar to Perl's localtime()
date = time.asctime(time.localtime())
print(f"<p>This program was generated at: {date}</p>")

# IP Address is an environment variable when using CGI
address = os.environ.get('REMOTE_ADDR', 'Unknown')
print(f"<p>Your current IP Address is: {address}</p>")

print("</body>")
print("</html>")
