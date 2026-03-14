#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <time.h>

int main() {
    printf("Cache-Control: no-cache\n");
    printf("Content-type: text/html \n\n");
    
    printf("<!DOCTYPE html>\n");
    printf("<html><head><title>General Request Echo</title>\n");
        
    printf("<hr>\n");
    
/* --- Required identity fields --- */

char *hostname = getenv("SERVER_NAME");
char *user_agent = getenv("HTTP_USER_AGENT");

/* Client IP: prefer forwarded header */
char *client_ip = getenv("HTTP_X_FORWARDED_FOR");
if (client_ip && strchr(client_ip, ',')) {
    client_ip = strtok(client_ip, ",");  // take first IP
}
if (!client_ip || strlen(client_ip) == 0) {
    client_ip = getenv("HTTP_X_REAL_IP");
}
if (!client_ip || strlen(client_ip) == 0) {
    client_ip = getenv("REMOTE_ADDR");
}

/* Date + time */
time_t now = time(NULL);
char time_buf[128];
strftime(time_buf, sizeof(time_buf), "%Y-%m-%d %H:%M:%S %Z", localtime(&now));

printf("<p><b>Hostname:</b> %s</p>\n", hostname ? hostname : "(unknown)");
printf("<p><b>Date/Time:</b> %s</p>\n", time_buf);
printf("<p><b>User-Agent:</b> %s</p>\n", user_agent ? user_agent : "(none)");
printf("<p><b>Client IP:</b> %s</p>\n", client_ip ? client_ip : "(unknown)");

printf("<hr>\n");

/* --- Echo what was received --- */

char *query_string = getenv("QUERY_STRING");
printf("<p><b>Received Query String:</b> %s</p>\n",
       query_string ? query_string : "");

char *content_length_str = getenv("CONTENT_LENGTH");
if (content_length_str) {
    int content_length = atoi(content_length_str);
    if (content_length > 0) {
        char *form_data = malloc(content_length + 1);
        if (form_data) {
		size_t bytes_read = fread(form_data, 1, content_length, stdin);
		form_data[bytes_read] = '\0';

            printf("<p><b>Received Message Body:</b></p><pre>%s</pre>\n", form_data);
            free(form_data);
        } else {
            printf("<p><b>Received Message Body:</b> (memory error)</p>\n");
        }
    } else {
        printf("<p><b>Received Message Body:</b> </p>\n");
    }
} else {
    printf("<p><b>Received Message Body:</b> </p>\n");
}

printf("</body></html>\n");

}
