# Review Management System - API Documentation

## Base URL
```
/api
```

## Authentication
All endpoints (except login/register) require authentication via Sanctum token.

Include the token in the Authorization header:
```
Authorization: Bearer {token}
```

## Response Format

All responses are JSON with the following structure:

### Success Response
```json
{
  "message": "Operation successful",
  "data": { /* response data */ }
}
```

### Error Response
```json
{
  "message": "Error description",
  "errors": { /* validation errors */ }
}
```

## Endpoints

### Authentication

#### Register
```
POST /register
```

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

**Response:** `201 Created`
```json
{
  "message": "User registered successfully",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "token": "1|abc123..."
  }
}
```

#### Login
```
POST /login
```

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response:** `200 OK`
```json
{
  "message": "Login successful",
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com",
    "token": "1|abc123..."
  }
}
```

#### Get Current User
```
GET /user
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "name": "John Doe",
  "email": "john@example.com",
  "role": "user"
}
```

#### Logout
```
POST /logout
```

**Response:** `200 OK`
```json
{
  "message": "Logged out successfully"
}
```

---

### Reviews

#### Create Review
```
POST /reviews
```

**Request:**
```json
{
  "title": "Machine Learning Review",
  "description": "A comprehensive review of ML techniques",
  "status": "draft"
}
```

**Validation:**
- `title` - Required, max 255 characters
- `description` - Optional, string
- `status` - Optional, one of: draft, active, completed, archived

**Response:** `201 Created`
```json
{
  "message": "Review created successfully",
  "data": {
    "id": 1,
    "user_id": 1,
    "title": "Machine Learning Review",
    "description": "A comprehensive review of ML techniques",
    "status": "draft",
    "created_at": "2024-01-15T10:00:00Z",
    "updated_at": "2024-01-15T10:00:00Z"
  }
}
```

#### List Reviews
```
GET /reviews?page=1
```

**Query Parameters:**
- `page` - Page number (default: 1)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "user_id": 1,
      "title": "Machine Learning Review",
      "description": "A comprehensive review of ML techniques",
      "status": "draft",
      "user": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com"
      },
      "articles": [],
      "members": [],
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 1,
  "total": 1
}
```

#### Get Review Details
```
GET /reviews/{id}
```

**Response:** `200 OK`
```json
{
  "id": 1,
  "user_id": 1,
  "title": "Machine Learning Review",
  "description": "A comprehensive review of ML techniques",
  "status": "draft",
  "user": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  },
  "articles": [
    {
      "id": 1,
      "review_id": 1,
      "title": "Deep Learning Basics",
      "authors": "Smith, J.",
      "abstract": "An introduction to deep learning",
      "url": "https://example.com/article1",
      "file_path": null,
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z"
    }
  ],
  "members": [
    {
      "id": 1,
      "review_id": 1,
      "user_id": 2,
      "role": "reviewer",
      "invited_at": "2024-01-15T10:00:00Z",
      "accepted_at": "2024-01-15T10:05:00Z",
      "user": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com"
      }
    }
  ],
  "created_at": "2024-01-15T10:00:00Z",
  "updated_at": "2024-01-15T10:00:00Z"
}
```

**Errors:**
- `403 Unauthorized` - User doesn't have access to this review
- `404 Not Found` - Review doesn't exist

#### Update Review
```
PUT /reviews/{id}
```

**Request:**
```json
{
  "title": "Updated Title",
  "description": "Updated description",
  "status": "active"
}
```

**Authorization:** Creator or coordinator only

**Response:** `200 OK`
```json
{
  "message": "Review updated successfully",
  "data": { /* updated review */ }
}
```

**Errors:**
- `403 Unauthorized` - User is not coordinator
- `404 Not Found` - Review doesn't exist

#### Delete Review
```
DELETE /reviews/{id}
```

**Authorization:** Creator only

**Response:** `200 OK`
```json
{
  "message": "Review deleted successfully"
}
```

**Errors:**
- `403 Unauthorized` - User is not creator
- `404 Not Found` - Review doesn't exist

---

### Articles

#### Upload Article
```
POST /reviews/{id}/articles
```

**Request (multipart/form-data):**
```
title: "Deep Learning for Medical Imaging"
authors: "Smith, J., Johnson, M."
abstract: "A comprehensive study of deep learning in medical imaging"
url: "https://example.com/article"
file: <binary file data>
```

**Validation:**
- `title` - Required, max 255 characters
- `authors` - Optional, string
- `abstract` - Optional, string
- `url` - Optional, valid URL
- `file` - Optional, PDF/DOC/DOCX, max 10MB

**Response:** `201 Created`
```json
{
  "message": "Article uploaded successfully",
  "data": {
    "id": 1,
    "review_id": 1,
    "title": "Deep Learning for Medical Imaging",
    "authors": "Smith, J., Johnson, M.",
    "abstract": "A comprehensive study of deep learning in medical imaging",
    "url": "https://example.com/article",
    "file_path": "articles/abc123.pdf",
    "created_at": "2024-01-15T10:00:00Z",
    "updated_at": "2024-01-15T10:00:00Z"
  }
}
```

**Errors:**
- `403 Unauthorized` - User doesn't have access to this review
- `404 Not Found` - Review doesn't exist
- `422 Unprocessable Entity` - Validation error

#### List Articles
```
GET /reviews/{id}/articles?page=1
```

**Query Parameters:**
- `page` - Page number (default: 1)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "review_id": 1,
      "title": "Deep Learning for Medical Imaging",
      "authors": "Smith, J., Johnson, M.",
      "abstract": "A comprehensive study of deep learning in medical imaging",
      "url": "https://example.com/article",
      "file_path": "articles/abc123.pdf",
      "created_at": "2024-01-15T10:00:00Z",
      "updated_at": "2024-01-15T10:00:00Z"
    }
  ],
  "current_page": 1,
  "last_page": 1,
  "total": 1
}
```

**Errors:**
- `403 Unauthorized` - User doesn't have access to this review
- `404 Not Found` - Review doesn't exist

#### Delete Article
```
DELETE /articles/{id}
```

**Authorization:** Coordinator only

**Response:** `200 OK`
```json
{
  "message": "Article deleted successfully"
}
```

**Errors:**
- `403 Unauthorized` - User is not coordinator
- `404 Not Found` - Article doesn't exist

---

### Review Members

#### Invite Member
```
POST /reviews/{id}/invite
```

**Request:**
```json
{
  "email": "reviewer@example.com",
  "role": "reviewer"
}
```

**Validation:**
- `email` - Required, valid email, must exist in users table
- `role` - Required, one of: reviewer, coordinator, observer

**Authorization:** Creator or coordinator only

**Response:** `201 Created`
```json
{
  "message": "Member invited successfully",
  "data": {
    "id": 1,
    "review_id": 1,
    "user_id": 2,
    "role": "reviewer",
    "invited_at": "2024-01-15T10:00:00Z",
    "accepted_at": null,
    "user": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com"
    }
  }
}
```

**Errors:**
- `400 Bad Request` - User already a member or is the creator
- `403 Unauthorized` - User is not coordinator
- `404 Not Found` - Review doesn't exist
- `422 Unprocessable Entity` - Validation error

#### List Members
```
GET /reviews/{id}/members?page=1
```

**Query Parameters:**
- `page` - Page number (default: 1)

**Response:** `200 OK`
```json
{
  "data": [
    {
      "id": 1,
      "review_id": 1,
      "user_id": 2,
      "role": "reviewer",
      "invited_at": "2024-01-15T10:00:00Z",
      "accepted_at": "2024-01-15T10:05:00Z",
      "user": {
        "id": 2,
        "name": "Jane Doe",
        "email": "jane@example.com"
      }
    }
  ],
  "current_page": 1,
  "last_page": 1,
  "total": 1
}
```

**Errors:**
- `403 Unauthorized` - User doesn't have access to this review
- `404 Not Found` - Review doesn't exist

#### Accept Invitation
```
POST /reviews/{id}/accept
```

**Response:** `200 OK`
```json
{
  "message": "Invitation accepted",
  "data": {
    "id": 1,
    "review_id": 1,
    "user_id": 2,
    "role": "reviewer",
    "invited_at": "2024-01-15T10:00:00Z",
    "accepted_at": "2024-01-15T10:05:00Z",
    "user": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com"
    }
  }
}
```

**Errors:**
- `404 Not Found` - User not invited to this review

#### Remove Member
```
DELETE /reviews/{id}/members/{memberId}
```

**Authorization:** Creator or coordinator only

**Response:** `200 OK`
```json
{
  "message": "Member removed successfully"
}
```

**Errors:**
- `400 Bad Request` - Cannot remove the review creator
- `403 Unauthorized` - User is not coordinator
- `404 Not Found` - Member not found in this review

#### Update Member Role
```
PUT /reviews/{id}/members/{memberId}/role
```

**Request:**
```json
{
  "role": "coordinator"
}
```

**Validation:**
- `role` - Required, one of: reviewer, coordinator, observer

**Authorization:** Creator or coordinator only

**Response:** `200 OK`
```json
{
  "message": "Member role updated successfully",
  "data": {
    "id": 1,
    "review_id": 1,
    "user_id": 2,
    "role": "coordinator",
    "invited_at": "2024-01-15T10:00:00Z",
    "accepted_at": "2024-01-15T10:05:00Z",
    "user": {
      "id": 2,
      "name": "Jane Doe",
      "email": "jane@example.com"
    }
  }
}
```

**Errors:**
- `403 Unauthorized` - User is not coordinator
- `404 Not Found` - Member not found in this review
- `422 Unprocessable Entity` - Validation error

---

## HTTP Status Codes

| Code | Meaning |
|------|---------|
| 200 | OK - Request successful |
| 201 | Created - Resource created successfully |
| 400 | Bad Request - Invalid request data |
| 403 | Forbidden - User doesn't have permission |
| 404 | Not Found - Resource doesn't exist |
| 422 | Unprocessable Entity - Validation error |
| 500 | Internal Server Error - Server error |

---

## Rate Limiting

Currently no rate limiting is implemented. This should be added for production.

---

## Pagination

List endpoints support pagination with the following parameters:

- `page` - Page number (default: 1)
- `per_page` - Items per page (default: varies by endpoint)

Response includes:
- `data` - Array of items
- `current_page` - Current page number
- `last_page` - Last page number
- `total` - Total number of items

---

## Examples

### Create a Review and Add Articles

```bash
# 1. Create review
curl -X POST http://localhost:8000/api/reviews \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "ML Review",
    "description": "Machine Learning Review",
    "status": "active"
  }'

# 2. Upload article
curl -X POST http://localhost:8000/api/reviews/1/articles \
  -H "Authorization: Bearer {token}" \
  -F "title=Deep Learning" \
  -F "authors=Smith, J." \
  -F "abstract=A study of deep learning" \
  -F "file=@article.pdf"

# 3. Invite member
curl -X POST http://localhost:8000/api/reviews/1/invite \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "reviewer@example.com",
    "role": "reviewer"
  }'
```

### Get Review with All Details

```bash
curl -X GET http://localhost:8000/api/reviews/1 \
  -H "Authorization: Bearer {token}"
```

This will return the review with all articles and members included.
