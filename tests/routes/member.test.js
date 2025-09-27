/**
 * Member Routes Test Suite
 * Tests for member-specific API endpoints
 */

const request = require('supertest');
const app = require('../../server');
const { generateToken } = require('../../middleware/auth');

describe('Member Routes', () => {
    let memberToken;
    let adminToken;

    beforeAll(async () => {
        // Generate test tokens
        memberToken = generateToken({ id: 1, role: 'member', email: 'member@test.com' });
        adminToken = generateToken({ id: 2, role: 'admin', email: 'admin@test.com' });
    });

    describe('GET /api/member/profile', () => {
        it('should get member profile', async () => {
            const response = await request(app)
                .get('/api/member/profile')
                .set('Authorization', `Bearer ${memberToken}`)
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data.profile).toBeDefined();
        });

        it('should require authentication', async () => {
            await request(app)
                .get('/api/member/profile')
                .expect(401);
        });
    });

    describe('PUT /api/member/profile/personal', () => {
        it('should update personal information', async () => {
            const updateData = {
                first_name: 'John',
                last_name: 'Doe',
                phone: '+1234567890'
            };

            const response = await request(app)
                .put('/api/member/profile/personal')
                .set('Authorization', `Bearer ${memberToken}`)
                .send(updateData)
                .expect(200);

            expect(response.body.success).toBe(true);
        });

        it('should validate personal information', async () => {
            await request(app)
                .put('/api/member/profile/personal')
                .set('Authorization', `Bearer ${memberToken}`)
                .send({ first_name: '' })
                .expect(400);
        });
    });

    describe('GET /api/member/donations', () => {
        it('should get member donations', async () => {
            const response = await request(app)
                .get('/api/member/donations')
                .set('Authorization', `Bearer ${memberToken}`)
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data.donations).toBeDefined();
        });
    });

    describe('POST /api/member/events/:eventId/register', () => {
        it('should register for event', async () => {
            const response = await request(app)
                .post('/api/member/events/123/register')
                .set('Authorization', `Bearer ${memberToken}`)
                .expect(200);

            expect(response.body.success).toBe(true);
        });

        it('should validate event ID', async () => {
            await request(app)
                .post('/api/member/events/invalid/register')
                .set('Authorization', `Bearer ${memberToken}`)
                .expect(400);
        });
    });

    describe('DELETE /api/member/sessions/:sessionId', () => {
        it('should delete specific session', async () => {
            const response = await request(app)
                .delete('/api/member/sessions/session123')
                .set('Authorization', `Bearer ${memberToken}`)
                .expect(200);

            expect(response.body.success).toBe(true);
        });
    });

    describe('PUT /api/member/profile/two-factor', () => {
        it('should update two-factor settings', async () => {
            const response = await request(app)
                .put('/api/member/profile/two-factor')
                .set('Authorization', `Bearer ${memberToken}`)
                .send({ enabled: true, method: 'sms' })
                .expect(200);

            expect(response.body.success).toBe(true);
        });
    });
});