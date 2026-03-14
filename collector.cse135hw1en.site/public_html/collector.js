(function() {
  'use strict';


  // =========================================================================
  // CONFIGURATION
  // =========================================================================

  const DEFAULTS = {
    endpoint: '',
    enableVitals: true,
    enableErrors: true,
    enableTechnographics: true,
    enableActivity: true,
    sampleRate: 1.0,
    debug: false,
    respectConsent: false,
    detectBots: false
  };

  let config = { ...DEFAULTS };
  let initialized = false;
  let blocked = false;

  const customData = {};
  let userId = null;
  const plugins = [];

  const reportedErrors = new Set();
  let errorCount = 0;

  const vitals = {
    lcp: null,
    cls: 0,
    inp: null
  };

  let pageShowTime = Date.now();
  let totalVisibleTime = 0;

  // =========================================================================
  // ACTIVITY BUFFER (batch + flush)
  // =========================================================================

  let activityBuffer = [];
  let activityFlushTimer = null;

  function bufferActivity(evt) {
    activityBuffer.push(evt);
    // cap buffer to prevent runaway memory
    if (activityBuffer.length > 500) activityBuffer.shift();
  }

  function flushActivity(reason = 'interval') {
    if (!activityBuffer.length) return;

    const sid = getSessionId();
    const batch = activityBuffer.splice(0, activityBuffer.length);

    send({
      type: 'activity_batch',
      reason,
      events: batch,
      timestamp: new Date().toISOString(),
      url: window.location.href,
      session_id: sid,
      session: sid
    });
  }

  // =========================================================================
  // UTILITIES
  // =========================================================================

  function round(n) {
    return Math.round(n * 100) / 100;
  }

  function throttle(fn, ms) {
    let last = 0;
    return function(...args) {
      const now = Date.now();
      if (now - last >= ms) {
        last = now;
        fn.apply(this, args);
      }
    };
  }

  // =========================================================================
  // CONSENT MANAGEMENT
  // =========================================================================

  function hasConsent() {
    if (navigator.globalPrivacyControl) {
      return false;
    }

    const cookies = document.cookie.split(';');
    for (const c of cookies) {
      const cookie = c.trim();
      if (cookie.indexOf('analytics_consent=') === 0) {
        return cookie.split('=')[1] === 'true';
      }
    }

    return false;
  }

  // =========================================================================
  // BOT DETECTION
  // =========================================================================

  function isBot() {
    if (navigator.webdriver) return true;

    const ua = navigator.userAgent;
    if (/HeadlessChrome|PhantomJS|Lighthouse/i.test(ua)) return true;

    if (/Chrome/.test(ua) && !window.chrome) return true;

    if (window._phantom || window.__nightmare || window.callPhantom) return true;

    return false;
  }

  // =========================================================================
  // SAMPLING
  // =========================================================================

  function shouldSample() {
    if (config.sampleRate >= 1) return true;
    if (config.sampleRate <= 0) return false;

    const storageKey = '_collector_sample';
    let sample = sessionStorage.getItem(storageKey);

    if (sample === null) {
      sample = Math.random();
      sessionStorage.setItem(storageKey, String(sample));
    } else {
      sample = parseFloat(sample);
    }

    return sample < config.sampleRate;
  }


  // =========================================================================
  // SESSION IDENTITY
  // =========================================================================

  function generateSessionId() {
    return Math.random().toString(36).substring(2) + Date.now().toString(36);
  }

  function getCookie(name) {
    const cookies = document.cookie.split(';');
    for (const c of cookies) {
      const cookie = c.trim();
      if (cookie.indexOf(name + '=') === 0) {
        return cookie.substring(name.length + 1);
      }
    }
    return null;
  }

  function getSessionId() {
    let sid = getCookie('_collector_sid');

    if (!sid) {
      sid = generateSessionId();

      // Session cookie: no expires/max-age, so it ends with the browser session
      document.cookie = `_collector_sid=${sid}; path=/; SameSite=Lax`;
    }

    return sid;
  }


  // =========================================================================
  // TECHNOGRAPHICS
  // =========================================================================

  function getNetworkInfo() {
    if (!('connection' in navigator)) return {};
    const conn = navigator.connection;
    return {
      effectiveType: conn.effectiveType,
      downlink: conn.downlink,
      rtt: conn.rtt,
      saveData: conn.saveData
    };
  }

  function checkImagesEnabled() {
    return new Promise((resolve) => {
      const img = new Image();
      img.onload = () => resolve(true);
      img.onerror = () => resolve(false);
      img.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';
    });
  }

  function checkCSSEnabled() {
    // If body isn't ready yet, don't crash.
    // We'll treat as "unknown/assume true" and capability check will run again later anyway.
    if (!document.body) return true;

    const test = document.createElement('div');
    test.style.position = 'absolute';
    test.style.left = '-9999px';
    document.body.appendChild(test);

    const computed = window.getComputedStyle(test).position;

    document.body.removeChild(test);
    return computed === 'absolute';
  }

  function getTechnographics() {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      cookiesEnabled: navigator.cookieEnabled,
      viewportWidth: window.innerWidth,
      viewportHeight: window.innerHeight,
      screenWidth: window.screen.width,
      screenHeight: window.screen.height,
      pixelRatio: window.devicePixelRatio,
      cores: navigator.hardwareConcurrency || 0,
      memory: navigator.deviceMemory || 0,
      network: getNetworkInfo(),
      colorScheme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      javascriptEnabled: true,
      cssEnabled: checkCSSEnabled()
    };
  }

  // =========================================================================
  // PERFORMANCE TIMING
  // =========================================================================

  function getNavigationTiming() {
    const entries = performance.getEntriesByType('navigation');
    if (!entries.length) return {};
    const n = entries[0];

    const pageStart = n.startTime ?? 0;
    const pageEnd = n.loadEventEnd ?? 0;
    const totalLoadMs = (pageEnd && pageStart) ? round(pageEnd - pageStart) : null;

    return {
      pageStart: round(pageStart),
      pageEnd: round(pageEnd),
      totalLoadMs: totalLoadMs,

      dnsLookup: round(n.domainLookupEnd - n.domainLookupStart),
      tcpConnect: round(n.connectEnd - n.connectStart),
      tlsHandshake: n.secureConnectionStart > 0 ? round(n.connectEnd - n.secureConnectionStart) : 0,
      ttfb: round(n.responseStart - n.requestStart),
      download: round(n.responseEnd - n.responseStart),
      domInteractive: round(n.domInteractive - n.fetchStart),
      domComplete: round(n.domComplete - n.fetchStart),
      loadEvent: round(n.loadEventEnd - n.fetchStart),
      fetchTime: round(n.responseEnd - n.fetchStart),
      transferSize: n.transferSize,
      headerSize: n.transferSize - n.encodedBodySize,

      raw: {
        startTime: round(n.startTime),
        fetchStart: round(n.fetchStart),
        requestStart: round(n.requestStart),
        responseStart: round(n.responseStart),
        responseEnd: round(n.responseEnd),
        domInteractive: round(n.domInteractive),
        domComplete: round(n.domComplete),
        loadEventEnd: round(n.loadEventEnd)
      }
    };
  }

  // =========================================================================
  // RESOURCE TIMING
  // =========================================================================

  function getResourceSummary() {
    const resources = performance.getEntriesByType('resource');

    const summary = {
      script:         { count: 0, totalSize: 0, totalDuration: 0 },
      link:           { count: 0, totalSize: 0, totalDuration: 0 },
      img:            { count: 0, totalSize: 0, totalDuration: 0 },
      font:           { count: 0, totalSize: 0, totalDuration: 0 },
      fetch:          { count: 0, totalSize: 0, totalDuration: 0 },
      xmlhttprequest: { count: 0, totalSize: 0, totalDuration: 0 },
      other:          { count: 0, totalSize: 0, totalDuration: 0 }
    };

    resources.forEach((r) => {
      const type = summary[r.initiatorType] ? r.initiatorType : 'other';
      summary[type].count++;
      summary[type].totalSize += r.transferSize || 0;
      summary[type].totalDuration += r.duration || 0;
    });

    return {
      totalResources: resources.length,
      byType: summary
    };
  }

  // =========================================================================
  // WEB VITALS
  // =========================================================================

  function getVitals() {
    return {
      lcp: vitals.lcp,
      cls: vitals.cls,
      inp: vitals.inp
    };
  }

  // =========================================================================
  // ERROR TRACKING
  // =========================================================================

  function reportError(errorData) {
    if (errorCount >= 10) return;

    const key = `${errorData.type}:${errorData.message || ''}:${errorData.source || ''}:${errorData.line || ''}`;
    if (reportedErrors.has(key)) return;

    reportedErrors.add(key);
    errorCount++;

    const sid = getSessionId();

    send({
      type: 'error',
      error: errorData,
      timestamp: new Date().toISOString(),
      url: window.location.href,
      session_id: sid,
      session: sid
    });

    window.dispatchEvent(new CustomEvent('collector:error', {
      detail: { errorData, count: errorCount }
    }));
  }

  // =========================================================================
  // RETRY QUEUE
  // =========================================================================

  function queueForRetry(payload) {
    try {
      const queue = JSON.parse(sessionStorage.getItem('_collector_retry') || '[]');
      if (queue.length >= 50) return;
      queue.push(payload);
      sessionStorage.setItem('_collector_retry', JSON.stringify(queue));
    } catch (e) {}
  }

  function processRetryQueue() {
    try {
      const queue = JSON.parse(sessionStorage.getItem('_collector_retry') || '[]');
      if (!queue.length) return;

      sessionStorage.removeItem('_collector_retry');
      queue.forEach(payload => send(payload));
    } catch (e) {}
  }

  // =========================================================================
  // PAYLOAD DELIVERY
  // =========================================================================

  function send(payload) {
    const canMark = typeof performance.mark === 'function';
    if (canMark) performance.mark('collector_send_start');

    if (config.debug) {
      console.log('[Collector] Debug payload:', payload);
      return;
    }

    if (!config.endpoint) {
      console.warn('[Collector] No endpoint configured');
      return;
    }

    const json = JSON.stringify(payload);
    let sent = false;

    if (navigator.sendBeacon) {
      sent = navigator.sendBeacon(
        config.endpoint,
        new Blob([json], { type: 'application/json' })
      );
    }

    if (!sent) {
      fetch(config.endpoint, {
        method: 'POST',
        body: json,
        headers: { 'Content-Type': 'application/json' },
        keepalive: true,
        credentials: 'include'
      }).catch(() => queueForRetry(payload));
    }

    if (canMark) {
      performance.mark('collector_send_end');
      performance.measure('collector_send', 'collector_send_start', 'collector_send_end');
    }

    window.dispatchEvent(new CustomEvent('collector:beacon', { detail: payload }));
  }

  // =========================================================================
  // BUILD PAYLOAD
  // =========================================================================

  function buildPayload(type = 'pageview') {
    const sid = getSessionId();

    let payload = {
      type: type,
      url: window.location.href,
      title: document.title,
      referrer: document.referrer,
      timestamp: new Date().toISOString(),

      session_id: sid,
      session: sid,

      technographics: getTechnographics(),
      timing: getNavigationTiming(),
      resources: getResourceSummary(),
      vitals: getVitals(),
      errorCount: errorCount,
      customData: customData
    };

    if (userId) payload.userId = userId;

    plugins.forEach(plugin => {
      if (typeof plugin.beforeSend === 'function') {
        const result = plugin.beforeSend(payload);
        if (result === false) return;
        if (result && typeof result === 'object') payload = result;
      }
    });

    return payload;
  }

  function collect() {
    const payload = buildPayload('pageview');
    send(payload);
    window.dispatchEvent(new CustomEvent('collector:payload', { detail: payload }));
  }

  // =========================================================================
  // INITIALIZE VITALS OBSERVERS
  // =========================================================================

  function initVitalsObservers() {
    try {
      new PerformanceObserver((list) => {
        const entries = list.getEntries();
        if (entries.length) vitals.lcp = round(entries[entries.length - 1].startTime);
      }).observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) {}

    try {
      new PerformanceObserver((list) => {
        list.getEntries().forEach(entry => {
          if (!entry.hadRecentInput) vitals.cls = round(vitals.cls + entry.value);
        });
      }).observe({ type: 'layout-shift', buffered: true });
    } catch (e) {}

    try {
      new PerformanceObserver((list) => {
        list.getEntries().forEach(entry => {
          if (vitals.inp === null || entry.duration > vitals.inp) vitals.inp = round(entry.duration);
        });
      }).observe({ type: 'event', buffered: true, durationThreshold: 16 });
    } catch (e) {}
  }

  // =========================================================================
  // INITIALIZE ERROR TRACKING
  // =========================================================================

  function initErrorTracking() {
    window.addEventListener('error', (event) => {
      if (event instanceof ErrorEvent) {
        reportError({
          type: 'js-error',
          message: event.message,
          source: event.filename,
          line: event.lineno,
          column: event.colno,
          stack: event.error ? event.error.stack : '',
          url: window.location.href
        });
      } else {
        const target = event.target;
        if (!target) return;

        const tagName = target.tagName;
        if (tagName === 'IMG' || tagName === 'SCRIPT' || tagName === 'LINK') {
          reportError({
            type: 'resource-error',
            tagName: tagName,
            src: target.src || target.href || '',
            url: window.location.href
          });
        }
      }
    }, true);

    window.addEventListener('unhandledrejection', (event) => {
      const reason = event.reason;
      reportError({
        type: 'promise-rejection',
        message: reason instanceof Error ? reason.message : String(reason),
        stack: reason instanceof Error ? reason.stack : '',
        url: window.location.href
      });
    });
  }

  // =========================================================================
  // TIME-ON-PAGE TRACKING
  // =========================================================================

  function initTimeTracking() {
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        totalVisibleTime += Date.now() - pageShowTime;

        const sid = getSessionId();

        const payload = {
          type: 'page_exit',
          url: window.location.href,
          timeOnPage: totalVisibleTime,
          vitals: getVitals(),
          errorCount: errorCount,
          timestamp: new Date().toISOString(),
          session_id: sid,
          session: sid
        };

        send(payload);
      } else {
        pageShowTime = Date.now();
      }
    });
  }

  // =========================================================================
  // ACTIVITY TRACKING (REQUIRED FOR HW) — batched every 5 seconds
  // =========================================================================

  function initActivityTracking() {
    // Track "enter page" (immediate)
    const sid = getSessionId();
    send({
      type: 'activity',
      activityType: 'page_enter',
      timestamp: new Date().toISOString(),
      url: window.location.href,
      session_id: sid,
      session: sid
    });

    let idleTimer = null;
    let idleStart = null;

    function startIdleTimer() {
      clearTimeout(idleTimer);
      idleTimer = setTimeout(() => {
        if (idleStart !== null) return; // already idle
        idleStart = Date.now();
        bufferActivity({
          activityType: 'idle_start',
          timestamp: new Date().toISOString()
        });
      }, 2000);
    }

    function onAnyActivity() {
      // If we were idle, close idle window
      if (idleStart !== null) {
        const duration = Date.now() - idleStart;
        idleStart = null;
        bufferActivity({
          activityType: 'idle_end',
          durationMs: duration,
          timestamp: new Date().toISOString()
        });
      }
      startIdleTimer();
    }

    const sendMouseMove = throttle((e) => {
      onAnyActivity();
      bufferActivity({
        activityType: 'mousemove',
        x: e.clientX,
        y: e.clientY,
        timestamp: new Date().toISOString()
      });
    }, 250);

    const sendScroll = throttle(() => {
      onAnyActivity();
      bufferActivity({
        activityType: 'scroll',
        scrollX: window.scrollX,
        scrollY: window.scrollY,
        timestamp: new Date().toISOString()
      });
    }, 250);

    document.addEventListener('mousemove', sendMouseMove, { passive: true });

    document.addEventListener('click', (e) => {
      onAnyActivity();
      bufferActivity({
        activityType: 'click',
        button: e.button,
        x: e.clientX,
        y: e.clientY,
        timestamp: new Date().toISOString()
      });
    }, { passive: true });

    document.addEventListener('scroll', sendScroll, { passive: true });

    document.addEventListener('keydown', (e) => {
      onAnyActivity();
      bufferActivity({
        activityType: 'keydown',
        key: e.key,
        timestamp: new Date().toISOString()
      });
    });

    document.addEventListener('keyup', (e) => {
      onAnyActivity();
      bufferActivity({
        activityType: 'keyup',
        key: e.key,
        timestamp: new Date().toISOString()
      });
    });

    // Arm idle detection immediately
    startIdleTimer();

    // Flush batched activity every 5 seconds
    if (activityFlushTimer) clearInterval(activityFlushTimer);
    activityFlushTimer = setInterval(() => flushActivity('interval'), 5000);

    // Flush right before leaving / hiding
    window.addEventListener('beforeunload', () => {
      flushActivity('beforeunload');
      const s = getSessionId();
      send({
        type: 'activity',
        activityType: 'page_leave',
        timestamp: new Date().toISOString(),
        url: window.location.href,
        session_id: s,
        session: s
      });
    });

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') flushActivity('hidden');
    });
  }

  // =========================================================================
  // PUBLIC API
  // =========================================================================

  const publicAPI = {
    init: function(options) {
      if (initialized) {
        console.warn('[Collector] Already initialized');
        return;
      }

      if (options) Object.assign(config, options);

      if (config.respectConsent && !hasConsent()) {
        console.log('[Collector] No consent — collection disabled');
        blocked = true;
        initialized = true;
        return;
      }

      if (config.detectBots && isBot()) {
        console.log('[Collector] Bot detected — collection disabled');
        blocked = true;
        initialized = true;
        return;
      }

      if (!shouldSample()) {
        console.log(`[Collector] Session not sampled (rate: ${config.sampleRate})`);
        blocked = true;
        initialized = true;
        return;
      }

      initialized = true;
      console.log('[Collector] Initialized', config);

      if (config.enableVitals) initVitalsObservers();
      if (config.enableErrors) initErrorTracking();

      processRetryQueue();
      initTimeTracking();

      if (config.enableActivity) {
        initActivityTracking();
      }


      if (config.enableTechnographics) {
         setTimeout(() => {
           checkImagesEnabled().then(enabled => {
             customData.imagesEnabled = enabled;

             const payload = buildPayload('capability');
             payload.capabilities = {
               javascript: true,
               css: checkCSSEnabled(),
               images: enabled
             };

             send(payload);
           }).catch(err => {
             console.warn('[Collector] Capability check failed:', err);
           });
           }, 1000);
         }

         if (document.readyState === 'loading') {
           setTimeout(() => collect(), 0);
         } else {
           window.addEventListener('load',() => setTimeout(() => collect(), 0));
         }
       },

   track: function(eventName, data) {
      if (!initialized || blocked) return;

      const sid = getSessionId();

      const payload = {
        type: 'event',
        event: eventName,
        data: data || {},
        timestamp: new Date().toISOString(),
        url: window.location.href,
        session_id: sid,
        session: sid,
        customData: customData
      };

      if (userId) payload.userId = userId;
      send(payload);
    },

    set: function(key, value) {
      customData[key] = value;
    },

    identify: function(id) {
      userId = id;
    },

    use: function(plugin) {
      if (plugin && typeof plugin === 'object') {
        plugins.push(plugin);
        if (typeof plugin.init === 'function') {
          plugin.init({
            track: this.track.bind(this),
            set: this.set.bind(this),
            getConfig: () => config,
            getSessionId: getSessionId
          });
        }
        console.log(`[Collector] Plugin registered: ${plugin.name || '(unnamed)'}`);
      } else {
        console.warn('[Collector] Invalid plugin');
      }
    }
  };

  // =========================================================================
  // COMMAND QUEUE PROCESSING
  // =========================================================================

  function processCommandQueue() {
    const queue = window._cq || [];

    for (const args of queue) {
      const method = args[0];
      const params = args.slice(1);
      if (typeof publicAPI[method] === 'function') {
        publicAPI[method](...params);
      }
    }

    window._cq = {
      push: (args) => {
        const method = args[0];
        const params = args.slice(1);
        if (typeof publicAPI[method] === 'function') {
          publicAPI[method](...params);
        }
      }
    };
  }

  processCommandQueue();

  // =========================================================================
  // EXPOSE FOR TESTING
  // =========================================================================

  window.__collector = {
    getNavigationTiming: getNavigationTiming,
    getResourceSummary: getResourceSummary,
    getTechnographics: getTechnographics,
    getWebVitals: getVitals,
    getSessionId: getSessionId,
    getNetworkInfo: getNetworkInfo,
    reportError: reportError,
    collect: collect,
    hasConsent: hasConsent,
    isBot: isBot,
    isSampled: shouldSample,
    getErrorCount: () => errorCount,
    getConfig: () => config,
    isBlocked: () => blocked,
    api: publicAPI
  };

  window.collector = publicAPI;
})();
