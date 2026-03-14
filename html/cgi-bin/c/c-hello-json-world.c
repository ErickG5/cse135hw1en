#include <stdio.h>
#include <stdlib.h>
#include <time.h>
#include <string.h>

char* escape_json_string(const char* str) {
    if (!str) return strdup("");
    
    size_t len = strlen(str);
    size_t new_len = len;
    
    for (size_t i = 0; i < len; i++) {
        if (str[i] == '\"' || str[i] == '\\' || str[i] == '\n' || str[i] == '\r' || str[i] == '\t' || str[i] == '\b' || str[i] == '\f') {
            new_len++;
        }
    }
    
    char* escaped = malloc(new_len + 1);
    if (!escaped) return NULL;
    
    size_t j = 0;
    for (size_t i = 0; i < len; i++) {
        switch (str[i]) {
            case '\"': 
                escaped[j++] = '\\'; 
                escaped[j++] = '\"'; 
                break;
            case '\\': 
                escaped[j++] = '\\'; 
                escaped[j++] = '\\'; 
                break;
            case '\n': 
                escaped[j++] = '\\'; 
                escaped[j++] = 'n'; 
                break;
            case '\r': 
                escaped[j++] = '\\'; 
                escaped[j++] = 'r'; 
                break;
            case '\t': 
                escaped[j++] = '\\'; 
                escaped[j++] = 't'; 
                break;
            case '\b': 
                escaped[j++] = '\\'; 
                escaped[j++] = 'b'; 
                break;
            case '\f': 
                escaped[j++] = '\\'; 
                escaped[j++] = 'f'; 
                break;
            default: 
                escaped[j++] = str[i]; 
                break;
        }
    }
    escaped[j] = '\0';
    return escaped;
}

int main() {
    printf("Cache-Control: no-cache\n");
    printf("Content-type: application/json\n\n");
    
    time_t now;
    time(&now);
    char* date = ctime(&now);
    date[strlen(date)-1] = '\0';
    
    char* address = getenv("REMOTE_ADDR");
    
    char* escaped_title = escape_json_string("Hello, C!");
    char* escaped_heading = escape_json_string("Hello, C!");
    char* escaped_message = escape_json_string("This page was generated with the C programming language");
    char* escaped_date = escape_json_string(date);
    char* escaped_address = escape_json_string(address ? address : "");
    
    printf("{\n");
    printf("  \"title\": \"%s\",\n", escaped_title);
    printf("  \"heading\": \"%s\",\n", escaped_heading);
    printf("  \"message\": \"%s\",\n", escaped_message);
    printf("  \"time\": \"%s\",\n", escaped_date);
    printf("  \"IP\": \"%s\"\n", escaped_address);
    printf("}\n");
    
    free(escaped_title);
    free(escaped_heading);
    free(escaped_message);
    free(escaped_date);
    free(escaped_address);
    
    return 0;
}
