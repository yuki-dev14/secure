import http from 'k6/http';
import { check, sleep } from 'k6';

// k6 Load Test & Bench Test Script
// Target Application: Local Laravel App (http://127.0.0.1:8000)

export const options = {
  stages: [
    // --- STAGE 1: Bench Test (Baseline, light load) ---
    { duration: '30s', target: 5 },

    // --- STAGE 2: Load Test (Ramp-up) ---
    { duration: '30s', target: 50 },
    { duration: '30s', target: 100 },
    { duration: '30s', target: 200 },

    // --- STAGE 3: Ramp-down ---
    { duration: '30s', target: 0 },
  ],
  thresholds: {
    // 95th percentile response time under 2000ms
    http_req_duration: ['p(95)<2000'],
    // Error rate under 1%
    http_req_failed: ['rate<0.01'],
  },
};

const BASE_URL = __ENV.TARGET_URL || 'http://127.0.0.1:8000';

// Tested Public Routes (no authentication required)
const ROUTES = [
  { name: 'Homepage', url: `${BASE_URL}/` },
  { name: 'Staff Login', url: `${BASE_URL}/login` },
  { name: 'Beneficiary Portal', url: `${BASE_URL}/portal` },
  { name: 'Sample CSV Download', url: `${BASE_URL}/uat_beneficiaries_sample.csv` },
  { name: 'Logo Image', url: `${BASE_URL}/logo.png` },
];

export default function () {
  // Randomly select or iterate over public endpoints
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
