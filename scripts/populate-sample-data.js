/**
 * Sample Data Population Script for BHRC India
 * Populates the database with realistic complaint data for testing
 */

const mysql = require('mysql2/promise');

// Database configuration
const dbConfig = {
    host: '37.27.60.109',
    user: 'tzdmiohj_bhmc',
    password: 'tzdmiohj_bhmc',
    database: 'tzdmiohj_bhmc',
    charset: 'utf8mb4'
};

// Sample data arrays
const sampleComplaints = [
    {
        complainant_name: 'Rajesh Kumar',
        complainant_email: 'rajesh.kumar@email.com',
        complainant_phone: '+91-9876543210',
        complainant_address: '123 Gandhi Nagar, Sector 15',
        complainant_city: 'New Delhi',
        complainant_state: 'Delhi',
        complainant_pincode: '110001',
        subject: 'Workplace Discrimination Based on Caste',
        description: 'I am facing severe discrimination at my workplace due to my caste. My colleagues make derogatory remarks and I am being denied promotions despite having better qualifications than others. The management is not taking any action despite multiple complaints.',
        category: 'discrimination',
        subcategory: 'workplace_discrimination',
        priority: 'high',
        status: 'under_review',
        incident_date: '2024-01-15',
        incident_time: '14:30:00',
        incident_location: 'ABC Corporation Office',
        incident_address: 'Connaught Place, New Delhi',
        legal_action_required: true,
        compensation_sought: 500000.00,
        follow_up_required: true,
        public_visibility: 'private'
    },
    {
        complainant_name: 'Priya Sharma',
        complainant_email: 'priya.sharma@email.com',
        complainant_phone: '+91-9876543211',
        complainant_address: '456 MG Road',
        complainant_city: 'Mumbai',
        complainant_state: 'Maharashtra',
        complainant_pincode: '400001',
        subject: 'Police Harassment and Illegal Detention',
        description: 'Local police officers have been harassing me and my family without any valid reason. They detained me for 6 hours without proper documentation and used abusive language. This is a clear violation of human rights.',
        category: 'police_brutality',
        subcategory: 'illegal_detention',
        priority: 'urgent',
        status: 'investigating',
        incident_date: '2024-01-20',
        incident_time: '20:00:00',
        incident_location: 'Bandra Police Station',
        incident_address: 'Bandra West, Mumbai',
        legal_action_required: true,
        compensation_sought: 200000.00,
        follow_up_required: true,
        public_visibility: 'anonymous'
    },
    {
        complainant_name: 'Mohammed Ali',
        complainant_email: 'mohammed.ali@email.com',
        complainant_phone: '+91-9876543212',
        complainant_address: '789 Park Street',
        complainant_city: 'Kolkata',
        complainant_state: 'West Bengal',
        complainant_pincode: '700001',
        subject: 'Religious Discrimination in Housing',
        description: 'I am being denied rental accommodation in multiple societies due to my religion. Property owners explicitly state they do not rent to Muslims. This is clear religious discrimination and violation of my fundamental rights.',
        category: 'discrimination',
        subcategory: 'religious_discrimination',
        priority: 'medium',
        status: 'submitted',
        incident_date: '2024-01-25',
        incident_time: '11:00:00',
        incident_location: 'Salt Lake City Housing Complex',
        incident_address: 'Salt Lake, Kolkata',
        legal_action_required: false,
        compensation_sought: null,
        follow_up_required: true,
        public_visibility: 'public'
    },
    {
        complainant_name: 'Sunita Devi',
        complainant_email: 'sunita.devi@email.com',
        complainant_phone: '+91-9876543213',
        complainant_address: '321 Civil Lines',
        complainant_city: 'Lucknow',
        complainant_state: 'Uttar Pradesh',
        complainant_pincode: '226001',
        subject: 'Domestic Violence and Dowry Harassment',
        description: 'My husband and in-laws are physically and mentally torturing me for additional dowry. They have threatened to throw acid on my face if I do not bring more money from my parents. I need immediate protection and legal help.',
        category: 'violence',
        subcategory: 'domestic_violence',
        priority: 'urgent',
        status: 'resolved',
        incident_date: '2024-01-10',
        incident_time: '22:00:00',
        incident_location: 'Residential Area',
        incident_address: 'Gomti Nagar, Lucknow',
        legal_action_required: true,
        compensation_sought: 1000000.00,
        follow_up_required: false,
        public_visibility: 'private',
        resolved_at: '2024-02-15 10:30:00'
    },
    {
        complainant_name: 'Arjun Singh',
        complainant_email: 'arjun.singh@email.com',
        complainant_phone: '+91-9876543214',
        complainant_address: '654 Brigade Road',
        complainant_city: 'Bangalore',
        complainant_state: 'Karnataka',
        complainant_pincode: '560001',
        subject: 'Corruption in Government Office',
        description: 'Government officials are demanding bribes for issuing my caste certificate. They have been delaying the process for 6 months and explicitly asking for money to expedite the work. This is clear corruption.',
        category: 'corruption',
        subcategory: 'bribery',
        priority: 'medium',
        status: 'under_review',
        incident_date: '2024-01-30',
        incident_time: '15:00:00',
        incident_location: 'Tehsildar Office',
        incident_address: 'Vidhana Soudha, Bangalore',
        legal_action_required: true,
        compensation_sought: 50000.00,
        follow_up_required: true,
        public_visibility: 'public'
    },
    {
        complainant_name: 'Kavita Patel',
        complainant_email: 'kavita.patel@email.com',
        complainant_phone: '+91-9876543215',
        complainant_address: '987 Ashram Road',
        complainant_city: 'Ahmedabad',
        complainant_state: 'Gujarat',
        complainant_pincode: '380001',
        subject: 'Sexual Harassment at Workplace',
        description: 'My supervisor has been making inappropriate comments and gestures. He has also threatened to fire me if I do not comply with his demands. I have evidence in the form of text messages and witness statements.',
        category: 'harassment',
        subcategory: 'sexual_harassment',
        priority: 'high',
        status: 'investigating',
        incident_date: '2024-02-01',
        incident_time: '18:00:00',
        incident_location: 'XYZ IT Company',
        incident_address: 'SG Highway, Ahmedabad',
        legal_action_required: true,
        compensation_sought: 300000.00,
        follow_up_required: true,
        public_visibility: 'private'
    },
    {
        complainant_name: 'Ramesh Yadav',
        complainant_email: 'ramesh.yadav@email.com',
        complainant_phone: '+91-9876543216',
        complainant_address: '147 Station Road',
        complainant_city: 'Jaipur',
        complainant_state: 'Rajasthan',
        complainant_pincode: '302001',
        subject: 'Denial of Basic Amenities in Slum Area',
        description: 'Our slum area has been denied basic amenities like water, electricity, and sanitation for over 2 years. The local authorities ignore our requests and treat us inhumanely due to our economic status.',
        category: 'civil_rights',
        subcategory: 'basic_amenities',
        priority: 'high',
        status: 'submitted',
        incident_date: '2024-02-05',
        incident_time: '09:00:00',
        incident_location: 'Slum Area Near Railway Station',
        incident_address: 'Railway Colony, Jaipur',
        legal_action_required: false,
        compensation_sought: null,
        follow_up_required: true,
        public_visibility: 'public'
    },
    {
        complainant_name: 'Deepika Reddy',
        complainant_email: 'deepika.reddy@email.com',
        complainant_phone: '+91-9876543217',
        complainant_address: '258 Tank Bund Road',
        complainant_city: 'Hyderabad',
        complainant_state: 'Telangana',
        complainant_pincode: '500001',
        subject: 'Child Labor in Local Factory',
        description: 'I have witnessed children below 14 years working in dangerous conditions at a local textile factory. They work 12-hour shifts without proper safety equipment. This is a clear violation of child rights.',
        category: 'civil_rights',
        subcategory: 'child_rights',
        priority: 'urgent',
        status: 'under_review',
        incident_date: '2024-02-08',
        incident_time: '16:00:00',
        incident_location: 'ABC Textile Factory',
        incident_address: 'Industrial Area, Hyderabad',
        legal_action_required: true,
        compensation_sought: null,
        follow_up_required: true,
        public_visibility: 'public'
    },
    {
        complainant_name: 'Vikram Choudhary',
        complainant_email: 'vikram.choudhary@email.com',
        complainant_phone: '+91-9876543218',
        complainant_address: '369 Mall Road',
        complainant_city: 'Chandigarh',
        complainant_state: 'Punjab',
        complainant_pincode: '160001',
        subject: 'Forced Eviction Without Notice',
        description: 'Local authorities are forcibly evicting families from our settlement without proper notice or rehabilitation. They are using bulldozers and threatening violence against anyone who resists.',
        category: 'civil_rights',
        subcategory: 'forced_eviction',
        priority: 'urgent',
        status: 'investigating',
        incident_date: '2024-02-10',
        incident_time: '06:00:00',
        incident_location: 'Slum Settlement Area',
        incident_address: 'Sector 25, Chandigarh',
        legal_action_required: true,
        compensation_sought: 2000000.00,
        follow_up_required: true,
        public_visibility: 'public'
    },
    {
        complainant_name: 'Anita Kumari',
        complainant_email: 'anita.kumari@email.com',
        complainant_phone: '+91-9876543219',
        complainant_address: '741 Fraser Road',
        complainant_city: 'Patna',
        complainant_state: 'Bihar',
        complainant_pincode: '800001',
        subject: 'Denial of Medical Treatment Due to Caste',
        description: 'Government hospital staff refused to treat my sick child because of our caste. They made us wait for hours and then asked us to go to a private hospital. This is discrimination in healthcare.',
        category: 'discrimination',
        subcategory: 'healthcare_discrimination',
        priority: 'high',
        status: 'closed',
        incident_date: '2024-01-05',
        incident_time: '14:00:00',
        incident_location: 'Government General Hospital',
        incident_address: 'Patna Medical College Road, Patna',
        legal_action_required: false,
        compensation_sought: 100000.00,
        follow_up_required: false,
        public_visibility: 'private',
        resolved_at: '2024-01-20 16:45:00'
    }
];

async function generateComplaintNumber() {
    const prefix = 'BHRC';
    const year = new Date().getFullYear();
    const month = String(new Date().getMonth() + 1).padStart(2, '0');
    const random = Math.floor(Math.random() * 9000) + 1000;
    return `${prefix}${year}${month}${random}`;
}

async function populateDatabase() {
    let connection;
    
    try {
        console.log('Connecting to database...');
        connection = await mysql.createConnection(dbConfig);
        console.log('Connected successfully!');
        
        // Insert sample complaints
        console.log('Inserting sample complaints...');
        for (let i = 0; i < sampleComplaints.length; i++) {
            const complaint = sampleComplaints[i];
            
            try {
                const complaintNumber = await generateComplaintNumber();
                
                const insertComplaintSql = `
                    INSERT INTO complaints (
                        complaint_number, complainant_name, complainant_email, complainant_phone,
                        complainant_address, complainant_city, complainant_state, complainant_pincode,
                        title, description, category, subcategory, priority, status,
                        incident_date, incident_time, incident_location, incident_address,
                        legal_action_required, compensation_sought, follow_up_required, 
                        public_visibility, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                `;
                
                await connection.execute(insertComplaintSql, [
                    complaintNumber,
                    complaint.complainant_name,
                    complaint.complainant_email,
                    complaint.complainant_phone,
                    complaint.complainant_address,
                    complaint.complainant_city,
                    complaint.complainant_state,
                    complaint.complainant_pincode,
                    complaint.subject,
                    complaint.description,
                    complaint.category,
                    complaint.subcategory,
                    complaint.priority,
                    complaint.status,
                    complaint.incident_date,
                    complaint.incident_time,
                    complaint.incident_location,
                    complaint.incident_address,
                    complaint.legal_action_required,
                    complaint.compensation_sought,
                    complaint.follow_up_required,
                    complaint.public_visibility
                ]);
                
                console.log(`✓ Complaint ${complaintNumber} inserted`);
                
            } catch (error) {
                console.log(`⚠ Error inserting complaint ${i + 1}:`, error.message);
            }
        }
        
        // Get final statistics
        const [complaintStats] = await connection.execute(`
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN status = 'submitted' THEN 1 END) as submitted,
                COUNT(CASE WHEN status = 'under_review' THEN 1 END) as under_review,
                COUNT(CASE WHEN status = 'investigating' THEN 1 END) as investigating,
                COUNT(CASE WHEN status = 'resolved' THEN 1 END) as resolved,
                COUNT(CASE WHEN status = 'closed' THEN 1 END) as closed
            FROM complaints
        `);
        
        console.log('\n📊 Database Population Complete!');
        console.log('=================================');
        console.log('Complaint Statistics:');
        console.log(`  Total: ${complaintStats[0].total}`);
        console.log(`  Submitted: ${complaintStats[0].submitted}`);
        console.log(`  Under Review: ${complaintStats[0].under_review}`);
        console.log(`  Investigating: ${complaintStats[0].investigating}`);
        console.log(`  Resolved: ${complaintStats[0].resolved}`);
        console.log(`  Closed: ${complaintStats[0].closed}`);
        console.log('\n✅ Sample data population completed successfully!');
        
    } catch (error) {
        console.error('❌ Error populating database:', error);
    } finally {
        if (connection) {
            await connection.end();
            console.log('Database connection closed.');
        }
    }
}

if (require.main === module) {
    populateDatabase();
}

module.exports = { populateDatabase };