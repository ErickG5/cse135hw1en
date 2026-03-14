#!/usr/bin/perl
print "Cache-Control: no-cache\n";
print "Content-type: text/html \n\n";

use strict;
use warnings;


# print HTML file top
print <<END;
<!DOCTYPE html>
<html>
<head>
  <title>Post Request Echo</title>

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
  <h1 align="center">Post Request Echo</h1>
  <hr>
END

# Enforce POST
if (($ENV{'REQUEST_METHOD'} // '') ne 'POST') {
  print "Status: 405 Method Not Allowed\r\n";
  print "Allow: POST\r\n";
  print "Content-Type: text/html\r\n\r\n";
  print "<p><b>405 Method Not Allowed</b> (expected POST)</p>";
  exit;
}



# Basic request info
my $hostname    = $ENV{'SERVER_NAME'}     // '';
my $server_time = $ENV{'DATE_LOCAL'}      // scalar localtime();
my $method      = $ENV{'REQUEST_METHOD'}  // '';
my $client_ip   = $ENV{'REMOTE_ADDR'}     // '';
my $user_agent  = $ENV{'HTTP_USER_AGENT'} // '';

# Read POST body from STDIN using CONTENT_LENGTH
my $len  = $ENV{'CONTENT_LENGTH'} // 0;
my $body = '';
if ($len > 0) {
  read(STDIN, $body, $len);
}

# Parse body into %in
my %in;
if (length($body) > 0) {
  my @pairs = split(/&/, $body);
  foreach my $pair (@pairs) {
    my ($name, $value) = split(/=/, $pair, 2);
    $name  = '' unless defined $name;
    $value = '' unless defined $value;

    # Decode application/x-www-form-urlencoded
    $name  =~ tr/+/ /;
    $value =~ tr/+/ /;
    $name  =~ s/%([a-fA-F0-9]{2})/pack("C", hex($1))/eg;
    $value =~ s/%([a-fA-F0-9]{2})/pack("C", hex($1))/eg;

    $in{$name} = $value;
  }
}

print <<"HTML";
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Perl POST Echo</title>
</head>
<body>
  <h1 align="center">POST Request Echo (Perl)</h1>
  <hr>

  <h3>Request Info</h3>
  <ul>
    <li><b>Hostname:</b> $hostname</li>
    <li><b>Server Date/Time:</b> $server_time</li>
    <li><b>Method:</b> $method</li>
    <li><b>Client IP:</b> $client_ip</li>
    <li><b>User-Agent:</b> $user_agent</li>
  </ul>

  <h3>Received Body</h3>
  <pre>$body</pre>

  <h3>Parsed Parameters</h3>
  <ul>
HTML

if (!keys %in) {
  print "<li>(none)</li>\n";
} else {
  foreach my $key (sort keys %in) {
    print "<li>$key = $in{$key}</li>\n";
  }
}


print "</ul>\n";
# Print the HTML file bottom
print "</body></html>\n";
