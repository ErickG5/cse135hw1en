#!/usr/bin/env python3
import json
import os
import time

print("Cache-Control: no-cache")
print("Content-type: application/json")
print()

date = time.asctime(time.localtime())
address = os.environ.get('REMOTE_ADDR', '')

message = {
    'title': 'Hello, Python!',
    'heading': 'Hello, Python!',
    'message': 'This page was generated with the Python programming language',
    'time': date,
    'IP': address
}

json_output = json.dumps(message)
print(json_output)
