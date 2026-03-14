#!/usr/bin/perl
use CGI::Session;
use CGI;

print "Content-type: text/html\n\n";

my $cgi = new CGI;

my $session;
{
    my $sid = $cgi->cookie("SITE_SID") || $cgi->param("sid") || undef;
    $session  = new CGI::Session($sid);
}
$session->delete();

print "<html>";
print "<head>";
print "<title>Perl Session Destroyed</title>";
print q{
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
};

print "</head>";
print "<body>";
print "<h1>Session Destroyed</h1>";
print "<a href=\"/perl-cgiform.html\">Back to the Perl CGI Form</a><br />";
print "<a href=\"/cgi-bin/perl-sessions-1.pl\">Back to Page 1</a><br />";
print "<a href=\"/cgi-bin/perl-sessions-2.pl\">Back to Page 2</a>";
print "</body>";
print "</html>";
