import http from 'k6/http';
import { check, sleep } from 'k6';

// k6 Load Test v2 Script with Fine-Grained Concurrency Stages
// Target: http://127.0.0.1:8000

export const options = {
  stages: [
    { duration: '30s', target: 5 },   // Stage 1: Baseline (5 VUs)
    { duration: '30s', target: 10 },  // Stage 2: 10 VUs
    { duration: '30s', target: 15 },  // Stage 3: 15 VUs
    { duration: '30s', target: 20 },  // Stage 4: 20 VUs
    { duration: '30s', target: 30 },  // Stage 5: 30 VUs
    { duration: '30s', target: 50 },  // Stage 6: 50 VUs
    { duration: '30s', target: 0 },   // Stage 7: Ramp down to 0
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.TARGET_URL || 'http://127.0.0.1:8000';

const ROUTES = [
  { name: 'Homepage', url: `${BASE_URL}/` },
  { name: 'Staff Login', url: `${BASE_URL}/login` },
  { name: 'Beneficiary Portal', url: `${BASE_URL}/portal` },
  { name: 'Sample CSV Download', url: `${BASE_URL}/uat_beneficiaries_sample.csv` },
  { name: 'Logo Image', url: `${BASE_URL}/logo.png` },
];

export default function () {
  for (const route of ROUTES) {
    const res = http.get(route.url, {
      tags: { name: route.name },
      timeout: '10s',
    });

    check(res, {
      [`${route.name} status is 200`]: (r) => r.status === 200,
    });

    sleep(0.2); // Pace virtual users realistically
  }
}
