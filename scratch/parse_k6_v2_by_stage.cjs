const fs = require('fs');
const readline = require('readline');

async function parseMetricsByStage(filename) {
  const fileStream = fs.createReadStream(filename);
  const rl = readline.createInterface({
    input: fileStream,
    crlfDelay: Infinity
  });

  let startTime = null;
  const events = [];

  for await (const line of rl) {
    if (!line.trim()) continue;
    try {
      const obj = JSON.parse(line);
      if (obj.type === 'Point') {
        const time = new Date(obj.data.time).getTime();
        if (!startTime) startTime = time;
        const relSec = (time - startTime) / 1000;

        if (obj.metric === 'http_req_duration') {
          events.push({
            type: 'duration',
            relSec,
            value: obj.data.value,
            status200: obj.data.tags && obj.data.tags.expected_response === 'true'
          });
        } else if (obj.metric === 'http_req_failed') {
          events.push({
            type: 'failed',
            relSec,
            value: obj.data.value // 1 if failed, 0 if passed
          });
        }
      }
    } catch (e) {}
  }

  const stages = [
    { name: 'Stage 1 (5 VUs)', start: 0, end: 30 },
    { name: 'Stage 2 (10 VUs)', start: 30, end: 60 },
    { name: 'Stage 3 (15 VUs)', start: 60, end: 90 },
    { name: 'Stage 4 (20 VUs)', start: 90, end: 120 },
    { name: 'Stage 5 (30 VUs)', start: 120, end: 150 },
    { name: 'Stage 6 (50 VUs)', start: 150, end: 180 },
    { name: 'Stage 7 (Ramp Down)', start: 180, end: 210 },
  ];

  function calcStats(arr) {
    if (arr.length === 0) return { count: 0, min: 'N/A', avg: 'N/A', med: 'N/A', p90: 'N/A', p95: 'N/A', p99: 'N/A', max: 'N/A' };
    const sorted = [...arr].sort((a, b) => a - b);
    const sum = sorted.reduce((a, b) => a + b, 0);
    const avg = sum / sorted.length;
    
    function pct(p) {
      const idx = Math.ceil((p / 100) * sorted.length) - 1;
      return sorted[Math.max(0, Math.min(idx, sorted.length - 1))];
    }

    return {
      count: sorted.length,
      min: sorted[0].toFixed(2),
      avg: avg.toFixed(2),
      med: pct(50).toFixed(2),
      p90: pct(90).toFixed(2),
      p95: pct(95).toFixed(2),
      p99: pct(99).toFixed(2),
      max: sorted[sorted.length - 1].toFixed(2)
    };
  }

  console.log("=== STAGE BREAKDOWN METRICS ===");
  for (const st of stages) {
    const stageDurationsAll = events.filter(e => e.type === 'duration' && e.relSec >= st.start && e.relSec < st.end).map(e => e.value);
    const stageDurations200 = events.filter(e => e.type === 'duration' && e.status200 && e.relSec >= st.start && e.relSec < st.end).map(e => e.value);
    const stageFailures = events.filter(e => e.type === 'failed' && e.relSec >= st.start && e.relSec < st.end);

    const totalReqs = stageFailures.length;
    const failedCount = stageFailures.filter(e => e.value === 1).length;
    const successCount = stageFailures.filter(e => e.value === 0).length;
    const errorRate = totalReqs > 0 ? ((failedCount / totalReqs) * 100).toFixed(2) + '%' : '0%';
    const rps = (totalReqs / (st.end - st.start)).toFixed(2);

    console.log(`\n--- ${st.name} [${st.start}s - ${st.end}s] ---`);
    console.log(`Requests: Total=${totalReqs}, Passed(200)=${successCount}, Failed=${failedCount}, ErrorRate=${errorRate}, Throughput=${rps} req/s`);
    console.log(`All Requests Latency (ms):`, calcStats(stageDurationsAll));
    console.log(`200 OK Requests Latency (ms):`, calcStats(stageDurations200));
  }

  console.log("\n=== OVERALL TEST METRICS ===");
  const allDurations = events.filter(e => e.type === 'duration').map(e => e.value);
  const allDurations200 = events.filter(e => e.type === 'duration' && e.status200).map(e => e.value);
  const allFailures = events.filter(e => e.type === 'failed');
  const total = allFailures.length;
  const failed = allFailures.filter(e => e.value === 1).length;
  const passed = allFailures.filter(e => e.value === 0).length;
  
  console.log(`Overall Requests: Total=${total}, Passed=${passed}, Failed=${failed}, ErrorRate=${((failed/total)*100).toFixed(4)}%`);
  console.log(`Overall All Latency (ms):`, calcStats(allDurations));
  console.log(`Overall 200 OK Latency (ms):`, calcStats(allDurations200));
}

parseMetricsByStage(process.argv[2] || 'raw_k6_metrics_v2.json');
