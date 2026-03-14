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

char* read_file(const char* filename) {
    FILE* file = fopen(filename, "r");
    if (!file) return NULL;
    
    fseek(file, 0, SEEK_END);
    long size = ftell(file);
    if (size <= 0) {
        fclose(file);
        return strdup("");
    }
    
    fseek(file, 0, SEEK_SET);
    
    char* content = malloc(size + 1);
    if (!content) {
        fclose(file);
        return NULL;
    }
    
    fread(content, 1, size, file);
    content[size] = '\0';
    
    fclose(file);
    return content;
}

int main() {
    printf("Content-Type: text/html\r\n");
    printf("Cache-Control: no-cache\r\n");
    printf("\r\n");
    
    char* cookie_header = getenv("HTTP_COOKIE");
    char* sid = NULL;
    char* name = NULL;
    
    if (cookie_header) {
        sid = get_cookie_value(cookie_header, "CGISESSID");
    }
    
    if (sid) {
        char session_file[256];
        snprintf(session_file, sizeof(session_file), "/tmp/sess_%s", sid);
        name = read_file(session_file);
    }
    
    printf("<!DOCTYPE html>\n");
    printf("<html>\n");
    printf("<head>\n");
    printf("<title>C Sessions</title>\n");
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
    printf("<h1>C Sessions Page 2</h1>\n");
    
    if (name && strlen(name) > 0) {
        printf("<p><b>Name:</b> %s</p>\n", name);
    } else {
        printf("<p><b>Name:</b> You do not have a name set</p>\n");
    }
    
    printf("<br/><br/>\n");
    printf("<a href=\"/cgi-bin/c/c-sessions-1.cgi\">Session Page 1</a><br/>\n");
    printf("<a href=\"/c-cgiform.html\">C CGI Form</a><br />\n");
    printf("<form style=\"margin-top:30px\" action=\"/cgi-bin/c/c-destroy-session.cgi\" method=\"get\">\n");
    printf("<button type=\"submit\">Destroy Session</button>\n");
    printf("</form>\n");
    printf("</body>\n");
    printf("</html>\n");
    
    if (sid) free(sid);
    if (name) free(name);
    
    return 0;
}