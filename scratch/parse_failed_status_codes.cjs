const fs = require('fs');
const readline = require('readline');

async function parseStatusCodes(filename) {
  const fileStream = fs.createReadStream(filename);
  const rl = readline.createInterface({
    input: fileStream,
    crlfDelay: Infinity
  });

  const statusCounts = {};

  for await (const line of rl) {
    if (!line.trim()) continue;
    try {
      const obj = JSON.parse(line);
      if (obj.type === 'Point' && obj.metric === 'http_req_failed') {
        const status = obj.data.tags ? (obj.data.tags.status || '0 / Timeout') : 'Unknown';
        statusCounts[status] = (statusCounts[status] || 0) + 1;
      }
    } catch (e) {}
  }

  console.log("=== ACTUAL HTTP STATUS CODES FOR ALL REQUESTS ===");
  console.log(statusCounts);
}

parseStatusCodes(process.argv[2] || 'raw_k6_metrics_smart.json');
