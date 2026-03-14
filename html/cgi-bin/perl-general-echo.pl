#!/usr/bin/perl
print "Content-Type: text/html; charset=UTF-8\r\n";
print "Cache-Control: no-store\r\n\r\n";

# print HTML file top
print <<END;
<!DOCTYPE html>
<html>
<head>
  <title>General Request Echo</title>


</head>
<body>
  <h1 align="center">General Request Echo</h1>
  <hr>
END

# --- Required "unique request" fields ---
use Sys::Hostname;
use POSIX qw(strftime);

my $hostname = $ENV{'SERVER_NAME'} || hostname();
my $datetime = strftime("%Y-%m-%d %H:%M:%S %Z", localtime());
my $user_agent = $ENV{'HTTP_USER_AGENT'} || '(none)';

# Client IP: prefer forwarded if present, else REMOTE_ADDR
my $client_ip = $ENV{'REMOTE_ADDR'} || '(unknown)';
if ($ENV{'HTTP_X_FORWARDED_FOR'}) {
  ($client_ip) = split(/\s*,\s*/, $ENV{'HTTP_X_FORWARDED_FOR'});
} elsif ($ENV{'HTTP_X_REAL_IP'}) {
  $client_ip = $ENV{'HTTP_X_REAL_IP'};
}

print "<p><b>Hostname:</b> $hostname</p>\n";
print "<p><b>Date/Time:</b> $datetime</p>\n";
print "<p><b>User-Agent:</b> $user_agent</p>\n";
print "<p><b>Client IP:</b> $client_ip</p>\n";

print "<hr>";

# --- Echo back what was received ---
my $query = $ENV{'QUERY_STRING'} || '';
print "<p><b>Received Query String:</b> $query</p>\n";

my $form_data = '';
my $len = $ENV{'CONTENT_LENGTH'} || 0;
if ($len > 0) {
  read(STDIN, $form_data, $len);
}
print "<p><b>Received Message Body:</b></p>\n<pre>$form_data</pre>";

# Print the HTML file bottom
print "</body></html>\n";
