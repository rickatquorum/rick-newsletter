/**
 * Edition theming for What We're Building.
 * Tokens pulled from the live brand sites:
 *   Quorum DMS  — https://quorumdms.com/     (default, no ?edition=)
 *   DealerMine  — https://dealerminecrm.com/ (?edition=DM)
 *   Autovance   — https://autovance.com/     (?edition=AV)
 *   Accessible Accessories — https://acc-acc.com/ (?edition=ACA)
 */
(function () {
  var THEMES = {
    QDMS: {
      name: 'Quorum DMS',
      logo: 'content/branding/logo-quorum-dms-white-01.svg',
      logoAlt: 'Quorum DMS',
      logoWidth: 210,
      headerBg: 'linear-gradient(180deg, #217ADC 0%, #0054A6 100%)',
      primary: '#0054A6',
      accent: '#05C3DE',
      navy: '#001329',
      pageBg: '#F0F3F6',
      wash: '#EBF4FF',
      border: '#D6E3F0',
      headerMuted: '#D6EBFF',
      chevron: '#7BA7C7',
      caption: '#7A93AB'
    },
    DM: {
      name: 'DealerMine',
      logo: 'content/branding/dm-w-logo-1.svg',
      logoAlt: 'DealerMine CRM',
      logoWidth: 240,
      headerBg: 'linear-gradient(180deg, #217ADC 0%, #0054A6 100%)',
      primary: '#0054A6',
      accent: '#05C3DE',
      navy: '#001329',
      pageBg: '#F0F3F6',
      wash: '#EBF4FF',
      border: '#D6E3F0',
      headerMuted: '#D6EBFF',
      chevron: '#7BA7C7',
      caption: '#7A93AB'
    },
    AV: {
      name: 'Autovance',
      logo: 'content/branding/AV-logo-white-01.svg',
      logoAlt: 'Autovance',
      logoWidth: 200,
      headerBg: 'linear-gradient(180deg, #CC2027 0%, #B50E14 100%)',
      primary: '#CC2027',
      accent: '#CC2027',
      navy: '#001329',
      pageBg: '#F4F2F2',
      wash: '#F8EEEE',
      border: '#E8D0D1',
      headerMuted: '#F8D6D8',
      chevron: '#C9898C',
      caption: '#9A7A7B'
    },
    ACA: {
      name: 'Accessible Accessories',
      logo: 'content/branding/ACA-logo-white-01.svg',
      logoAlt: 'Accessible Accessories',
      logoWidth: 250,
      headerBg: 'linear-gradient(180deg, #CC2027 0%, #B50E14 100%)',
      primary: '#CC2027',
      accent: '#CC2027',
      navy: '#001329',
      pageBg: '#F4F2F2',
      wash: '#F8EEEE',
      border: '#E8D0D1',
      headerMuted: '#F8D6D8',
      chevron: '#C9898C',
      caption: '#9A7A7B'
    }
  };

  function editionFromQuery() {
    var params = new URLSearchParams(location.search);
    var edition = (params.get('edition') || 'QDMS').toUpperCase();
    if (edition === 'ACC') edition = 'ACA';
    if (THEMES[edition]) return edition;
    return 'QDMS';
  }

  function injectFonts() {
    if (document.getElementById('newsletter-theme-fonts')) return;
    var pre = document.createElement('link');
    pre.rel = 'preconnect';
    pre.href = 'https://fonts.googleapis.com';
    document.head.appendChild(pre);

    var link = document.createElement('link');
    link.id = 'newsletter-theme-fonts';
    link.rel = 'stylesheet';
    link.href = 'https://fonts.googleapis.com/css2?family=Catamaran:wght@600;700;800&display=swap';
    document.head.appendChild(link);

    var style = document.createElement('style');
    style.textContent =
      'h1,h2,h3{font-family:Catamaran,Arial,Helvetica,sans-serif!important;font-weight:700;}' +
      '.issue-card:hover{border-color:var(--brand-primary)!important;}';
    document.head.appendChild(style);
  }

  function colorMap(theme) {
    return {
      '#1c3556': theme.navy,
      '#1e3b5c': theme.primary,
      '#2c6fad': theme.primary,
      '#1769aa': theme.primary,
      '#7aaccc': theme.headerMuted,
      '#5a8fb0': theme.caption,
      '#7ba7c7': theme.chevron,
      '#7a93ab': theme.caption,
      '#9cc2dc': theme.headerMuted,
      '#d6e6f3': theme.headerMuted,
      '#edf0f4': theme.pageBg,
      '#f3f6fa': theme.wash,
      '#f5f8fb': theme.wash,
      '#e8f0f7': theme.wash,
      '#d8e1eb': theme.border,
      '#d8e4ee': theme.border,
      '#e4e9ee': theme.border,
      '#c2d8eb': theme.border,
      '#0054a6': theme.primary,
      '#001329': theme.navy,
      '#05c3de': theme.accent,
      '#ebf4ff': theme.wash,
      '#f0f3f6': theme.pageBg,
      '#217adc': theme.primary,
      '#cc2027': theme.primary,
      '#b50e14': theme.primary
    };
  }

  function replaceColors(css, map) {
    var next = css;
    Object.keys(map).forEach(function (from) {
      var re = new RegExp(from.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'), 'gi');
      next = next.replace(re, map[from]);
    });
    next = next.replace(/border-radius:\s*3px/g, 'border-radius:12px');
    return next;
  }

  function applyInlineColors(theme) {
    var map = colorMap(theme);
    var nodes = document.querySelectorAll('[style]');
    for (var i = 0; i < nodes.length; i++) {
      var el = nodes[i];
      var s = el.getAttribute('style');
      if (!s) continue;
      var next = replaceColors(s, map);
      if (next !== s) el.setAttribute('style', next);
    }

    var bgNodes = document.querySelectorAll('[bgcolor]');
    for (var j = 0; j < bgNodes.length; j++) {
      var node = bgNodes[j];
      var bg = (node.getAttribute('bgcolor') || '').toLowerCase();
      if (map[bg]) node.setAttribute('bgcolor', map[bg]);
    }
  }

  function applyChrome(theme) {
    document.body.setAttribute('data-edition', theme.name);

    var root = document.documentElement;
    root.style.setProperty('--brand-primary', theme.primary);
    root.style.setProperty('--brand-navy', theme.navy);
    root.style.setProperty('--brand-page-bg', theme.pageBg);
    root.style.setProperty('--brand-wash', theme.wash);
    root.style.setProperty('--brand-border', theme.border);
    root.style.setProperty('--brand-header-muted', theme.headerMuted);
    root.style.setProperty('--brand-caption', theme.caption);
    root.style.setProperty('--brand-accent', theme.accent);
    root.style.setProperty('--brand-logo-width', theme.logoWidth + 'px');

    var header = document.getElementById('header-brand');
    if (header) {
      header.style.backgroundColor = theme.primary;
      header.style.backgroundImage = theme.headerBg;
    }

    var logo = document.getElementById('header-logo');
    if (logo) {
      logo.src = theme.logo;
      logo.alt = theme.logoAlt;
      logo.width = theme.logoWidth;
      logo.style.maxWidth = theme.logoWidth + 'px';
    }

    var muted = document.getElementById('header-subtitle');
    if (muted) muted.style.color = theme.headerMuted;
  }

  function preserveEditionOnLinks(edition) {
    if (!new URLSearchParams(location.search).get('edition')) return;
    var links = document.querySelectorAll('a[href]');
    for (var i = 0; i < links.length; i++) {
      var href = links[i].getAttribute('href');
      if (!href) continue;
      var file = href.split('?')[0].split('#')[0];
      var name = file.split('/').pop();
      if (name !== 'index.html' && name.indexOf('newsletter-') !== 0) continue;
      var hash = href.indexOf('#') >= 0 ? href.slice(href.indexOf('#')) : '';
      var query = href.indexOf('?') >= 0 ? href.slice(href.indexOf('?') + 1).split('#')[0] : '';
      var params = new URLSearchParams(query);
      params.set('edition', edition);
      links[i].setAttribute('href', name + '?' + params.toString() + hash);
    }
  }

  function apply(edition) {
    var theme = THEMES[edition] || THEMES.QDMS;
    injectFonts();
    applyInlineColors(theme);
    applyChrome(theme);
    preserveEditionOnLinks(edition);
    if (document.querySelector('.issue-card')) {
      document.title = "What We're Building | " + theme.name;
    }
    return theme;
  }

  var edition = editionFromQuery();
  apply(edition);
}());
