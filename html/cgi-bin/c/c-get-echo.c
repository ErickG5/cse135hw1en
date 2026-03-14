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
    if (!method || strcmp(method, "GET") != 0) {
        print_405("GET");
        return 0;
    }

printf("Cache-Control: no-cache, no-store, must-revalidate\r\n");
printf("Pragma: no-cache\r\n");
printf("Expires: 0\r\n");
printf("Content-Type: text/html\r\n\r\n");


    // Request info
    const char* hostname   = getenv("SERVER_NAME");
    const char* remoteaddr = getenv("REMOTE_ADDR");
    const char* useragent  = getenv("HTTP_USER_AGENT");
    const char* xff        = getenv("HTTP_X_FORWARDED_FOR"); // if behind proxy
    const char* client_ip  = (xff && *xff) ? xff : remoteaddr;

    // Server time
    time_t now = time(NULL);
    char timestr[64];
    strftime(timestr, sizeof(timestr), "%Y-%m-%d %H:%M:%S %z", localtime(&now));

    const char* query_string = getenv("QUERY_STRING");
    if (!query_string) query_string = "";

    // Page start
    printf("<!DOCTYPE html>\n");
    printf("<html><head>\n");
    printf("  <meta charset=\"utf-8\">\n");
printf("<h1 align=\"center\">Get Request Echo</h1>\n");
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
    printf("  <h1 align=\"center\">GET Request Echo (C)</h1>\n");
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

    // Raw query string
    printf("  <h3>Received Query String</h3>\n");
    printf("  <pre>%s</pre>\n", query_string);

    // Parsed params
    printf("  <h3>Parsed Parameters</h3>\n");
    printf("  <ul>\n");

    if (strlen(query_string) == 0) {
        printf("    <li>(none)</li>\n");
    } else {
        char* query_copy = strdup(query_string);
        if (query_copy) {
            char* token = strtok(query_copy, "&");
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
            free(query_copy);
        } else {
            printf("    <li>(error: out of memory)</li>\n");
        }
    }

    printf("  </ul>\n");
    printf("</body></html>\n");
    return 0;
}
