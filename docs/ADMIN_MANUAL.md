# School CMS — Admin Manual

## 1. Login Process

### 1.1 Accessing the Admin Dashboard
1. Navigate to `http://your-domain.com/admin/login`
2. Enter your **Username** and **Password**
3. Click **Sign In**
4. Upon successful login, you will be redirected to the Dashboard

### 1.2 Default Credentials
| Field | Value |
|-------|-------|
| Username | `superadmin` |
| Password | `Admin@123` |

> **⚠️ Security Warning**: Change the default password immediately after first login.

### 1.3 Login Security
- Accounts are temporarily locked after **5 failed login attempts** for **30 minutes**
- Sessions expire after **2 hours** of inactivity (configurable via `SESSION_LIFETIME`)
- CSRF tokens are validated on all form submissions

### 1.4 Logging Out
Click your username in the sidebar or navigate away from admin. Use the logout functionality in the admin header.

## 2. Dashboard Usage

The Dashboard (`/admin`) provides an overview of your CMS:

### 2.1 Statistics Cards
- **Total Pages** — Count of all pages (published and draft)
- **Published Pages** — Pages visible to the public
- **Total News** — All news articles
- **Published News** — News visible to the public
- **Total Events** — All events
- **Upcoming Events** — Events with future dates
- **Total Users** — Registered admin users
- **Total Files** — Uploaded media files

### 2.2 Recent Activity Feed
Displays the latest actions performed in the CMS:
- User logins/logouts
- Page create/update/delete
- News create/update/delete
- Event create/update/delete
- File uploads

## 3. Content Management

### 3.1 Pages

#### Listing Pages
Navigate to **Pages** in the sidebar to see all pages with:
- Title, Slug, Status (Published/Draft), Last Updated date
- Quick actions: Edit, Delete

#### Creating a New Page
1. Click **"New Page"** button or navigate to **Pages → New Page**
2. Fill in the page details:
   - **Title** — Page title (required)
   - **Slug** — URL-friendly identifier (auto-generated from title)
   - **Status** — Published or Draft
   - **Meta Title** — SEO title tag
   - **Meta Description** — SEO description
   - **Featured Image** — Select from media library via Image Picker
3. Add sections using the **Section Builder** (see below)
4. Click **Save** to create the page

#### Editing a Page
1. Navigate to **Pages**
2. Click **Edit** on the desired page
3. Modify any fields
4. Click **Save** to update

#### Section-Based Page Builder
Pages are composed of multiple sections, each with its own type and layout:

**Section Types:**
| Type | Description |
|------|-------------|
| `text` | Rich text content with title and body |
| `gallery` | Image gallery with multiple images |
| `video` | Embedded video with URL |
| `quote` | Blockquote with attribution |
| `contact` | Contact form section |

**Layout Options:**
| Layout | Description |
|--------|-------------|
| `full` | Full-width content |
| `left` | Content aligned left with image right |
| `right` | Content aligned right with image left |
| `centered` | Centered content |

**Section Fields:**
- Title, Content, Image URL, Image Caption
- Video URL, Gallery URLs (JSON array), PDF URL
- Display Order (drag to reorder)

#### Applying Templates
1. When creating a new page, select a template from the template picker
2. Templates pre-populate the page with predefined sections
3. Available templates: About Page, Admissions Page, Academics Page, Contact Page, Department Page

#### Deleting Pages
1. Navigate to **Pages**
2. Click **Delete** on the desired page
3. Confirm the deletion in the dialog
4. Pages are soft-deleted (marked as deleted, not removed from database)

### 3.2 News Management

#### Creating a News Article
1. Navigate to **News → New Article**
2. Fill in the details:
   - **Title** (required)
   - **Slug** (auto-generated)
   - **Content** — Full article content
   - **Excerpt** — Short summary for listing pages
   - **Featured Image** — Select from media library
   - **Status** — Published or Draft
3. Click **Save**

#### Editing News
- Navigate to **News**, click **Edit** on the article
- Modify fields and save

#### Publishing
- Set status to **Published** to make the article visible on the public website
- Set to **Draft** to keep it hidden

### 3.3 Events Management

#### Creating an Event
1. Navigate to **Events → New Event**
2. Fill in:
   - **Title** (required)
   - **Slug** (auto-generated)
   - **Description** — Event details
   - **Venue** — Location
   - **Event Date** — Start date and time (required)
   - **End Date** — End date and time (optional)
   - **Featured Image** — Select from media library
   - **Status** — Published or Draft
3. Click **Save**

#### Upcoming Events
- Events with future dates and **Published** status appear on the public Events page

## 4. User Management

> **⚠️ Note**: User management is only available to **super_admin** users.

### 4.1 Viewing Users
Navigate to **Users** in the Administration section of the sidebar.

### 4.2 Creating a User
1. Click **"New User"**
2. Fill in:
   - First Name, Last Name (required)
   - Username (required, must be unique)
   - Email (required, must be unique)
   - Password (required for new users)
   - Role: Super Admin, Admin, or Editor
   - Status: Active or Inactive
3. Click **Create**

### 4.3 Editing a User
1. Click **Edit** on the user row
2. Modify fields (leave password blank to keep unchanged)
3. Click **Update**

### 4.4 Deleting a User
1. Click **Delete** on the user row
2. Confirm the deletion
3. **Note**: You cannot delete your own account

### 4.5 User Roles
| Role | Access Level |
|------|-------------|
| **super_admin** | Full system access: user management, system settings, all CMS features |
| **admin** | CMS management: pages, news, events, files, media |
| **editor** | Limited access: can authenticate but cannot access admin dashboard |

## 5. Media Management

### 5.1 File Manager
Navigate to **Files** to access the Media Library.

### 5.2 Uploading Files
1. Click the upload area or drag and drop a file
2. Supported image types: JPEG, PNG, GIF, WebP
3. Supported document types: PDF
4. Maximum file size: 10MB (images: 5MB)

### 5.3 Image Picker
When editing pages, news, or events, use the **Image Picker** component:
1. Click **"Select Image"** button
2. Browse the Media Library modal
3. Click an image to select it
4. The image URL is automatically populated in the field

### 5.4 Deleting Files
Click the delete button on any file in the File Manager. This removes the file from both the database and the filesystem.

## 6. Settings Management

System settings are stored in the `settings` database table and configured through environment variables. Key settings include:

| Setting | Description | Default |
|---------|-------------|---------|
| `site_name` | Website name | School CMS |
| `site_tagline` | Tagline | Excellence in Education |
| `site_email` | Contact email | info@schoolcms.com |
| `site_phone` | Phone number | +254 700 000 000 |
| `site_address` | Physical address | Nairobi, Kenya |
| `posts_per_page` | Items per page | 10 |
| `maintenance_mode` | Enable maintenance mode | false |

## 7. Role Management

### Role Hierarchy
```
super_admin (Level 3)
    └── Full system access
    └── User management
    └── System settings
    └── Security settings
    └── All CMS features

admin (Level 2)
    └── CMS management
    └── Pages, News, Events, Files
    └── Dashboard access
    └── Cannot manage users

editor (Level 1)
    └── Can authenticate
    └── Cannot access admin dashboard
    └── Redirected to Access Denied page
```

### Role Assignment
- Only **super_admin** can create users and assign roles
- Roles can be changed in the User Edit modal
- Downgrading a user's role takes effect immediately
