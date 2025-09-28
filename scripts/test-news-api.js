const db = require('../config/database');

async function testNewsAPI() {
  try {
    console.log('Testing news API endpoints...');
    
    // Test 1: Check if news_articles table exists and has data
    console.log('\n1. Testing news_articles table...');
    const countQuery = 'SELECT COUNT(*) as total FROM news_articles';
    const [countResult] = await db.query(countQuery);
    console.log('Total articles in database:', countResult.total);
    
    // Test 2: Test the stats query
    console.log('\n2. Testing stats query...');
    const statsQuery = `
      SELECT 
        COUNT(*) as total_articles,
        SUM(CASE WHEN status = 'published' THEN 1 ELSE 0 END) as published_articles,
        SUM(CASE WHEN status = 'draft' THEN 1 ELSE 0 END) as draft_articles,
        SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived_articles,
        SUM(CASE WHEN DATE(created_at) = CURDATE() THEN 1 ELSE 0 END) as today_articles,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as week_articles,
        SUM(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as month_articles
      FROM news_articles
    `;
    const [stats] = await db.query(statsQuery);
    console.log('Stats result:', stats);
    
    // Test 3: Test the admin news query
    console.log('\n3. Testing admin news query...');
    const adminQuery = `
      SELECT na.id, na.title, na.slug, na.excerpt, na.content, na.featured_image, 
             na.category, na.status, na.created_at, na.updated_at, na.author_id, 
             na.published_at, na.views_count,
             u.first_name, u.last_name, u.username
      FROM news_articles na
      LEFT JOIN users u ON na.author_id = u.id
      ORDER BY na.created_at DESC 
      LIMIT 5
    `;
    const articles = await db.query(adminQuery);
    console.log('Articles result:', articles.length, 'articles found');
    articles.forEach(article => {
      console.log(`- ${article.title} (${article.status}) by ${article.first_name || article.username || 'Unknown'}`);
    });
    
    console.log('\n✅ All tests passed!');
    process.exit(0);
    
  } catch (error) {
    console.error('❌ Test failed:', error.message);
    console.error('Stack trace:', error.stack);
    process.exit(1);
  }
}

testNewsAPI();
