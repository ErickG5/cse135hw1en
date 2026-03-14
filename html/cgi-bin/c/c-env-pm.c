#include <stdio.h>
#include <stdlib.h>

extern char **environ;

int compare(const void *a, const void *b) {
    return strcmp(*(const char **)a, *(const char **)b);
}

int main() {
    printf("Cache-Control: no-cache\n");
    printf("Content-Type: text/html\n\n");
    
    printf("<!DOCTYPE html>\n");
    printf("<html>\n");
    printf("<head>\n");
    printf("<title>Environment Variables</title>\n");
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
    printf("<h1 align='center'>Environment Variables</h1><hr />\n");
    
    int count = 0;
    while (environ[count] != NULL) {
        count++;
    }
    
    char **sorted_env = malloc(count * sizeof(char *));
    for (int i = 0; i < count; i++) {
        sorted_env[i] = environ[i];
    }
    
    qsort(sorted_env, count, sizeof(char *), compare);
    
    for (int i = 0; i < count; i++) {
        printf("%s<br />\n", sorted_env[i]);
    }
    
    free(sorted_env);
    
    printf("</body>\n");
    printf("</html>\n");
    
    return 0;
}