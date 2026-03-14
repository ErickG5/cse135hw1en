#!/usr/bin/env python3
import os
import sys
import socket
import datetime
import html


print("Cache-Control: no-cache")
print("Content-type: text/html")
print()

print("""<!DOCTYPE html>
<html>
<head>
  <title>General Request Echo</title>


</head>
<body>
  <h1 align="center">General Request Echo</h1>
  <hr>
""")

# --- Required identity fields ---
hostname = os.environ.get("SERVER_NAME") or socket.gethostname()
now = datetime.datetime.now().astimezone().strftime("%Y-%m-%d %H:%M:%S %Z")
user_agent = os.environ.get("HTTP_USER_AGENT", "(none)")

# Client IP: forwarded first, fallback to REMOTE_ADDR
client_ip = os.environ.get("HTTP_X_FORWARDED_FOR", "")
if client_ip:
    client_ip = client_ip.split(",")[0].strip()
else:
    client_ip = os.environ.get("HTTP_X_REAL_IP", "")
if not client_ip:
    client_ip = os.environ.get("REMOTE_ADDR", "(unknown)")

print(f"<p><b>Hostname:</b> {html.escape(hostname)}</p>")
print(f"<p><b>Date/Time:</b> {html.escape(now)}</p>")
print(f"<p><b>User-Agent:</b> {html.escape(user_agent)}</p>")
print(f"<p><b>Client IP:</b> {html.escape(client_ip)}</p>")

print("<hr>")

# --- Echo what was received ---
query_string = os.environ.get("QUERY_STRING", "")
print(f"<p><b>Received Query String:</b> {html.escape(query_string)}</p>")

content_length = os.environ.get("CONTENT_LENGTH")
form_data = ""
if content_length:
    try:
        form_data = sys.stdin.read(int(content_length))
    except Exception:
        pass

print("<p><b>Received Message Body:</b></p>")
print(f"<pre>{html.escape(form_data)}</pre>")

print("""
</body>
</html>
""")
