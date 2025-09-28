/**
 * Database Configuration for BHRC India Application
 * 
 * MySQL database connection setup using mysql2 with connection pooling
 * for optimal performance and connection management.
 */

const mysql = require('mysql2/promise');

// Database configuration - using the specified credentials
const dbConfig = {
    host: '193.203.184.228',
    user: 'u851023220_bhrcdatabasee',
    password: 'u851023220_BHRC-pwd',
    database: 'u851023220_bhrcdatabasee',
    charset: 'utf8mb4',
    timezone: '+00:00',
    // Connection pool settings
    connectionLimit: 10,
    queueLimit: 0,
    // Additional MySQL settings
    supportBigNumbers: true,
    bigNumberStrings: true,
    dateStrings: false,
    debug: false,
    multipleStatements: false
};

// Create connection pool
const pool = mysql.createPool(dbConfig);

/**
 * Database class with common operations
 */
class Database {
    constructor() {
        this.pool = pool;
    }

    /**
     * Test database connection
     * @returns {Promise<boolean>}
     */
    async testConnection() {
        try {
            const connection = await this.pool.getConnection();
            await connection.ping();
            connection.release();
            return true;
        } catch (error) {
            console.error('Database connection test failed:', error.message);
            throw error;
        }
    }

    /**
     * Execute a query with parameters
     * @param {string} query - SQL query
     * @param {Array} params - Query parameters
     * @returns {Promise<Array>} Query results
     */
    async query(query, params = []) {
        try {
            const [results] = await this.pool.execute(query, params);
            return results;
        } catch (error) {
            console.error('Database query error:', error.message);
            console.error('Query:', query);
            console.error('Params:', params);
            throw error;
        }
    }

    /**
     * Execute a query and return the first row
     * @param {string} query - SQL query
     * @param {Array} params - Query parameters
     * @returns {Promise<Object|null>} First row or null
     */
    async queryOne(query, params = []) {
        try {
            const results = await this.query(query, params);
            return results.length > 0 ? results[0] : null;
        } catch (error) {
            throw error;
        }
    }

    /**
     * Insert a record and return the inserted ID
     * @param {string} table - Table name
     * @param {Object} data - Data to insert
     * @returns {Promise<number>} Inserted ID
     */
    async insert(table, data) {
        try {
            const fields = Object.keys(data);
            const values = Object.values(data);
            const placeholders = fields.map(() => '?').join(', ');
            
            const query = `INSERT INTO ${table} (${fields.join(', ')}) VALUES (${placeholders})`;
            const [result] = await this.pool.execute(query, values);
            
            return result.insertId;
        } catch (error) {
            console.error('Database insert error:', error.message);
            throw error;
        }
    }

    /**
     * Update records in a table
     * @param {string} table - Table name
     * @param {Object} data - Data to update
     * @param {string} whereClause - WHERE clause
     * @param {Array} whereParams - WHERE clause parameters
     * @returns {Promise<number>} Number of affected rows
     */
    async update(table, data, whereClause, whereParams = []) {
        try {
            const fields = Object.keys(data);
            const values = Object.values(data);
            const setClause = fields.map(field => `${field} = ?`).join(', ');
            
            const query = `UPDATE ${table} SET ${setClause} WHERE ${whereClause}`;
            const [result] = await this.pool.execute(query, [...values, ...whereParams]);
            
            return result.affectedRows;
        } catch (error) {
            console.error('Database update error:', error.message);
            throw error;
        }
    }

    /**
     * Delete records from a table
     * @param {string} table - Table name
     * @param {string} whereClause - WHERE clause
     * @param {Array} whereParams - WHERE clause parameters
     * @returns {Promise<number>} Number of affected rows
     */
    async delete(table, whereClause, whereParams = []) {
        try {
            const query = `DELETE FROM ${table} WHERE ${whereClause}`;
            const [result] = await this.pool.execute(query, whereParams);
            
            return result.affectedRows;
        } catch (error) {
            console.error('Database delete error:', error.message);
            throw error;
        }
    }

    /**
     * Begin a database transaction
     * @returns {Promise<Connection>} Database connection with transaction
     */
    async beginTransaction() {
        try {
            const connection = await this.pool.getConnection();
            await connection.beginTransaction();
            return connection;
        } catch (error) {
            console.error('Transaction begin error:', error.message);
            throw error;
        }
    }

    /**
     * Commit a transaction
     * @param {Connection} connection - Database connection
     */
    async commitTransaction(connection) {
        try {
            await connection.commit();
            connection.release();
        } catch (error) {
            console.error('Transaction commit error:', error.message);
            await connection.rollback();
            connection.release();
            throw error;
        }
    }

    /**
     * Rollback a transaction
     * @param {Connection} connection - Database connection
     */
    async rollbackTransaction(connection) {
        try {
            await connection.rollback();
            connection.release();
        } catch (error) {
            console.error('Transaction rollback error:', error.message);
            connection.release();
            throw error;
        }
    }

    /**
     * Get connection pool statistics
     * @returns {Object} Pool statistics
     */
    getPoolStats() {
        return {
            totalConnections: this.pool._allConnections.length,
            freeConnections: this.pool._freeConnections.length,
            acquiringConnections: this.pool._acquiringConnections.length
        };
    }

    /**
     * Close all connections in the pool
     */
    async closePool() {
        try {
            await this.pool.end();
            console.log('Database connection pool closed');
        } catch (error) {
            console.error('Error closing database pool:', error.message);
            throw error;
        }
    }
}

// Create and export database instance
const db = new Database();

module.exports = db;