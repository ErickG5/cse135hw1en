#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>
#include <ctype.h>

char* generate_uuid() {
    static char uuid[37];
    srand(time(NULL));
    snprintf(uuid, sizeof(uuid), "%08x-%04x-%04x-%04x-%08x%04x",
        rand() & 0xFFFFFFFF,
        rand() & 0xFFFF,
        (rand() & 0x0FFF) | 0x4000,
        (rand() & 0x3FFF) | 0x8000,
        rand() & 0xFFFFFFFF,
        rand() & 0xFFFF);
    return uuid;
}

char* url_decode(const char* src) {
    if (!src) return NULL;
    
    size_t src_len = strlen(src);
    char* decoded = malloc(src_len + 1);
    if (!decoded) return NULL;
    
    char* p = decoded;
    while (*src) {
        if (*src == '%' && isxdigit(src[1]) && isxdigit(src[2])) {
            char hex[3] = {src[1], src[2], '\0'};
            *p++ = (char)strtol(hex, NULL, 16);
            src += 3;
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

void write_file(const char* filename, const char* content) {
    FILE* file = fopen(filename, "w");
    if (file) {
        fprintf(file, "%s", content);
        fclose(file);
    }
}

char* read_post_data() {
    char* content_length_str = getenv("CONTENT_LENGTH");
    if (!content_length_str) return NULL;
    
    int content_length = atoi(content_length_str);
    if (content_length <= 0 || content_length > 10000) return NULL;
    
    char* post_data = malloc(content_length + 1);
    if (!post_data) return NULL;
    
    int bytes_read = fread(post_data, 1, content_length, stdin);
    post_data[bytes_read] = '\0';
    
    return post_data;
}

int main() {
    printf("Content-type: text/html\r\n");
    printf("Cache-Control: no-cache\r\n");
    
    char* cookie_header = getenv("HTTP_COOKIE");
    char* request_method = getenv("REQUEST_METHOD");
    char* query_string = NULL;
    char* sid = NULL;
    char* name = NULL;
    char* decoded_username = NULL;
    char* allocated_sid = NULL;
    char* post_data = NULL;
    
    // Get session ID from cookie
    if (cookie_header) {
        sid = get_cookie_value(cookie_header, "CGISESSID");
    }
    
    // Generate new session ID if needed
    if (!sid) {
        allocated_sid = strdup(generate_uuid());
        sid = allocated_sid;
    } else {
        sid = strdup(sid);
        allocated_sid = sid;
    }
    
    if (!sid) {
        printf("\r\n");
        printf("<html><body><h1>Error: Could not create session</h1></body></html>\n");
        return 1;
    }
    
    // Read existing session data
    char session_file[256];
    snprintf(session_file, sizeof(session_file), "/tmp/sess_%s", sid);
    name = read_file(session_file);
    
    // Get the data string (either from POST or GET)
    if (request_method && strcmp(request_method, "POST") == 0) {
        post_data = read_post_data();
        query_string = post_data;
    } else {
        query_string = getenv("QUERY_STRING");
    }
    
    // Parse query string for username
    if (query_string && strlen(query_string) > 0) {
        char* query_copy = strdup(query_string);
        if (query_copy) {
            char* token = strtok(query_copy, "&");
            while (token != NULL) {
                char* eq = strchr(token, '=');
                if (eq && strncmp(token, "username", 8) == 0) {
                    *eq = '\0';
                    decoded_username = url_decode(eq + 1);
                    *eq = '=';
                    break;
                }
                token = strtok(NULL, "&");
            }
            free(query_copy);
        }
    }
    
    // Update session if new username provided
    if (decoded_username && strlen(decoded_username) > 0) {
        if (name) free(name);
        name = strdup(decoded_username);
        write_file(session_file, name);
    }
    
    // Set session cookie - MUST be before the blank line
    printf("Set-Cookie: CGISESSID=%s; path=/\r\n", sid);
    printf("\r\n");
    
    // Output HTML
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
    printf("<h1>C Sessions Page 1</h1>\n");
    
    if (name && strlen(name) > 0) {
        printf("<p><b>Name:</b> %s</p>\n", name);
    } else {
        printf("<p><b>Name:</b> You do not have a name set</p>\n");
    }
    
    printf("<br/><br/>\n");
    printf("<a href=\"/cgi-bin/c/c-sessions-2.cgi\">Session Page 2</a><br/>\n");
    printf("<a href=\"/c-cgiform.html\">C CGI Form</a><br />\n");
    printf("<form style=\"margin-top:30px\" action=\"/cgi-bin/c/c-destroy-session.cgi\" method=\"get\">\n");
    printf("<button type=\"submit\">Destroy Session</button>\n");
    printf("</form>\n");
    printf("</body>\n");
    printf("</html>\n");
    
    // Cleanup
    if (allocated_sid) free(allocated_sid);
    if (name) free(name);
    if (decoded_username) free(decoded_username);
    if (post_data) free(post_data);
    
    return 0;
}