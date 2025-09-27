/**
 * Admin Routes Test Suite
 * Tests for admin-specific API endpoints
 */

const request = require('supertest');
const app = require('../../server');
const { generateToken } = require('../../middleware/auth');

describe('Admin Routes', () => {
    let adminToken;
    let userToken;

    beforeAll(async () => {
        // Generate test tokens
        adminToken = generateToken({ id: 1, role: 'admin', email: 'admin@test.com' });
        userToken = generateToken({ id: 2, role: 'member', email: 'user@test.com' });
    });

    describe('GET /api/admin/users', () => {
        it('should get all users for admin', async () => {
            const response = await request(app)
                .get('/api/admin/users')
                .set('Authorization', `Bearer ${adminToken}`)
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data).toBeDefined();
        });

        it('should deny access for non-admin users', async () => {
            await request(app)
                .get('/api/admin/users')
                .set('Authorization', `Bearer ${userToken}`)
                .expect(403);
        });
    });

    describe('DELETE /api/admin/users/:userId', () => {
        it('should delete user for admin', async () => {
            const response = await request(app)
                .delete('/api/admin/users/123')
                .set('Authorization', `Bearer ${adminToken}`)
                .expect(200);

            expect(response.body.success).toBe(true);
        });

        it('should validate user ID parameter', async () => {
            await request(app)
                .delete('/api/admin/users/invalid')
                .set('Authorization', `Bearer ${adminToken}`)
                .expect(400);
        });
    });

    describe('GET /api/admin/activities', () => {
        it('should get admin activities', async () => {
            const response = await request(app)
                .get('/api/admin/activities')
                .set('Authorization', `Bearer ${adminToken}`)
                .expect(200);

            expect(response.body.success).toBe(true);
            expect(response.body.data.activities).toBeDefined();
        });
    });

    describe('POST /api/admin/users/bulk-delete', () => {
        it('should bulk delete users', async () => {
            const response = await request(app)
                .post('/api/admin/users/bulk-delete')
                .set('Authorization', `Bearer ${adminToken}`)
                .send({ user_ids: [1, 2, 3] })
                .expect(200);

            expect(response.body.success).toBe(true);
        });

        it('should validate bulk delete payload', async () => {
            await request(app)
                .post('/api/admin/users/bulk-delete')
                .set('Authorization', `Bearer ${adminToken}`)
                .send({ user_ids: [] })
                .expect(400);
        });
    });
});