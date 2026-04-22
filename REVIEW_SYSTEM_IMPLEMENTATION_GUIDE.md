# Review Management System - Complete Implementation Guide

## Project Overview

This is a complete review management system built with Laravel 13 backend and React/Inertia.js frontend. It allows users to create literature reviews, upload articles, and collaborate with team members.

## What's Included

### Backend (Laravel)
- ✅ 3 Database migrations (reviews, articles, review_members)
- ✅ 3 Eloquent models with relationships
- ✅ 3 API controllers with full CRUD operations
- ✅ 11 API endpoints for reviews, articles, and members
- ✅ Comprehensive authorization and validation
- ✅ 15 passing feature tests
- ✅ Database factories for testing
- ✅ Database seeder with sample data

### Frontend (React/Inertia.js)
- ✅ 2 Page components (Reviews list, Review detail)
- ✅ 6 Reusable components
- ✅ Complete form handling with validation
- ✅ Pagination support
- ✅ Tab-based interface
- ✅ Real-time updates

### Documentation
- ✅ API documentation with examples
- ✅ Setup guide
- ✅ Database schema documentation
- ✅ Authorization rules

## Quick Start

### 1. Run Migrations
```bash
php artisan migrate
```

This creates three tables:
- `reviews` - Main review records
- `articles` - Articles within reviews
- `review_members` - Team members and their roles

### 2. Seed Sample Data (Optional)
```bash
php artisan db:seed
```

This creates:
- 2 test users
- 1 sample review with articles and members

### 3. Run Tests
```bash
php artisan test
```

All 15 tests should pass, covering:
- Review CRUD operations
- Article management
- Member invitations and roles
- Authorization checks

### 4. Start Development Server
```bash
npm run dev
```

Then access the application at `http://localhost:8000`

## File Structure

```
backend/
├── app/
│   ├── Http/
│   │   └── Controllers/Api/
│   │       ├── ReviewController.php
│   │       ├── ArticleController.php
│   │       └── ReviewMemberController.php
│   └── Models/
│       ├── Review.php
│       ├── Article.php
│       └── ReviewMember.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_15_000001_create_reviews_table.php
│   │   ├── 2024_01_15_000002_create_articles_table.php
│   │   └── 2024_01_15_000003_create_review_members_table.php
│   ├── factories/
│   │   ├── ReviewFactory.php
│   │   ├── ArticleFactory.php
│   │   └── ReviewMemberFactory.php
│   └── seeders/
│       └── ReviewSeeder.php
├── resources/js/
│   ├── Pages/
│   │   └── Reviews/
│   │       ├── Index.jsx
│   │       └── Show.jsx
│   └── Components/
│       ├── CreateReviewModal.jsx
│       ├── ReviewsList.jsx
│       ├── UploadArticleSection.jsx
│       ├── ArticlesList.jsx
│       ├── InviteMemberSection.jsx
│       └── MemberManagement.jsx
├── routes/
│   ├── api.php
│   └── web.php
└── tests/
    └── Feature/
        └── ReviewSystemTest.php
```

## Database Schema

### reviews table
```sql
CREATE TABLE reviews (
  id BIGINT PRIMARY KEY,
  user_id BIGINT NOT NULL (FK: users),
  title VARCHAR(255) NOT NULL,
  description TEXT,
  status ENUM('draft', 'active', 'completed', 'archived') DEFAULT 'draft',
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### articles table
```sql
CREATE TABLE articles (
  id BIGINT PRIMARY KEY,
  review_id BIGINT NOT NULL (FK: reviews),
  title VARCHAR(255) NOT NULL,
  authors TEXT,
  abstract TEXT,
  url VARCHAR(255),
  file_path VARCHAR(255),
  created_at TIMESTAMP,
  updated_at TIMESTAMP
);
```

### review_members table
```sql
CREATE TABLE review_members (
  id BIGINT PRIMARY KEY,
  review_id BIGINT NOT NULL (FK: reviews),
  user_id BIGINT NOT NULL (FK: users),
  role ENUM('reviewer', 'coordinator', 'observer') DEFAULT 'reviewer',
  invited_at TIMESTAMP,
  accepted_at TIMESTAMP,
  created_at TIMESTAMP,
  updated_at TIMESTAMP,
  UNIQUE(review_id, user_id)
);
```

## API Endpoints

### Reviews
- `POST /api/reviews` - Create review
- `GET /api/reviews` - List user's reviews (paginated)
- `GET /api/reviews/{id}` - Get review details
- `PUT /api/reviews/{id}` - Update review
- `DELETE /api/reviews/{id}` - Delete review

### Articles
- `POST /api/reviews/{id}/articles` - Upload article
- `GET /api/reviews/{id}/articles` - List articles (paginated)
- `DELETE /api/articles/{id}` - Delete article

### Members
- `POST /api/reviews/{id}/invite` - Invite member
- `GET /api/reviews/{id}/members` - List members (paginated)
- `POST /api/reviews/{id}/accept` - Accept invitation
- `DELETE /api/reviews/{id}/members/{memberId}` - Remove member
- `PUT /api/reviews/{id}/members/{memberId}/role` - Update member role

## Authorization Rules

### Review Access
- **Creator**: Full access (read, update, delete)
- **Coordinator**: Can manage articles and members
- **Reviewer/Observer**: Can view and add articles

### Article Management
- **Creator/Coordinator**: Can delete articles
- **All members**: Can view and upload articles

### Member Management
- **Creator/Coordinator**: Can invite, remove, and update roles
- **Members**: Can accept invitations

## Key Features

### 1. Review Management
- Create reviews with title, description, and status
- Update review details
- Delete reviews (creator only)
- Track review status (draft, active, completed, archived)

### 2. Article Management
- Upload articles with metadata (title, authors, abstract)
- Attach files (PDF, DOC, DOCX) or URLs
- List articles with pagination
- Delete articles (coordinator only)

### 3. Team Collaboration
- Invite members by email
- Assign roles (reviewer, coordinator, observer)
- Track invitation status
- Update member roles
- Remove members

### 4. Pagination
- Reviews: 15 per page
- Articles: 20 per page
- Members: 20 per page

## Frontend Components

### Pages

#### Reviews/Index.jsx
- List all reviews
- Create new review button
- Pagination support

#### Reviews/Show.jsx
- Review details with tabs
- Overview tab: Review information
- Articles tab: Upload and manage articles
- Members tab: Invite and manage members

### Components

#### CreateReviewModal.jsx
- Modal form for creating reviews
- Title, description, status fields
- Form validation

#### ReviewsList.jsx
- Display reviews in list format
- Show review metadata
- Pagination controls

#### UploadArticleSection.jsx
- Form to upload articles
- Title, authors, abstract, URL, file upload
- File validation

#### ArticlesList.jsx
- Display articles in list format
- Show article metadata
- Delete button for coordinators

#### InviteMemberSection.jsx
- Form to invite members
- Email and role selection
- Only visible to coordinators

#### MemberManagement.jsx
- Display all members
- Show member roles
- Edit roles (coordinators only)
- Remove members (coordinators only)

## Validation Rules

### Review Creation
- `title` - Required, max 255 characters
- `description` - Optional, string
- `status` - Optional, one of: draft, active, completed, archived

### Article Upload
- `title` - Required, max 255 characters
- `authors` - Optional, string
- `abstract` - Optional, string
- `url` - Optional, valid URL
- `file` - Optional, PDF/DOC/DOCX, max 10MB

### Member Invitation
- `email` - Required, valid email, must exist in users table
- `role` - Required, one of: reviewer, coordinator, observer

## Error Handling

All API endpoints return appropriate HTTP status codes:
- `200` - Success
- `201` - Created
- `400` - Bad request (validation error)
- `403` - Unauthorized
- `404` - Not found
- `500` - Server error

Error responses include a message explaining the issue.

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test
```bash
php artisan test tests/Feature/ReviewSystemTest.php
```

### Test Coverage
- 15 tests covering all major functionality
- Tests for CRUD operations
- Tests for authorization
- Tests for validation

## Performance Considerations

- Relationships are eager loaded to prevent N+1 queries
- Pagination limits results to prevent large data transfers
- Indexes on foreign keys for fast lookups
- Unique constraint on (review_id, user_id) to prevent duplicate members

## Security Features

- Authentication required for all endpoints (except login/register)
- Authorization checks on all operations
- Input validation on all requests
- SQL injection prevention via Eloquent ORM
- CSRF protection via Sanctum

## File Upload Configuration

Files are stored in `storage/app/public/articles/`

To make files accessible:
```bash
php artisan storage:link
```

Supported file types:
- PDF (.pdf)
- Word (.doc, .docx)

Max file size: 10MB

## Future Enhancements

- Comments on articles
- Review templates
- Bulk article upload
- Export reviews to PDF
- Email notifications
- Review sharing with external users
- Article tagging and categorization
- Review analytics and statistics
- Advanced search and filtering
- Review versioning and history

## Troubleshooting

### Migrations not running
```bash
php artisan migrate:refresh
```

### Tests failing
```bash
php artisan test --no-coverage
```

### Storage link not working
```bash
php artisan storage:link
```

### Clear cache
```bash
php artisan cache:clear
php artisan config:clear
```

## Support

For issues or questions, refer to:
- `REVIEW_API_DOCUMENTATION.md` - API endpoint details
- `REVIEW_SYSTEM_SETUP.md` - Setup instructions
- `tests/Feature/ReviewSystemTest.php` - Test examples

## License

This project is part of the SRM (Systematic Review Management) system.
