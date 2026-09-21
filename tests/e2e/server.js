const http = require('http');
const fs = require('fs');
const path = require('path');

const root = path.resolve(__dirname, '../..');

const page = `<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><title>2IZI Guard E2E</title></head>
<body>
<form id="f" data-guard-action="contact">
  <input name="x" value="test">
  <button type="submit">Send</button>
</form>
<script src="/guard/public/assets/guard.js"
        data-guard-base="/guard/public"
        data-guard-worker="/e2e-worker.js"
        data-guard-isolation="shadow"></script>
<script>
window.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('f');
  form.addEventListener('submit', event => {
    if (form.dataset.guardSubmitting === '1') {
      event.preventDefault();
      document.body.dataset.e2e = 'passed';
      document.body.dataset.token = form.querySelector('[name="guard_token"]')?.value || '';
    }
  });
  setTimeout(() => form.requestSubmit(), 50);
});
</script>
</body>
</html>`;

function json(res, value) {
  res.statusCode = 200;
  res.setHeader('content-type', 'application/json; charset=utf-8');
  res.end(JSON.stringify(value));
}

const e2eWorker = `self.onmessage = event => {
  self.postMessage({ok:true, nonce:'0', elapsedMs:1});
};`;

const server = http.createServer((req, res) => {
  console.log(req.method, req.url);

  if (req.url === '/') {
    res.setHeader('content-type', 'text/html; charset=utf-8');
    return res.end(page);
  }

  if (req.url === '/e2e-worker.js') {
    res.setHeader('content-type', 'application/javascript; charset=utf-8');
    return res.end(e2eWorker);
  }

  if (req.url.startsWith('/guard/public/assets/')) {
    const rel = req.url.replace('/guard/public/', '');
    const file = path.join(root, 'public', rel);
    if (!file.startsWith(path.join(root, 'public')) || !fs.existsSync(file)) {
      res.statusCode = 404;
      return res.end('not found');
    }
    const type = req.url.endsWith('.js')
      ? 'application/javascript; charset=utf-8'
      : 'text/css; charset=utf-8';
    res.setHeader('content-type', type);
    return fs.createReadStream(file).pipe(res);
  }

  if (req.url === '/guard/public/challenge.php') {
    return json(res, {
      status: 'challenge',
      challenge_id: 'ch_e2e',
      type: 'pow',
      expires_in: 60,
      ui: { working: 'Checking', verified: 'Verified' },
      parameters: {
        salt: 'salt',
        difficulty: 1,
        honeypot_name: 'g_hp_e2e'
      }
    });
  }

  if (req.url === '/guard/public/verify.php') {
    return json(res, {
      success: true,
      token: 'gt_e2e_token',
      expires_in: 120,
      ui: { verified: 'Verified' }
    });
  }

  res.statusCode = 404;
  res.end('not found');
});

server.listen(18765, '127.0.0.1', () => console.log('ready'));
