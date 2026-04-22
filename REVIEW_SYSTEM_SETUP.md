# Review Management System - Setup Guide

This document provides a complete guide to the review management system implementation.

## Overview

The review management system allows users to:
- Create and manage literature reviews
- Upload and organize articles
- Invite team members to collaborate
- Manage member roles and permissions
- Track review status

## Database Schema

### Tables

#### reviews
- `id` - Primary key
- `user_id` - Foreign key to users (creator)
- `title` - Review title
- `description` - Review description
- `status` - enum: draft, active, completed, archived
- `created_at`, `updated_at` - Timestamps

#### articles
- `id` - Primary key
- `review_id` - Foreign key to reviews
- `title` - Article title
- `authors` - Article authors
- `abstract` - Article abstract
- `url` - Article URL
- `file_path` - Path to uploaded file
- `created_at`, `updated_at` - Timestamps

#### review_members
- `id` - Primary key
- `review_id` - Foreign key to reviews
- `user_id` - Foreign key to users
- `role` - enum: reviewer, coordinator, observer
- `invited_at` - When invitation was sent
- `accepted_at` - When member accepted
- `created_at`, `updated_at` - Timestamps

## Models

### Review
- Relationships:
  - `user()` - Creator of the review
  - `articles()` - Articles in the review
  - `members()` - Review members
  - `users()` - All users (via many-to-many)
- Methods:
  - `hasMember(User $user)` - Check if user is a member
  - `getMemberRole(User $user)` - Get user's role
  - `isCoordinator(User $user)` - Check if user is coordinator

### Article
- Relationships:
  - `review()` - Parent review

### ReviewMember
- Relationships:
  - `review()` - Parent review
  - `user()` - Member user
- Methods:
  - `hasAccepted()` - Check if member accepted
  - `accept()` - Mark as accepted

### User
- New relationships:
  - `reviews()` - Reviews created by user
  - `reviewMemberships()` - Reviews user is member of

## API Endpoints

### Reviews
- `POST /api/reviews` - Create review
- `GET /api/reviews` - List user's reviews
- `GET /api/reviews/{id}` - Get review details
- `PUT /api/reviews/{id}` - Update review
- `DELETE /api/reviews/{id}` - Delete review

### Articles
- `POST /api/reviews/{id}/articles` - Upload article
- `GET /api/reviews/{id}/articles` - List articles
- `DELETE /api/articles/{id}` - Delete article

### Members
- `POST /api/reviews/{id}/invite` - Invite member
- `GET /api/reviews/{id}/members` - List members
- `POST /api/reviews/{id}/accept` - Accept invitation
- `DELETE /api/reviews/{id}/members/{memberId}` - Remove member
- `PUT /api/reviews/{id}/members/{memberId}/role` - Update member role

## Frontend Components

### Pages
- `Reviews/Index.jsx` - List all reviews
- `Reviews/Show.jsx` - Review detail page with tabs

### Components
- `CreateReviewModal.jsx` - Modal to create new review
- `ReviewsList.jsx` - List of reviews with pagination
- `UploadArticleSection.jsx` - Upload article form
- `ArticlesList.jsx` - Display articles with delete option
- `InviteMemberSection.jsx` - Invite member form
- `MemberManagement.jsx` - Manage members and roles

## Authorization

### Review Access
- Creator can always access
- Members can access if invited and accepted
- Coordinators can manage review

### Article Management
- Creator and members can view
- Only coordinators can delete

### Member Management
- Only creator or coordinators can invite
- Only creator or coordinators can remove members
- Only creator or coordinators can update roles

## Setup Instructions

### 1. Run Migrations
```bash
php artisan migrate
```

This will create:
- reviews table
- articles table
- review_members table

### 2. Access the System

#### Create a Review
1. Navigate to `/reviews`
2. Click "Create Review"
3. Fill in title, description, and status
4. Click "Create Review"

#### Upload Articles
1. Go to review detail page
2. Click "Articles" tab
3. Click "Add Article"
4. Fill in article details (title required)
5. Optionally upload file or add URL
6. Click "Upload Article"

#### Invite Members
1. Go to review detail page
2. Click "Members" tab
3. Click "Invite Member"
4. Enter member email and select role
5. Click "Send Invite"

#### Manage Members
- View all members and their roles
- Edit member roles (coordinator only)
- Remove members (coordinator only)
- Members can accept invitations

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

## Testing the System

### Create Test Data
```bash
php artisan tinker
```

```php
$user = User::first();
$review = $user->reviews()->create([
    'title' => 'Test Review',
    'description' => 'A test review',
    'status' => 'active'
]);

$review->articles()->create([
    'title' => 'Test Article',
    'authors' => 'John Doe',
    'abstract' => 'Test abstract'
]);

$member = $review->members()->create([
    'user_id' => User::where('id', '!=', $user->id)->first()->id,
    'role' => 'reviewer',
    'invited_at' => now()
]);
```

## Performance Considerations

- Reviews are paginated (15 per page)
- Articles are paginated (20 per page)
- Members are paginated (20 per page)
- Relationships are eager loaded to prevent N+1 queries

## Future Enhancements

- Comments on articles
- Review templates
- Bulk article upload
- Export reviews to PDF
- Email notifications
- Review sharing with external users
- Article tagging and categorization
- Review analytics and statistics
