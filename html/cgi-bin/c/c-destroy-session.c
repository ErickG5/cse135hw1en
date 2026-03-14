#include <stdio.h>
#include <stdlib.h>
#include <string.h>

char* get_cookie_value(const char* cookie_header, const char* cookie_name) {
    if (!cookie_header) return NULL;
    
    char* cookies = strdup(cookie_header);
    if (!cookies) return NULL;
    
    char* token = strtok(cookies, ";");
    while (token != NULL) {
        while (*token == ' ') token++;
        if (strncmp(token, cookie_name, strlen(cookie_name)) == 0 && token[strlen(cookie_name)] == '=') {
            char* value = token + strlen(cookie_name) + 1;
            char* result = strdup(value);
            free(cookies);
            return result;
        }
        token = strtok(NULL, ";");
    }
    free(cookies);
    return NULL;
}

int main() {
    printf("Content-type: text/html\r\n");
    printf("Cache-Control: no-cache\r\n");
    
    // Get and delete the session cookie
    char* cookie_header = getenv("HTTP_COOKIE");
    char* sid = NULL;
    
    if (cookie_header) {
        sid = get_cookie_value(cookie_header, "CGISESSID");
    }
    
    // Delete the session file if it exists
    if (sid) {
        char session_file[256];
        snprintf(session_file, sizeof(session_file), "/tmp/sess_%s", sid);
        remove(session_file);
        free(sid);
    }
    
    // Delete the cookie by setting it to expire
    printf("Set-Cookie: CGISESSID=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/\r\n");
    printf("\r\n");
    
    printf("<!DOCTYPE html>\n");
    printf("<html>\n");
    printf("<head>\n");
    printf("<title>C Session Destroyed</title>\n");
    printf("<!-- Google tag (gtag.js) -->\n");
    printf("<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-G3WM8DBKPE\"></script>\n");
    printf("<script>\n");
    printf("  window.dataLayer = window.dataLayer || [];\n");
    printf("  function gtag(){dataLayer.push(arguments);}\n");
    printf("  gtag('js', new Date());\n");
    printf("  gtag('config', 'G-G3WM8DBKPE');\n");
    printf("</script>\n");
    printf("<!-- Matomo -->\n");
    printf("<script>\n");
    printf("  var _paq = window._paq = window._paq || [];\n");
    printf("  /* tracker methods like \"setCustomDimension\" should be called before \"trackPageView\" */\n");
    printf("  _paq.push(['trackPageView']);\n");
    printf("  _paq.push(['enableLinkTracking']);\n");
    printf("  (function() {\n");
    printf("    var u=\"https://cse135hw1ensite.matomo.cloud/\";\n");
    printf("    _paq.push(['setTrackerUrl', u+'matomo.php']);\n");
    printf("    _paq.push(['setSiteId', '1']);\n");
    printf("    var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n");
    printf("    g.async=true; g.src='https://cdn.matomo.cloud/cse135hw1ensite.matomo.cloud/matomo.js'; s.parentNode.insertBefore(g,s);\n");
    printf("  })();\n");
    printf("</script>\n");
    printf("<!-- End Matomo Code -->\n");
    printf("<script src=\"https://cdn.logr-in.com/LogRocket.min.js\" crossorigin=\"anonymous\"></script>\n");
    printf("<script>window.LogRocket && window.LogRocket.init('w8qefj/cse135hw1en');</script>\n");
    printf("</head>\n");
    printf("<body>\n");
    printf("<h1>Session Destroyed</h1>\n");
    printf("<a href=\"/c-cgiform.html\">Back to the C CGI Form</a><br />\n");
    printf("<a href=\"/cgi-bin/c/c-sessions-1.cgi\">Back to Page 1</a><br />\n");
    printf("<a href=\"/cgi-bin/c/c-sessions-2.cgi\">Back to Page 2</a>\n");
    printf("</body>\n");
    printf("</html>\n");
    
    return 0;
}