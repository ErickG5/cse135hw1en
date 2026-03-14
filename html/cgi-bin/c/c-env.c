#include <stdio.h>
#include <stdlib.h>
#include <string.h>

extern char **environ;

int compare(const void *a, const void *b) {
    return strcmp(*(const char **)a, *(const char **)b);
}

int main() {
    printf("Cache-Control: no-cache\n");
    printf("Content-type: text/html \n\n");
    
    printf("<!DOCTYPE html>\n");
    printf("<html><head><title>Environment Variables</title>\n");

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
    printf("</head><body><h1 align=\"center\">Environment Variables</h1>\n");
    printf("<hr>\n");
    
    int count = 0;
    char **env_copy = NULL;
    
    for (int i = 0; environ[i] != NULL; i++) {
        count++;
    }
    
    env_copy = malloc(count * sizeof(char *));
    for (int i = 0; i < count; i++) {
        env_copy[i] = environ[i];
    }
    
    qsort(env_copy, count, sizeof(char *), compare);
    
    for (int i = 0; i < count; i++) {
        char *env = env_copy[i];
        char *eq = strchr(env, '=');
        if (eq) {
            *eq = '\0';
            printf("<b>%s:</b> %s<br />\n", env, eq + 1);
            *eq = '=';
        } else {
            printf("<b>%s:</b> <br />\n", env);
        }
    }
    
    free(env_copy);
    
    printf("</body></html>\n");
    
    return 0;
}
