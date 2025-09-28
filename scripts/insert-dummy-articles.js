const db = require('../config/database');

async function insertDummyArticles() {
  try {
    console.log('Inserting dummy articles...');
    
    // Insert sample articles
    const articles = [
      {
        title: 'BHRC India Launches New Human Rights Initiative',
        slug: 'bhrc-new-initiative-2024',
        content: 'BHRC India has launched a comprehensive new initiative to promote human rights awareness across the country. This initiative focuses on education, legal aid, and community outreach programs.',
        excerpt: 'New initiative to promote human rights awareness nationwide',
        featured_image: '',
        category: 'news',
        status: 'published',
        author_id: 1,
        published_at: new Date()
      },
      {
        title: 'Legal Aid Program Reaches 1000 Beneficiaries',
        slug: 'legal-aid-1000-beneficiaries',
        content: 'Our legal aid program has successfully reached over 1000 beneficiaries across various states. The program provides free legal assistance to underprivileged communities.',
        excerpt: 'Legal aid program milestone achievement',
        featured_image: '',
        category: 'press_release',
        status: 'published',
        author_id: 1,
        published_at: new Date(Date.now() - 7 * 24 * 60 * 60 * 1000)
      },
      {
        title: 'Upcoming Workshop on Civil Rights',
        slug: 'upcoming-civil-rights-workshop',
        content: 'Join us for an informative workshop on civil rights and legal remedies. This workshop will cover fundamental rights, legal procedures, and how to seek justice.',
        excerpt: 'Workshop announcement for civil rights education',
        featured_image: '',
        category: 'announcement',
        status: 'published',
        author_id: 1,
        published_at: new Date(Date.now() - 3 * 24 * 60 * 60 * 1000)
      },
      {
        title: 'Draft Article: Human Rights in Digital Age',
        slug: 'draft-human-rights-digital-age',
        content: 'This is a draft article about human rights in the digital age. It covers topics like digital privacy, online freedom of expression, and cyber rights.',
        excerpt: 'Exploring human rights challenges in the digital era',
        featured_image: '',
        category: 'article',
        status: 'draft',
        author_id: 1,
        published_at: null
      },
      {
        title: 'Blog Post: Community Outreach Success',
        slug: 'blog-community-outreach-success',
        content: 'Our community outreach programs have been making a significant impact in rural areas. This blog post shares some success stories and lessons learned.',
        excerpt: 'Sharing success stories from our community outreach programs',
        featured_image: '',
        category: 'blog',
        status: 'published',
        author_id: 1,
        published_at: new Date(Date.now() - 1 * 24 * 60 * 60 * 1000)
      }
    ];

    for (const article of articles) {
      const query = `INSERT INTO news_articles (title, slug, content, excerpt, featured_image, category, status, author_id, published_at, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())`;
      await db.query(query, [
        article.title,
        article.slug,
        article.content,
        article.excerpt,
        article.featured_image,
        article.category,
        article.status,
        article.author_id,
        article.published_at
      ]);
      console.log(`Inserted: ${article.title}`);
    }
    
    console.log('✅ All dummy articles inserted successfully!');
    process.exit(0);
  } catch (error) {
    console.error('❌ Error inserting articles:', error.message);
    console.error('Stack trace:', error.stack);
    process.exit(1);
  }
}

insertDummyArticles();
