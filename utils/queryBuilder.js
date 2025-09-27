/**
 * Query Builder Utility for BHRC India Application
 * 
 * Provides standardized database query building methods
 * for consistent and secure database operations across controllers.
 */

const db = require('../config/database');

class QueryBuilder {
    constructor(table) {
        this.table = table;
        this.selectFields = ['*'];
        this.whereConditions = [];
        this.joinClauses = [];
        this.orderByClause = '';
        this.limitClause = '';
        this.groupByClause = '';
        this.havingClause = '';
        this.params = [];
    }

    /**
     * Create new QueryBuilder instance
     * @param {string} table - Table name
     * @returns {QueryBuilder}
     */
    static table(table) {
        return new QueryBuilder(table);
    }

    /**
     * Select specific fields
     * @param {string|array} fields - Fields to select
     * @returns {QueryBuilder}
     */
    select(fields) {
        if (Array.isArray(fields)) {
            this.selectFields = fields;
        } else {
            this.selectFields = [fields];
        }
        return this;
    }

    /**
     * Add WHERE condition
     * @param {string} field - Field name
     * @param {string} operator - Comparison operator
     * @param {any} value - Field value
     * @returns {QueryBuilder}
     */
    where(field, operator, value) {
        this.whereConditions.push(`${field} ${operator} ?`);
        this.params.push(value);
        return this;
    }

    /**
     * Add WHERE IN condition
     * @param {string} field - Field name
     * @param {array} values - Array of values
     * @returns {QueryBuilder}
     */
    whereIn(field, values) {
        if (values.length === 0) return this;
        
        const placeholders = values.map(() => '?').join(',');
        this.whereConditions.push(`${field} IN (${placeholders})`);
        this.params.push(...values);
        return this;
    }

    /**
     * Add WHERE LIKE condition
     * @param {string} field - Field name
     * @param {string} value - Search value
     * @returns {QueryBuilder}
     */
    whereLike(field, value) {
        this.whereConditions.push(`${field} LIKE ?`);
        this.params.push(`%${value}%`);
        return this;
    }

    /**
     * Add WHERE BETWEEN condition
     * @param {string} field - Field name
     * @param {any} start - Start value
     * @param {any} end - End value
     * @returns {QueryBuilder}
     */
    whereBetween(field, start, end) {
        this.whereConditions.push(`${field} BETWEEN ? AND ?`);
        this.params.push(start, end);
        return this;
    }

    /**
     * Add WHERE NULL condition
     * @param {string} field - Field name
     * @returns {QueryBuilder}
     */
    whereNull(field) {
        this.whereConditions.push(`${field} IS NULL`);
        return this;
    }

    /**
     * Add WHERE NOT NULL condition
     * @param {string} field - Field name
     * @returns {QueryBuilder}
     */
    whereNotNull(field) {
        this.whereConditions.push(`${field} IS NOT NULL`);
        return this;
    }

    /**
     * Add OR WHERE condition
     * @param {string} field - Field name
     * @param {string} operator - Comparison operator
     * @param {any} value - Field value
     * @returns {QueryBuilder}
     */
    orWhere(field, operator, value) {
        if (this.whereConditions.length > 0) {
            this.whereConditions.push(`OR ${field} ${operator} ?`);
        } else {
            this.whereConditions.push(`${field} ${operator} ?`);
        }
        this.params.push(value);
        return this;
    }

    /**
     * Add JOIN clause
     * @param {string} table - Table to join
     * @param {string} condition - Join condition
     * @param {string} type - Join type (INNER, LEFT, RIGHT)
     * @returns {QueryBuilder}
     */
    join(table, condition, type = 'INNER') {
        this.joinClauses.push(`${type} JOIN ${table} ON ${condition}`);
        return this;
    }

    /**
     * Add LEFT JOIN clause
     * @param {string} table - Table to join
     * @param {string} condition - Join condition
     * @returns {QueryBuilder}
     */
    leftJoin(table, condition) {
        return this.join(table, condition, 'LEFT');
    }

    /**
     * Add RIGHT JOIN clause
     * @param {string} table - Table to join
     * @param {string} condition - Join condition
     * @returns {QueryBuilder}
     */
    rightJoin(table, condition) {
        return this.join(table, condition, 'RIGHT');
    }

    /**
     * Add ORDER BY clause
     * @param {string} field - Field to order by
     * @param {string} direction - Order direction (ASC, DESC)
     * @returns {QueryBuilder}
     */
    orderBy(field, direction = 'ASC') {
        this.orderByClause = `ORDER BY ${field} ${direction}`;
        return this;
    }

    /**
     * Add GROUP BY clause
     * @param {string} field - Field to group by
     * @returns {QueryBuilder}
     */
    groupBy(field) {
        this.groupByClause = `GROUP BY ${field}`;
        return this;
    }

    /**
     * Add HAVING clause
     * @param {string} condition - Having condition
     * @returns {QueryBuilder}
     */
    having(condition) {
        this.havingClause = `HAVING ${condition}`;
        return this;
    }

    /**
     * Add LIMIT clause
     * @param {number} limit - Number of records to limit
     * @param {number} offset - Number of records to skip
     * @returns {QueryBuilder}
     */
    limit(limit, offset = 0) {
        this.limitClause = `LIMIT ${limit}`;
        if (offset > 0) {
            this.limitClause += ` OFFSET ${offset}`;
        }
        return this;
    }

    /**
     * Add pagination
     * @param {number} page - Page number
     * @param {number} perPage - Records per page
     * @returns {QueryBuilder}
     */
    paginate(page, perPage) {
        const offset = (page - 1) * perPage;
        return this.limit(perPage, offset);
    }

    /**
     * Build SELECT query
     * @returns {Object} Query object with sql and params
     */
    buildSelect() {
        let sql = `SELECT ${this.selectFields.join(', ')} FROM ${this.table}`;

        if (this.joinClauses.length > 0) {
            sql += ` ${this.joinClauses.join(' ')}`;
        }

        if (this.whereConditions.length > 0) {
            sql += ` WHERE ${this.whereConditions.join(' AND ')}`;
        }

        if (this.groupByClause) {
            sql += ` ${this.groupByClause}`;
        }

        if (this.havingClause) {
            sql += ` ${this.havingClause}`;
        }

        if (this.orderByClause) {
            sql += ` ${this.orderByClause}`;
        }

        if (this.limitClause) {
            sql += ` ${this.limitClause}`;
        }

        return { sql, params: this.params };
    }

    /**
     * Build COUNT query
     * @returns {Object} Query object with sql and params
     */
    buildCount() {
        let sql = `SELECT COUNT(*) as total FROM ${this.table}`;

        if (this.joinClauses.length > 0) {
            sql += ` ${this.joinClauses.join(' ')}`;
        }

        if (this.whereConditions.length > 0) {
            sql += ` WHERE ${this.whereConditions.join(' AND ')}`;
        }

        return { sql, params: this.params };
    }

    /**
     * Execute SELECT query
     * @returns {Promise<Array>} Query results
     */
    async get() {
        const { sql, params } = this.buildSelect();
        return await db.query(sql, params);
    }

    /**
     * Execute SELECT query and return first result
     * @returns {Promise<Object|null>} First result or null
     */
    async first() {
        const { sql, params } = this.buildSelect();
        return await db.queryOne(sql, params);
    }

    /**
     * Execute COUNT query
     * @returns {Promise<number>} Total count
     */
    async count() {
        const { sql, params } = this.buildCount();
        const result = await db.queryOne(sql, params);
        return result.total;
    }

    /**
     * Execute query with pagination
     * @returns {Promise<Object>} Results with pagination info
     */
    async paginated() {
        // Get total count first
        const total = await this.count();
        
        // Get paginated results
        const results = await this.get();
        
        return {
            data: results,
            total,
            pagination: {
                current_page: Math.floor(this.params.length > 0 ? 
                    (this.limitClause.includes('OFFSET') ? 
                        parseInt(this.limitClause.split('OFFSET ')[1]) / parseInt(this.limitClause.split(' ')[1]) + 1 : 1) : 1),
                per_page: this.limitClause ? parseInt(this.limitClause.split(' ')[1]) : total,
                total,
                total_pages: this.limitClause ? Math.ceil(total / parseInt(this.limitClause.split(' ')[1])) : 1
            }
        };
    }

    /**
     * Insert data into table
     * @param {Object} data - Data to insert
     * @returns {Promise<Object>} Insert result
     */
    static async insert(table, data) {
        return await db.insert(table, data);
    }

    /**
     * Update data in table
     * @param {string} table - Table name
     * @param {Object} data - Data to update
     * @param {string} whereClause - WHERE clause
     * @param {Array} whereParams - WHERE parameters
     * @returns {Promise<Object>} Update result
     */
    static async update(table, data, whereClause, whereParams = []) {
        return await db.update(table, data, whereClause, whereParams);
    }

    /**
     * Delete data from table
     * @param {string} table - Table name
     * @param {string} whereClause - WHERE clause
     * @param {Array} whereParams - WHERE parameters
     * @returns {Promise<Object>} Delete result
     */
    static async delete(table, whereClause, whereParams = []) {
        return await db.delete(table, whereClause, whereParams);
    }

    /**
     * Find record by ID
     * @param {string} table - Table name
     * @param {number} id - Record ID
     * @returns {Promise<Object|null>} Record or null
     */
    static async findById(table, id) {
        return await QueryBuilder.table(table).where('id', '=', id).first();
    }

    /**
     * Check if record exists
     * @param {string} table - Table name
     * @param {string} field - Field name
     * @param {any} value - Field value
     * @returns {Promise<boolean>} True if exists
     */
    static async exists(table, field, value) {
        const result = await QueryBuilder.table(table).where(field, '=', value).count();
        return result > 0;
    }

    /**
     * Get records with search functionality
     * @param {string} table - Table name
     * @param {Array} searchFields - Fields to search in
     * @param {string} searchTerm - Search term
     * @param {Object} options - Additional options
     * @returns {QueryBuilder} QueryBuilder instance
     */
    static search(table, searchFields, searchTerm, options = {}) {
        const query = QueryBuilder.table(table);
        
        if (searchTerm && searchFields.length > 0) {
            // Add search conditions
            searchFields.forEach((field, index) => {
                if (index === 0) {
                    query.whereLike(field, searchTerm);
                } else {
                    query.orWhere(field, 'LIKE', `%${searchTerm}%`);
                }
            });
        }

        // Apply additional filters
        if (options.filters) {
            Object.entries(options.filters).forEach(([field, value]) => {
                if (value !== null && value !== undefined && value !== '') {
                    query.where(field, '=', value);
                }
            });
        }

        // Apply sorting
        if (options.sortBy && options.sortOrder) {
            query.orderBy(options.sortBy, options.sortOrder);
        }

        return query;
    }
}

module.exports = QueryBuilder;