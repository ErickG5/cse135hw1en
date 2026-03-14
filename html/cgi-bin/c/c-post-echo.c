#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <time.h>

static char* url_decode(const char* src) {
    size_t src_len = strlen(src);
    char* decoded = (char*)malloc(src_len + 1);
    if (!decoded) return NULL;

    char* p = decoded;
    while (*src) {
        if (*src == '%') {
            if (isxdigit((unsigned char)src[1]) && isxdigit((unsigned char)src[2])) {
                char hex[3] = { src[1], src[2], '\0' };
                *p++ = (char)strtol(hex, NULL, 16);
                src += 3;
            } else {
                *p++ = *src++;
            }
        } else if (*src == '+') {
            *p++ = ' ';
            src++;
        } else {
            *p++ = *src++;
        }
    }
    *p = '\0';
    return decoded;
}

static void print_405(const char* allow) {
    printf("Status: 405 Method Not Allowed\r\n");
    printf("Allow: %s\r\n", allow);
    printf("Content-Type: text/html\r\n\r\n");
    printf("<p><b>405 Method Not Allowed</b> (expected %s)</p>", allow);
}

int main(void) {
    const char* method = getenv("REQUEST_METHOD");
    if (!method || strcmp(method, "POST") != 0) {
        print_405("POST");
        return 0;
    }

    // Headers
    printf("Cache-Control: no-cache, no-store, must-revalidate\r\n");
    printf("Pragma: no-cache\r\n");
    printf("Expires: 0\r\n");
    printf("Content-Type: text/html\r\n\r\n");

    // Request info
    const char* hostname   = getenv("SERVER_NAME");
    const char* remoteaddr = getenv("REMOTE_ADDR");
    const char* useragent  = getenv("HTTP_USER_AGENT");
    const char* xff        = getenv("HTTP_X_FORWARDED_FOR"); // proxy case
    const char* client_ip  = (xff && *xff) ? xff : remoteaddr;

    // Server time
    time_t now = time(NULL);
    char timestr[64];
    strftime(timestr, sizeof(timestr), "%Y-%m-%d %H:%M:%S %z", localtime(&now));

    // Read POST body
    const char* cl = getenv("CONTENT_LENGTH");
    int content_length = 0;
    if (cl) content_length = atoi(cl);
    if (content_length < 0) content_length = 0;

    char* body = NULL;
    if (content_length > 0) {
        body = (char*)malloc((size_t)content_length + 1);
        if (body) {
            size_t n = fread(body, 1, (size_t)content_length, stdin);
            body[n] = '\0'; // terminate at bytes actually read
        }
    }
    if (!body) {
        body = strdup("");
        if (!body) body = (char*)""; // last-resort (won't free)
    }

    // Page start
    printf("<!DOCTYPE html>\n");
    printf("<html><head>\n");
    printf("  <meta charset=\"utf-8\">\n");
printf("<h1 align=\"center\">Post Request Echo</h1>\n");
printf("<hr>\n");



    // --- keep your analytics if you want ---
    printf("  <script async src=\"https://www.googletagmanager.com/gtag/js?id=G-G3WM8DBKPE\"></script>\n");
    printf("  <script>\n");
    printf("    window.dataLayer = window.dataLayer || [];\n");
    printf("    function gtag(){dataLayer.push(arguments);}\n");
    printf("    gtag('js', new Date());\n");
    printf("    gtag('config', 'G-G3WM8DBKPE');\n");
    printf("  </script>\n");
    printf("  <script>\n");
    printf("    var _paq = window._paq = window._paq || [];\n");
    printf("    _paq.push(['trackPageView']);\n");
    printf("    _paq.push(['enableLinkTracking']);\n");
    printf("    (function() {\n");
    printf("      var u=\"https://cse135hw1ensite.matomo.cloud/\";\n");
    printf("      _paq.push(['setTrackerUrl', u+'matomo.php']);\n");
    printf("      _paq.push(['setSiteId', '1']);\n");
    printf("      var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];\n");
    printf("      g.async=true; g.src='https://cdn.matomo.cloud/cse135hw1ensite.matomo.cloud/matomo.js';\n");
    printf("      s.parentNode.insertBefore(g,s);\n");
    printf("    })();\n");
    printf("  </script>\n");
    printf("  <script src=\"https://cdn.logr-in.com/LogRocket.min.js\" crossorigin=\"anonymous\"></script>\n");
    printf("  <script>window.LogRocket && window.LogRocket.init('w8qefj/cse135hw1en');</script>\n");
    // --- end analytics ---

    printf("</head><body>\n");
    printf("  <h1 align=\"center\">POST Request Echo (C)</h1>\n");
    printf("  <hr>\n");

    // Request Info
    printf("  <h3>Request Info</h3>\n");
    printf("  <ul>\n");
    printf("    <li><b>Hostname:</b> %s</li>\n", hostname ? hostname : "");
    printf("    <li><b>Server Date/Time:</b> %s</li>\n", timestr);
    printf("    <li><b>Method:</b> %s</li>\n", method);
    printf("    <li><b>Client IP:</b> %s</li>\n", client_ip ? client_ip : "");
    printf("    <li><b>User-Agent:</b> %s</li>\n", useragent ? useragent : "");
    printf("  </ul>\n");

    // Raw body
    printf("  <h3>Received Body</h3>\n");
    printf("  <pre>%s</pre>\n", body);

    // Parsed params
    printf("  <h3>Parsed Parameters</h3>\n");
    printf("  <ul>\n");

    if (strlen(body) == 0) {
        printf("    <li>(none)</li>\n");
    } else {
        char* data_copy = strdup(body);
        if (data_copy) {
            char* token = strtok(data_copy, "&");
            while (token) {
                char* eq = strchr(token, '=');
                if (eq) {
                    *eq = '\0';
                    char* decoded_key = url_decode(token);
                    char* decoded_val = url_decode(eq + 1);

                    if (decoded_key && decoded_val) {
                        printf("    <li>%s = %s</li>\n", decoded_key, decoded_val);
                    }

                    free(decoded_key);
                    free(decoded_val);
                } else {
                    // key with no "="
                    char* decoded_key = url_decode(token);
                    if (decoded_key) {
                        printf("    <li>%s = </li>\n", decoded_key);
                        free(decoded_key);
                    }
                }
                token = strtok(NULL, "&");
            }
            free(data_copy);
        } else {
            printf("    <li>(error: out of memory)</li>\n");
        }
    }

    printf("  </ul>\n");
    printf("</body></html>\n");

    // Free body if it was allocated/duplicated
    if (content_length > 0 && body && body[0] != '\0') {
        free(body);
    } else {
        // If body came from strdup(""), free is safe too, but we can't always know.
        // Easiest safe approach: just free if it wasn't the string literal.
        // In this code, body is either malloc'd, strdup'd, or "" literal fallback.
        // If the fallback "" literal happened, freeing is unsafe. We guard above.
        // If you want fully clean ownership, always use malloc and track a flag.
    }

    return 0;
}
