/**
 * Contact Routes Test Suite
 * Tests for contact form API endpoints
 */

const request = require('supertest');
const app = require('../../server');
const { generateToken } = require('../../middleware/auth');

describe('Contact Routes', () => {
    let memberToken;

    beforeAll(async () => {
        // Generate test token
        memberToken = generateToken({ id: 1, role: 'member', email: 'member@test.com' });
    });

    describe('POST /api/contact', () => {
        it('should submit contact form with valid data', async () => {
            const contactData = {
                name: 'John Doe',
                email: 'john@example.com',
                phone: '+1234567890',
                subject: 'Test Subject',
                message: 'This is a test message',
                category: 'general',
                priority: 'medium'
            };

            const response = await request(app)
                .post('/api/contact')
                .send(contactData)
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data.submission_id).toBeDefined();
        });

        it('should validate required fields', async () => {
            const invalidData = {
                name: '',
                email: 'invalid-email',
                message: ''
            };

            await request(app)
                .post('/api/contact')
                .send(invalidData)
                .expect(400);
        });

        it('should accept authenticated submissions', async () => {
            const contactData = {
                name: 'Jane Doe',
                email: 'jane@example.com',
                subject: 'Member Inquiry',
                message: 'This is from an authenticated member',
                category: 'support'
            };

            const response = await request(app)
                .post('/api/contact')
                .set('Authorization', `Bearer ${memberToken}`)
                .send(contactData)
                .expect(200);

            expect(response.body.success).toBe(true);
        });
    });

    describe('GET /api/contact/status/:submissionId', () => {
        it('should get contact submission status', async () => {
            const response = await request(app)
                .get('/api/contact/status/12345')
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data.status).toBeDefined();
        });

        it('should validate submission ID format', async () => {
            await request(app)
                .get('/api/contact/status/invalid')
                .expect(400);
        });
    });

    describe('GET /api/contact/categories', () => {
        it('should get contact categories', async () => {
            const response = await request(app)
                .get('/api/contact/categories')
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data.categories).toBeDefined();
            expect(Array.isArray(response.body.data.categories)).toBe(true);
        });
    });

    describe('GET /api/contact/info', () => {
        it('should get contact information', async () => {
            const response = await request(app)
                .get('/api/contact/info')
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data.contact_info).toBeDefined();
        });
    });
});