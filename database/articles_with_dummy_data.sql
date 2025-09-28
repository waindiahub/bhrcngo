-- BHRC India Articles Database with Dummy Data
-- Import this file into phpMyAdmin to get the articles table with sample data

-- Create news_articles table if it doesn't exist
CREATE TABLE IF NOT EXISTS news_articles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    excerpt TEXT,
    featured_image VARCHAR(255),
    category ENUM('news', 'press_release', 'article', 'announcement', 'blog') NOT NULL,
    status ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    author_id INT NOT NULL,
    published_at TIMESTAMP NULL,
    views_count INT DEFAULT 0,
    tags JSON,
    meta_description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_category (category),
    INDEX idx_published_at (published_at)
);

-- Clear existing articles (optional - remove this if you want to keep existing data)
DELETE FROM news_articles;

-- Insert dummy articles
INSERT INTO news_articles (title, slug, content, excerpt, featured_image, category, status, author_id, published_at, views_count, created_at, updated_at) VALUES
(
    'BHRC India Launches New Human Rights Initiative',
    'bhrc-new-initiative-2024',
    'BHRC India has launched a comprehensive new initiative to promote human rights awareness across the country. This initiative focuses on education, legal aid, and community outreach programs that aim to empower citizens with knowledge about their fundamental rights.\n\n## Key Features of the Initiative:\n\n- **Educational Workshops**: Regular workshops in schools and communities\n- **Legal Aid Services**: Free legal assistance for underprivileged communities\n- **Awareness Campaigns**: Digital and traditional media campaigns\n- **Community Outreach**: Direct engagement with rural and urban communities\n\nThis initiative represents a significant step forward in our mission to protect and promote human rights across India.',
    'New initiative to promote human rights awareness nationwide',
    '',
    'news',
    'published',
    1,
    NOW(),
    45,
    NOW(),
    NOW()
),
(
    'Legal Aid Program Reaches 1000 Beneficiaries',
    'legal-aid-1000-beneficiaries',
    'Our legal aid program has successfully reached over 1000 beneficiaries across various states. The program provides free legal assistance to underprivileged communities who cannot afford legal representation.\n\n## Program Impact:\n\n- **1000+ Beneficiaries**: Direct legal assistance provided\n- **15 States Covered**: Pan-India presence\n- **85% Success Rate**: Cases resolved in favor of beneficiaries\n- **₹50 Lakh Saved**: Legal fees saved for beneficiaries\n\n### Success Stories:\n\n1. **Land Rights Case**: Helped 50 families reclaim their ancestral land\n2. **Labor Rights**: Secured fair wages for 200 construction workers\n3. **Women\'s Rights**: Provided legal support to 150 domestic violence survivors\n\nThis milestone demonstrates our commitment to making justice accessible to all.',
    'Legal aid program milestone achievement',
    '',
    'press_release',
    'published',
    1,
    DATE_SUB(NOW(), INTERVAL 7 DAY),
    32,
    DATE_SUB(NOW(), INTERVAL 7 DAY),
    DATE_SUB(NOW(), INTERVAL 7 DAY)
),
(
    'Upcoming Workshop on Civil Rights',
    'upcoming-civil-rights-workshop',
    'Join us for an informative workshop on civil rights and legal remedies. This workshop will cover fundamental rights, legal procedures, and how to seek justice when your rights are violated.\n\n## Workshop Details:\n\n- **Date**: [To be announced]\n- **Time**: 10:00 AM - 4:00 PM\n- **Venue**: BHRC Community Center, New Delhi\n- **Registration**: Free (Limited seats available)\n\n### Topics Covered:\n\n1. **Fundamental Rights**: Understanding your constitutional rights\n2. **Legal Procedures**: How to file complaints and petitions\n3. **Court Processes**: Navigating the legal system\n4. **Documentation**: Required documents for legal cases\n5. **Case Studies**: Real examples of successful cases\n\n### Who Should Attend:\n\n- Students and young professionals\n- Community leaders\n- Social workers\n- Anyone interested in human rights\n\n**Registration**: Contact us at info@bhrcdata.online or call +91-11-12345678',
    'Workshop announcement for civil rights education',
    '',
    'announcement',
    'published',
    1,
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    28,
    DATE_SUB(NOW(), INTERVAL 3 DAY),
    DATE_SUB(NOW(), INTERVAL 3 DAY)
),
(
    'Draft Article: Human Rights in Digital Age',
    'draft-human-rights-digital-age',
    'This is a draft article about human rights in the digital age. It covers topics like digital privacy, online freedom of expression, and cyber rights.\n\n## Introduction\n\nAs we move deeper into the digital age, new challenges and opportunities arise for human rights protection. The internet has become a fundamental part of our daily lives, but it also presents unique challenges to traditional human rights frameworks.\n\n## Key Areas of Concern\n\n### Digital Privacy\n- Data protection and privacy rights\n- Surveillance and monitoring\n- Consent and data ownership\n\n### Online Freedom of Expression\n- Social media and free speech\n- Content moderation policies\n- Digital censorship\n\n### Cyber Rights\n- Access to information\n- Digital divide\n- Online harassment and cyberbullying\n\n## Conclusion\n\nThis article is still being developed and will be published soon with comprehensive analysis and recommendations.',
    'Exploring human rights challenges in the digital era',
    '',
    'article',
    'draft',
    1,
    NULL,
    0,
    NOW(),
    NOW()
),
(
    'Blog Post: Community Outreach Success',
    'blog-community-outreach-success',
    'Our community outreach programs have been making a significant impact in rural areas. This blog post shares some success stories and lessons learned from our field work.\n\n## Community Outreach Impact\n\nOver the past year, our community outreach team has visited 25 villages across 5 states, conducting awareness sessions and providing direct assistance to community members.\n\n### Success Stories\n\n#### Village Empowerment in Rajasthan\nIn a small village in Rajasthan, we helped establish a women\'s self-help group that now provides micro-finance services to 50 families. The group has successfully provided loans totaling ₹5 lakh.\n\n#### Education Initiative in Bihar\nOur education initiative in Bihar has helped 200 children return to school by providing scholarships and school supplies. The dropout rate in the target area has reduced by 60%.\n\n#### Health Awareness in Odisha\nHealth awareness camps in Odisha have reached 1000+ people, providing free health checkups and connecting them with government health schemes.\n\n## Lessons Learned\n\n1. **Community Engagement**: Direct community involvement is crucial for success\n2. **Local Partnerships**: Working with local organizations increases impact\n3. **Sustainability**: Programs must be designed for long-term sustainability\n4. **Cultural Sensitivity**: Understanding local culture and customs is essential\n\n## Future Plans\n\nWe plan to expand our outreach to 50 more villages in the next year, focusing on areas with limited access to legal and social services.',
    'Sharing success stories from our community outreach programs',
    '',
    'blog',
    'published',
    1,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    19,
    DATE_SUB(NOW(), INTERVAL 1 DAY),
    DATE_SUB(NOW(), INTERVAL 1 DAY)
);

-- Verify the data
SELECT 
    id, 
    title, 
    category, 
    status, 
    author_id, 
    created_at,
    views_count
FROM news_articles 
ORDER BY created_at DESC;
