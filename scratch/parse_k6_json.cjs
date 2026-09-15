const fs = require('fs');
const readline = require('readline');

async function parseMetrics(filename) {
  const fileStream = fs.createReadStream(filename);
  const rl = readline.createInterface({
    input: fileStream,
    crlfDelay: Infinity
  });

  const durationsAll = [];
  const durationsExpected200 = [];
  let totalReqs = 0;
  let failedReqs = 0;
  let successReqs = 0;

  for await (const line of rl) {
    if (!line.trim()) continue;
    try {
      const obj = JSON.parse(line);
      if (obj.type === 'Point') {
        if (obj.metric === 'http_req_duration') {
          durationsAll.push(obj.data.value);
          if (obj.data.tags && obj.data.tags.expected_response === 'true') {
            durationsExpected200.push(obj.data.value);
          }
        } else if (obj.metric === 'http_req_failed') {
          totalReqs++;
          if (obj.data.value === 1) {
            failedReqs++;
          } else {
            successReqs++;
          }
        }
      }
    } catch (e) {
      // ignore non-json lines if any
    }
  }

  function getStats(arr) {
    if (arr.length === 0) return null;
    const sorted = [...arr].sort((a, b) => a - b);
    const sum = sorted.reduce((a, b) => a + b, 0);
    const avg = sum / sorted.length;
    const min = sorted[0];
    const max = sorted[sorted.length - 1];
    
    function percentile(p) {
      const idx = Math.ceil((p / 100) * sorted.length) - 1;
      return sorted[Math.max(0, Math.min(idx, sorted.length - 1))];
    }

    return {
      count: sorted.length,
      min: min.toFixed(2),
      avg: avg.toFixed(2),
      med: percentile(50).toFixed(2),
      p90: percentile(90).toFixed(2),
      p95: percentile(95).toFixed(2),
      p99: percentile(99).toFixed(2),
      max: max.toFixed(2)
    };
  }

  console.log("=== ALL REQUESTS http_req_duration (ms) ===");
  console.log(getStats(durationsAll));

  console.log("\n=== EXPECTED (200 OK) http_req_duration (ms) ===");
  console.log(getStats(durationsExpected200));

  console.log("\n=== http_req_failed ===");
  console.log({
    totalReqs,
    failedReqs,
    successReqs,
    failedRatePct: totalReqs > 0 ? ((failedReqs / totalReqs) * 100).toFixed(4) + '%' : '0%'
  });
}

parseMetrics(process.argv[2] || 'raw_k6_metrics.json');
