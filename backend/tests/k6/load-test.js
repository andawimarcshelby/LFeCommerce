import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Custom metrics
const errorRate = new Rate('errors');

// Test configuration
export const options = {
    stages: [
        { duration: '30s', target: 10 },   // Ramp up to 10 users
        { duration: '1m', target: 50 },    // Ramp up to 50 users
        { duration: '2m', target: 100 },   // Ramp up to 100 users
        { duration: '1m', target: 100 },   // Stay at 100 users
        { duration: '30s', target: 0 },    // Ramp down
    ],
    thresholds: {
        http_req_duration: ['p(95)<500', 'p(99)<1000'], // 95% < 500ms, 99% < 1s
        errors: ['rate<0.1'], // Error rate < 10%
    },
};

const BASE_URL = __ENV.API_URL || 'http://localhost:8000';

export default function () {
    // Test 1: Report Preview (Detail Report)
    const previewPayload = JSON.stringify({
        report_type: 'detail',
        filters: {
            date_from: '2024-01-01',
            date_to: '2024-12-31',
            term_ids: [1, 2],
        },
        page: 1,
        per_page: 100,
    });

    const previewParams = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    const previewRes = http.post(
        `${BASE_URL}/api/reports/preview`,
        previewPayload,
        previewParams
    );

    const previewSuccess = check(previewRes, {
        'preview status is 200': (r) => r.status === 200,
        'preview response time < 500ms': (r) => r.timings.duration < 500,
        'preview has data': (r) => JSON.parse(r.body).data !== undefined,
        'preview has pagination': (r) => JSON.parse(r.body).pagination !== undefined,
    });

    errorRate.add(!previewSuccess);

    sleep(1);

    // Test 2: Summary Report Preview
    const summaryPayload = JSON.stringify({
        report_type: 'summary',
        filters: {
            date_from: '2024-01-01',
            date_to: '2024-03-31',
        },
        page: 1,
        per_page: 50,
    });

    const summaryRes = http.post(
        `${BASE_URL}/api/reports/preview`,
        summaryPayload,
        previewParams
    );

    const summarySuccess = check(summaryRes, {
        'summary status is 200': (r) => r.status === 200,
        'summary response time < 500ms': (r) => r.timings.duration < 500,
    });

    errorRate.add(!summarySuccess);

    sleep(1);

    // Test 3: Get Export Jobs List
    const jobsRes = http.get(`${BASE_URL}/api/reports/exports`);

    const jobsSuccess = check(jobsRes, {
        'jobs list status is 200': (r) => r.status === 200,
        'jobs list response time < 200ms': (r) => r.timings.duration < 200,
    });

    errorRate.add(!jobsSuccess);

    sleep(2);

    // Test 4: Create Export Job (less frequently)
    if (Math.random() < 0.1) { // Only 10% of requests create exports
        const exportPayload = JSON.stringify({
            report_type: 'summary',
            format: 'xlsx',
            filters: {
                date_from: '2024-01-01',
                date_to: '2024-01-31',
            },
        });

        const exportRes = http.post(
            `${BASE_URL}/api/reports/exports`,
            exportPayload,
            previewParams
        );

        const exportSuccess = check(exportRes, {
            'export creation status is 201': (r) => r.status === 201,
            'export has job_id': (r) => JSON.parse(r.body).job_id !== undefined,
        });

        errorRate.add(!exportSuccess);
    }

    sleep(1);
}

// Setup function (runs once per VU)
export function setup() {
    console.log(`Starting load test against ${BASE_URL}`);
    console.log('Testing report preview and export API endpoints');
}

// Teardown function (runs once at the end)
export function teardown(data) {
    console.log('Load test completed');
}
