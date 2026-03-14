#!/usr/bin/perl
use CGI;
use CGI::Session;

# print "Cache-Control: no-cache\n";

# Access Perl Session
use CGI::Session;

# Create a new CGI Object
$cgi = CGI->new;

# Get the Session ID from the Cookie
$sid = $cgi->cookie("CGISESSID") || undef;
$session = new CGI::Session(undef, $cgi, {Directory=>'/tmp'});

# Access Stored Data
$name = $session->param("username");
$color = $session->param("color");

print "Content-Type: text/html\n\n";

print "<html>";
print "<head>";
print "<title>PERL Sessions</title>";

print <<'GA';
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
GA

print "</head>";

print "<body>";

print "<h1>Perl Sessions Page 2</h1>";

if ($name){
	print("<p><b>Name:</b> $name");
}else{
	print "<p><b>Name:</b> You do not have a name set</p>";
}

if ($color){
        print("<p><b>Favorite color:</b> $color");
} else{
        print("<p><b>Favorite color:</b> You do not have a color set</p>")
}

print "<br/><br/>";
print "<a href=\"/cgi-bin/perl-sessions-1.pl\">Session Page 1</a><br/>";
print "<a href=\"/perl-cgiform.html\">Perl CGI Form</a><br />";
print "<form style=\"margin-top:30px\" action=\"/cgi-bin/perl-destroy-session.pl\" method=\"get\">";
print "<button type=\"submit\">Destroy Session</button>";
print "</form>";

print "</body>";
print "</html>";


