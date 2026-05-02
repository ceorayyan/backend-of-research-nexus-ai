# Multi-Decision Screening Implementation

## Overview
Implemented support for multiple independent screening decisions per article (one per user), replacing the previous single-decision model.

## Database Changes

### New Table: `article_screenings`
- Stores one row per user per article
- Columns: `id`, `article_id`, `user_id`, `decision`, `notes`, `labels`, `exclusion_reasons`, `timestamps`
- Unique constraint on `(article_id, user_id)` to ensure one decision per user per article

### Migration
- Created migration to create `article_screenings` table
- Created data migration to move existing screening data from `articles` table to `article_screenings` table
- Migrated 23 existing screenings successfully

## Model Changes

### ArticleScreening Model
- Added fillable fields: `article_id`, `user_id`, `decision`, `notes`, `labels`, `exclusion_reasons`
- Added casts for `labels` and `exclusion_reasons` (array)
- Added relationships: `article()`, `user()`

### Article Model
- Added relationship: `screenings()` - hasMany ArticleScreening

## Controller Changes

### ArticleController::index()
**Behavior:**
- Loads articles with `screenings` relationship
- If blind mode ON: Only loads current user's screenings
- If blind mode OFF: Loads all users' screenings with user names
- Returns `screenings` array for each article
- **Backward compatibility**: Also provides top-level fields (`screening_decision`, `screening_decision_by`, etc.) showing current user's decision

**Response format:**
```json
{
  "id": 1,
  "title": "Article Title",
  "screening_decision": "included",  // Current user's decision (backward compatible)
  "screening_decision_by": "rayyan",  // Current user's name (backward compatible)
  "screenings": [  // NEW: Array of all screenings
    {
      "user_id": 1,
      "user_name": "rayyan",  // null if blind mode ON
      "decision": "included",
      "notes": "Good article",
      "labels": ["Relevant"],
      "exclusion_reasons": null
    },
    {
      "user_id": 2,
      "user_name": "rayyan2",  // null if blind mode ON
      "decision": "excluded",
      "notes": "Not relevant",
      "labels": null,
      "exclusion_reasons": ["Wrong Population"]
    }
  ]
}
```

### ArticleController::updateScreening()
**Behavior:**
- Uses `ArticleScreening::updateOrCreate()` to save to `article_screenings` table
- Creates or updates the screening for the current user
- Returns the updated screening with user info

### ArticleController::getScreeningStats()
**Behavior:**
- If blind mode ON: Counts only current user's screenings
- If blind mode OFF: Counts all screenings (with distinct article count for "screened")

## Blind Mode Behavior

### When Blind Mode is ON:
- Each user sees ALL articles
- Each user sees ONLY their own screening decisions
- `screenings` array contains only current user's screening
- Top-level fields show current user's decision

### When Blind Mode is OFF:
- Each user sees ALL articles
- Each user sees ALL users' screening decisions with usernames
- `screenings` array contains all users' screenings
- Top-level fields show current user's decision

## Frontend Compatibility

The implementation maintains backward compatibility with the existing frontend:
- Top-level fields (`screening_decision`, `screening_decision_by`, etc.) continue to work
- Frontend can be enhanced later to display multiple decision badges using the `screenings` array

## Next Steps (Future Enhancement)

1. Update frontend to display multiple decision badges per article when blind mode is OFF
2. Show "included by rayyan" and "excluded by rayyan2" badges side-by-side
3. Add visual indicators for conflicting decisions
4. Implement full-text screening with the same multi-decision model

## Testing

To test the implementation:
1. Login as rayyan (owner)
2. Screen some articles
3. Login as rayyan2 (reviewer)
4. Screen the same articles with different decisions
5. Toggle blind mode ON/OFF and verify:
   - Blind mode ON: Each user sees only their own decisions
   - Blind mode OFF: Each user sees all decisions with usernames
