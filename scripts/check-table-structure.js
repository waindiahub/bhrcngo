const mysql = require('mysql2/promise');

const dbConfig = {
    host: '37.27.60.109',
    user: 'tzdmiohj_bhmc',
    password: 'tzdmiohj_bhmc',
    database: 'tzdmiohj_bhmc',
    charset: 'utf8mb4'
};

async function checkTableStructure() {
    let connection;
    
    try {
        connection = await mysql.createConnection(dbConfig);
        console.log('Connected to database');
        
        const [rows] = await connection.execute('DESCRIBE complaints');
        console.log('\nComplaints table structure:');
        console.log('Field\t\t\tType\t\t\tNull\tKey\tDefault\tExtra');
        console.log('='.repeat(80));
        
        rows.forEach(row => {
            console.log(`${row.Field.padEnd(20)}\t${row.Type.padEnd(20)}\t${row.Null}\t${row.Key}\t${row.Default}\t${row.Extra}`);
        });
        
    } catch (error) {
        console.error('Error:', error.message);
    } finally {
        if (connection) {
            await connection.end();
        }
    }
}

checkTableStructure();