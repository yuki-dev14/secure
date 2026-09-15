const http = require('http');
const { spawn } = require('child_process');

const WORKER_COUNT = 16;
const START_PORT = 8001;
const PORTS = [];
const workers = [];
const activeRequests = new Array(WORKER_COUNT).fill(0);

console.log(`Starting ${WORKER_COUNT} PHP worker instances with Least-Connections Routing...`);

for (let i = 0; i < WORKER_COUNT; i++) {
  const port = START_PORT + i;
  PORTS.push(port);
  const child = spawn('php', ['artisan', 'serve', `--port=${port}`], {
    cwd: process.cwd(),
    stdio: 'ignore'
  });
  workers.push(child);
}

process.on('exit', () => workers.forEach(w => w.kill()));
process.on('SIGINT', () => process.exit());
process.on('SIGTERM', () => process.exit());

// Select worker with the FEWEST active in-flight requests (availability tracking)
function getBestWorkerIndex() {
  let minIndex = 0;
  let minCount = activeRequests[0];
  for (let i = 1; i < WORKER_COUNT; i++) {
    if (activeRequests[i] < minCount) {
      minCount = activeRequests[i];
      minIndex = i;
    }
  }
  return minIndex;
}

const proxy = http.createServer((req, res) => {
  const workerIdx = getBestWorkerIndex();
  const targetPort = PORTS[workerIdx];

  activeRequests[workerIdx]++;

  let decremented = false;
  function releaseWorker() {
    if (!decremented) {
      decremented = true;
      activeRequests[workerIdx]--;
    }
  }

  const options = {
    hostname: '127.0.0.1',
    port: targetPort,
    path: req.url,
    method: req.method,
    headers: req.headers,
    timeout: 15000
  };

  const proxyReq = http.request(options, (proxyRes) => {
    if (!res.headersSent) {
      res.writeHead(proxyRes.statusCode, proxyRes.headers);
    }
    proxyRes.pipe(res, { end: true });
    proxyRes.on('end', releaseWorker);
    proxyRes.on('close', releaseWorker);
  });

  proxyReq.on('error', (err) => {
    releaseWorker();
    if (!res.headersSent) {
      res.writeHead(502, { 'Content-Type': 'text/plain' });
      res.end('Bad Gateway');
    } else {
      res.destroy();
    }
  });

  proxyReq.on('timeout', () => {
    releaseWorker();
    proxyReq.destroy();
    if (!res.headersSent) {
      res.writeHead(504, { 'Content-Type': 'text/plain' });
      res.end('Gateway Timeout');
    } else {
      res.destroy();
    }
  });

  req.pipe(proxyReq, { end: true });
});

setTimeout(() => {
  proxy.listen(8000, '127.0.0.1', () => {
    console.log(`Smart Availability-Tracking Proxy running on http://127.0.0.1:8000 (${WORKER_COUNT} PHP workers)`);
  });
}, 3000);
