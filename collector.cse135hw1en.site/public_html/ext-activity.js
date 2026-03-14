const ActivityTracker = {
  name: 'activity-tracker',
  
  _lastActivity: Date.now(),
  _idleThreshold: 2000,
  _idleStart: null,
  _maxScrollDepth: 0,
  _scrollReported: {},
  _scrollThresholds: [25, 50, 75, 100],
  
  init: function(api) {
    this.api = api;
    this._lastActivity = Date.now();
    this._startIdleDetection();
    
    // Mouse clicks (detailed)
    document.addEventListener('click', (e) => {
      this._recordClick(e);
    }, true); // Capture phase for detailed click tracking
    
    // Mouse movement (throttled)
    let moveTimeout;
    document.addEventListener('mousemove', (e) => {
      clearTimeout(moveTimeout);
      moveTimeout = setTimeout(() => {
        this._recordActivity('mousemove', { x: e.clientX, y: e.clientY });
      }, 100);
    });
    
    // Keyboard
    document.addEventListener('keydown', (e) => {
      this._recordActivity('keydown', { key: e.key, code: e.code });
    });
    
    // Scroll (with thresholds)
    let scrollTimeout;
    window.addEventListener('scroll', () => {
      clearTimeout(scrollTimeout);
      scrollTimeout = setTimeout(() => {
        this._measureScroll();
      }, 100);
    });
    
    // Final scroll on exit
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        this._reportFinalScroll();
      }
    });
  },
  
  _recordClick: function(e) {
    this._resetIdle();
    const target = e.target;
    
    this.api.track('click', {
      tagName: target.tagName,
      id: target.id || undefined,
      className: target.className || undefined,
      text: (target.textContent || '').substring(0, 100),
      x: e.clientX,
      y: e.clientY,
      button: e.button,
      selector: this._getSelector(target)
    });
  },
  
  _getSelector: function(el) {
    const parts = [];
    let element = el;
    while (element && element !== document.body) {
      let part = element.tagName.toLowerCase();
      if (element.id) {
        part += `#${element.id}`;
        parts.unshift(part);
        break;
      }
      if (element.className) {
        const classes = element.className.trim().split(/\s+/);
        if (classes.length) part += `.${classes.join('.')}`;
      }
      parts.unshift(part);
      element = element.parentElement;
    }
    return parts.join(' > ');
  },
  
  _measureScroll: function() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const docHeight = Math.max(
      document.documentElement.scrollHeight,
      document.body.scrollHeight
    );
    const winHeight = window.innerHeight;
    const percent = Math.min(100, Math.round((scrollTop + winHeight) / docHeight * 100));
    
    if (percent > this._maxScrollDepth) {
      this._maxScrollDepth = percent;
    }
    
    // Report raw scroll position
    this._recordActivity('scroll', { x: window.scrollX, y: window.scrollY, percent: percent });
    
    // Report thresholds
    for (const t of this._scrollThresholds) {
      if (percent >= t && !this._scrollReported[t]) {
        this._scrollReported[t] = true;
        this.api.track('scroll_depth', { threshold: t, maxDepth: this._maxScrollDepth });
      }
    }
  },
  
  _reportFinalScroll: function() {
    this.api.track('scroll_final', { maxDepth: this._maxScrollDepth });
  },
  
  _recordActivity: function(type, data) {
    this._resetIdle();
    this.api.track(type, data);
  },
  
  _startIdleDetection: function() {
    this._idleTimer = setInterval(() => {
      const now = Date.now();
      const idleTime = now - this._lastActivity;
      if (idleTime >= this._idleThreshold && !this._idleStart) {
        this._idleStart = now - idleTime;
      }
    }, 1000);
  },
  
  _resetIdle: function() {
    if (this._idleStart) {
      const idleDuration = Date.now() - this._idleStart;
      this.api.track('idle_end', {
        duration: idleDuration,
        endedAt: new Date().toISOString()
      });
      this._idleStart = null;
    }
    this._lastActivity = Date.now();
  },
  
  destroy: function() {
    if (this._idleTimer) clearInterval(this._idleTimer);
  }
};