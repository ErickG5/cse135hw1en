#!/usr/bin/perl
use CGI;

# Create a new Perl Session
use CGI::Session;
$session = new CGI::Session("driver:File", undef, {Directory=>"/tmp"});

# Create CGI Object
$cgi = CGI->new;

# Create a new Cookie from the Session ID
$cookie = $cgi->cookie(
  -name  => 'CGISESSID',
  -value => $session->id,
  -path  => '/'
);
print $cgi->header( -cookie => $cookie );

#Store Data in that Perl Session
# Prefer newly submitted params; otherwise fall back to session
my $new_name  = $cgi->param('username');
my $new_color = $cgi->param('color');

# Only overwrite session if a new value was submitted (prevents wipe on refresh)
if (defined $new_name && $new_name ne '') {
    $session->param('username', $new_name);
}
if (defined $new_color && $new_color ne '') {
    $session->param('color', $new_color);
}

# Values to display come from session
my $name  = $session->param('username');
my $color = $session->param('color');


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

print "<h1>Perl Sessions Page 1</h1>";

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
print "<a href=\"/cgi-bin/perl-sessions-2.pl\">Session Page 2</a><br/>";
print "<a href=\"/perl-cgiform.html\">Perl CGI Form</a><br />";
print "<form style=\"margin-top:30px\" action=\"/cgi-bin/perl-destroy-session.pl\" method=\"get\">";
print "<button type=\"submit\">Destroy Session</button>";
print "</form>";

print "</body>";
print "</html>";
