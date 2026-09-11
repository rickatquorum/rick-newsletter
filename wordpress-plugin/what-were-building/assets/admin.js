(function () {
  var cfg = window.QNLAdmin || {};
  var sourceInput = document.getElementById('qnl-source-url');
  var loadBtn = document.getElementById('qnl-load-issues');
  var issueSelect = document.getElementById('qnl-issue');
  var editionSelect = document.getElementById('qnl-edition');
  var titleInput = document.getElementById('qnl-page-title');
  var importBtn = document.getElementById('qnl-import');
  var connectStatus = document.getElementById('qnl-connect-status');
  var importStatus = document.getElementById('qnl-import-status');
  var resultBox = document.getElementById('qnl-result');
  var issues = [];
  var titleTouched = false;

  function post(action, nonce, extra) {
    var body = new URLSearchParams();
    body.set('action', action);
    body.set('nonce', nonce);
    Object.keys(extra || {}).forEach(function (key) {
      body.set(key, extra[key]);
    });
    return fetch(cfg.ajaxUrl, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString()
    }).then(function (res) {
      return res.json();
    });
  }

  function setStatus(el, message, kind) {
    if (!el) return;
    el.hidden = !message;
    el.textContent = message || '';
    el.classList.remove('is-error', 'is-ok');
    if (kind) el.classList.add(kind);
  }

  function selectedIssue() {
    var file = issueSelect.value;
    for (var i = 0; i < issues.length; i++) {
      if (issues[i].file === file) return issues[i];
    }
    return null;
  }

  function monthFromTitle(title) {
    if (!title) return '';
    return title.split('·')[0].replace(/\s+/g, ' ').trim();
  }

  function suggestedTitle() {
    var issue = selectedIssue();
    var editionCode = editionSelect.value;
    var editionName = (cfg.editions && cfg.editions[editionCode]) || editionCode;
    var month = issue ? monthFromTitle(issue.title) : '';
    if (month && editionName) {
      return "What We're Building — " + month + ' — ' + editionName;
    }
    if (editionName) {
      return "What We're Building — " + editionName;
    }
    return "What We're Building";
  }

  function maybeFillTitle() {
    if (!titleTouched) {
      titleInput.value = suggestedTitle();
    }
  }

  function enableImport(enabled) {
    issueSelect.disabled = !enabled;
    editionSelect.disabled = !enabled;
    titleInput.disabled = !enabled;
    importBtn.disabled = !enabled;
  }

  function fillIssues(list) {
    issues = list || [];
    issueSelect.innerHTML = '';
    var placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = cfg.i18n.choose;
    issueSelect.appendChild(placeholder);
    issues.forEach(function (issue) {
      var opt = document.createElement('option');
      opt.value = issue.file;
      opt.textContent = issue.title || issue.file;
      issueSelect.appendChild(opt);
    });
    if (issues.length) {
      issueSelect.value = issues[0].file;
    }
    enableImport(issues.length > 0);
    maybeFillTitle();
  }

  loadBtn.addEventListener('click', function () {
    loadBtn.disabled = true;
    setStatus(connectStatus, cfg.i18n.loading, '');
    post('qnl_fetch_issues', cfg.fetchNonce, { source_url: sourceInput.value })
      .then(function (json) {
        if (!json || !json.success) {
          throw new Error((json && json.data && json.data.message) || cfg.i18n.error);
        }
        if (json.data.source) sourceInput.value = json.data.source;
        if (json.data.editions) cfg.editions = json.data.editions;
        fillIssues(json.data.issues || []);
        var count = (json.data.issues || []).length;
        setStatus(connectStatus, 'Connected. Found ' + count + ' issue' + (count === 1 ? '' : 's') + '.', 'is-ok');
      })
      .catch(function (err) {
        enableImport(false);
        setStatus(connectStatus, err.message || cfg.i18n.error, 'is-error');
      })
      .then(function () {
        loadBtn.disabled = false;
      });
  });

  issueSelect.addEventListener('change', maybeFillTitle);
  editionSelect.addEventListener('change', maybeFillTitle);
  titleInput.addEventListener('input', function () {
    titleTouched = titleInput.value.trim() !== '';
  });

  importBtn.addEventListener('click', function () {
    if (!issueSelect.value) {
      setStatus(importStatus, 'Please choose a newsletter.', 'is-error');
      return;
    }
    if (!titleInput.value.trim()) {
      setStatus(importStatus, 'Please enter a name for the page.', 'is-error');
      return;
    }

    importBtn.disabled = true;
    resultBox.hidden = true;
    setStatus(importStatus, cfg.i18n.importing, '');

    post('qnl_import', cfg.importNonce, {
      source_url: sourceInput.value,
      issue_file: issueSelect.value,
      edition: editionSelect.value,
      page_title: titleInput.value
    })
      .then(function (json) {
        if (!json || !json.success) {
          throw new Error((json && json.data && json.data.message) || cfg.i18n.error);
        }
        var data = json.data;
        var warning = '';
        if (data.failed && data.failed.length) {
          warning = '<p>Saved with ' + data.failed.length + ' missing asset' + (data.failed.length === 1 ? '' : 's') + '.</p>';
        }
        function escapeHtml(value) {
          return String(value).replace(/[&<>"']/g, function (char) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
          });
        }

        resultBox.innerHTML =
          '<p><strong>Landing page created.</strong> Downloaded ' + data.assets + ' asset' + (data.assets === 1 ? '' : 's') + ' from GitHub Pages.</p>' +
          warning +
          (data.permalink ? '<p><a href="' + escapeHtml(data.permalink) + '" target="_blank" rel="noopener">View page</a>' : '') +
          (data.edit_link ? '<a href="' + escapeHtml(data.edit_link) + '">Edit page</a></p>' : '</p>');
        resultBox.hidden = false;
        setStatus(importStatus, '', '');
      })
      .catch(function (err) {
        setStatus(importStatus, err.message || cfg.i18n.error, 'is-error');
      })
      .then(function () {
        importBtn.disabled = false;
      });
  });
})();
