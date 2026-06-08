# PHASE 3 — SMART CONTENT MANAGEMENT SYSTEM ARCHITECTURE

## 1. SMART CONTENT ENGINE ARCHITECTURE

### Content Flow
```
Admin writes plain text
        ↓
Content Parser Engine (PHP)
        ↓
Plain text → Semantic HTML
        ↓
Stored as JSON sections in database
        ↓
API returns JSON to React
        ↓
React Section Renderer
        ↓
Professional page with H1, H2, paragraphs, lists, images
```

### Key Principle
The administrator NEVER writes HTML. They write plain text with simple conventions:
- Lines starting with `#` become headings
- Lines starting with `-` or `*` become bullet lists
- Lines starting with `1.` become numbered lists
- Lines starting with `>` become blockquotes
- Blank lines separate paragraphs
- A URL on its own line starting with `http` becomes an embedded link or image

### Content Parser Rules
```
Input (plain text by admin):
──────────────────────────────
Our school was founded in 1995 with a vision to transform education.

# Our Vision
To be the leading institution in academic excellence.

# Our Mission
- Provide quality education
- Nurture creative thinking
- Build strong character
- Foster innovation

# Core Values
1. Integrity
2. Excellence
3. Innovation
4. Discipline

> Education is the most powerful weapon which you can use to change the world. - Nelson Mandela
──────────────────────────────

Output (semantic HTML):
──────────────────────────────
<p>Our school was founded in 1995 with a vision to transform education.</p>

<h3>Our Vision</h3>
<p>To be the leading institution in academic excellence.</p>

<h3>Our Mission</h3>
<ul>
  <li>Provide quality education</li>
  <li>Nurture creative thinking</li>
  <li>Build strong character</li>
  <li>Foster innovation</li>
</ul>

<h3>Core Values</h3>
<ol>
  <li>Integrity</li>
  <li>Excellence</li>
  <li>Innovation</li>
  <li>Discipline</li>
</ol>

<blockquote>
  <p>Education is the most powerful weapon which you can use to change the world. - Nelson Mandela</p>
</blockquote>
──────────────────────────────
```

## 2. CONTENT PARSER DESIGN

### Parser Pipeline
```
Raw Text → Line Splitter → Line Classifier → Block Builder → HTML Generator
```

### Line Types
| Prefix | Type | Output |
|--------|------|--------|
| `# ` | Sub-heading | `<h3>` |
| `## ` | Sub-heading | `<h4>` |
| `- ` or `* ` | Bullet item | `<li>` in `<ul>` |
| `1.` `2.` etc. | Numbered item | `<li>` in `<ol>` |
| `> ` | Blockquote | `<blockquote><p>` |
| `---` | Divider | `<hr>` |
| `http...` (image ext) | Image | `<img>` |
| `http...` (other) | Link | `<a>` |
| Empty line | Paragraph break | `</p><p>` |
| Other text | Paragraph | `<p>` |

## 3. DATABASE CHANGES

### New Table: `page_sections`
```sql
CREATE TABLE page_sections (
    id BIGSERIAL PRIMARY KEY,
    page_id BIGINT NOT NULL REFERENCES pages(id) ON DELETE CASCADE,
    section_type VARCHAR(50) NOT NULL DEFAULT 'text',
    title VARCHAR(255) NOT NULL DEFAULT '',
    content TEXT DEFAULT '',
    image_url VARCHAR(500) DEFAULT NULL,
    image_caption VARCHAR(255) DEFAULT '',
    video_url VARCHAR(500) DEFAULT NULL,
    gallery_urls TEXT DEFAULT NULL,  -- JSON array of image URLs
    pdf_url VARCHAR(500) DEFAULT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    layout VARCHAR(50) NOT NULL DEFAULT 'full',  -- full, left, right, centered
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

### Modified pages table behavior
- `content` field becomes legacy/optional
- Content is now stored in `page_sections`
- Page title auto-generates H1
- Meta tags auto-generated from title + first section excerpt

### New Table: `page_templates`
```sql
CREATE TABLE page_templates (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT DEFAULT '',
    sections JSONB NOT NULL,  -- Template section definitions
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);
```

## 4. API CHANGES

### New Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /api/v1/pages/{id}/sections | Get all sections for a page |
| POST | /api/v1/pages/{id}/sections | Create a section |
| PUT | /api/v1/pages/sections/{id} | Update a section |
| DELETE | /api/v1/pages/sections/{id} | Delete a section |
| PUT | /api/v1/pages/{id}/sections/reorder | Reorder sections |
| GET | /api/v1/templates | List available templates |
| GET | /api/v1/templates/{slug} | Get template with section definitions |
| POST | /api/v1/pages/{id}/apply-template | Apply template to page |

### Modified Endpoints
| Method | Endpoint | Change |
|--------|----------|--------|
| GET | /api/v1/pages/slug/{slug} | Now includes sections in response |
| POST | /api/v1/pages | Accepts sections array |
| PUT | /api/v1/pages/{id} | Accepts sections array |

### New Response Format
```json
{
  "success": true,
  "data": {
    "id": 1,
    "title": "About Our School",
    "slug": "about-our-school",
    "meta_title": "About Our School — School CMS",
    "meta_description": "Learn about our school's history...",
    "featured_image": "/uploads/images/school.jpg",
    "status": "published",
    "sections": [
      {
        "id": 1,
        "section_type": "text",
        "title": "Our History",
        "content": "Founded in 1995...",
        "content_html": "<p>Founded in 1995...</p>",
        "image_url": null,
        "display_order": 1,
        "layout": "full"
      },
      {
        "id": 2,
        "section_type": "text",
        "title": "Core Values",
        "content": "- Integrity\n- Excellence\n- Innovation",
        "content_html": "<ul><li>Integrity</li><li>Excellence</li><li>Innovation</li></ul>",
        "image_url": "/uploads/images/values.jpg",
        "display_order": 2,
        "layout": "left"
      }
    ],
    "seo": {
      "h1": "About Our School",
      "meta_title": "About Our School — School CMS",
      "meta_description": "Founded in 1995 with a vision...",
      "og_title": "About Our School",
      "og_description": "Founded in 1995...",
      "og_image": "/uploads/images/school.jpg",
      "canonical_url": "/about-our-school"
    }
  }
}
```

## 5. REACT RENDERER ARCHITECTURE

### Component Hierarchy
```
PublicPage.jsx
  ├── <SEOHead> (auto-generated meta tags)
  ├── <PageHero> (title + featured image)
  └── <PageSections>
        └── maps each section → <SectionRenderer>
              ├── <TextSection>
              │     ├── <AutoH2> (from section title)
              │     └── <ContentHTML> (parsed content)
              ├── <ImageTextSection> (text + image layout)
              ├── <GallerySection>
              ├── <VideoSection>
              └── <QuoteSection>
```

### Smart Rendering Rules
- Page title → always rendered as `<h1>`
- Section titles → always rendered as `<h2>`
- Content is pre-parsed to HTML by backend
- Images auto-sized and responsive
- Layout options (full/left/right/centered) applied via Tailwind classes

## 6. PAGE BUILDER UI DESIGN

### Admin Interface
```
┌──────────────────────────────────────────────────┐
│ Page Builder                                      │
├──────────────────────────────────────────────────┤
│                                                   │
│ Page Title: [________________________]           │
│ Slug: [auto-generated___] Status: [Draft ▾]     │
│ Meta Description: [_____________________]        │
│ Featured Image: [Upload]                         │
│                                                   │
│ ┌──────────────────────────────────────────────┐ │
│ │ Section 1: Our History                 [↑↓✕] │ │
│ │ Title: [________________________]            │ │
│ │ Content:                                     │ │
│ │ ┌──────────────────────────────────────────┐ │ │
│ │ │ Write your content here.                 │ │ │
│ │ │                                          │ │ │
│ │ │ Use simple formatting:                  │ │ │
│ │ │ - Start with dash for bullet lists      │ │ │
│ │ │ 1. Start with number for numbered lists │ │ │
│ │ │ > Start with > for quotes               │ │ │
│ │ └──────────────────────────────────────────┘ │ │
│ │ Image: [Upload]  Layout: [Full Width ▾]     │ │
│ └──────────────────────────────────────────────┘ │
│                                                   │
│ ┌──────────────────────────────────────────────┐ │
│ │ Section 2: Core Values                [↑↓✕]  │ │
│ │ ...                                          │ │
│ └──────────────────────────────────────────────┘ │
│                                                   │
│ [+ Add Section]  [Apply Template ▾]              │
│                                                   │
│ [Save Draft]  [Publish]                          │
└──────────────────────────────────────────────────┘
```

## 7. MEDIA HANDLING DESIGN

### Section Media Types
| Type | Field | Rendering |
|------|-------|-----------|
| Image | `image_url` | Responsive `<img>` with caption |
| PDF | `pdf_url` | Download link button |
| Video | `video_url` | Embedded iframe (YouTube/Vimeo) |
| Gallery | `gallery_urls` | Grid of images with lightbox |

### Layout Options
| Layout | Description | Classes |
|--------|-------------|---------|
| `full` | Content full width, image below | Full width |
| `left` | Image left, text right | `md:grid-cols-2` |
| `right` | Text left, image right | `md:grid-cols-2` reversed |
| `centered` | Content centered with image above | `max-w-3xl mx-auto` |

## 8. SEO AUTOMATION STRATEGY

### Auto-Generated Fields
| Field | Source | Rule |
|-------|--------|------|
| `<h1>` | Page title | Always from title |
| `<title>` | Page title + site name | `{title} — {site_name}` |
| `<meta name="description">` | Meta description or first section excerpt | Max 160 chars |
| `<link rel="canonical">` | Page slug | `/{slug}` |
| `<og:title>` | Page title | Same as title |
| `<og:description>` | Meta description | Same as meta desc |
| `<og:image>` | Featured image or first section image | Auto-selected |
| `<og:type>` | Fixed | `website` |

### Structured Data (JSON-LD)
```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "About Our School",
  "description": "...",
  "image": "...",
  "url": "https://school.example.com/about-our-school"
}
```

## 9. JSON SCHEMA DESIGN

### Section JSON (stored in DB)
```json
{
  "id": 1,
  "page_id": 5,
  "section_type": "text",
  "title": "Core Values",
  "content": "- Integrity\n- Excellence\n- Innovation\n- Discipline",
  "image_url": null,
  "image_caption": "",
  "video_url": null,
  "gallery_urls": null,
  "pdf_url": null,
  "display_order": 2,
  "layout": "full"
}
```

### Template JSON (stored in DB)
```json
{
  "name": "About Page",
  "slug": "about",
  "sections": [
    { "title": "Our History", "type": "text", "layout": "full" },
    { "title": "Vision", "type": "text", "layout": "centered" },
    { "title": "Mission", "type": "text", "layout": "centered" },
    { "title": "Core Values", "type": "text", "layout": "left" },
    { "title": "Our Team", "type": "gallery", "layout": "full" },
    { "title": "Contact Information", "type": "text", "layout": "centered" }
  ]
}
```

## 10. CONTENT PUBLISHING WORKFLOW

```
1. Admin clicks "New Page" or selects a template
2. Admin fills in:
   - Page Title → auto-generates slug and H1
   - Meta Description → auto-generates if left empty
   - Featured Image → optional
3. Admin adds sections:
   - Clicks "Add Section" or template pre-populates
   - Fills in title (becomes H2) and plain text content
   - Optionally adds image, video, gallery, PDF
   - Chooses layout (full/left/right/centered)
4. Admin clicks "Save Draft" or "Publish"
5. On publish:
   - Backend parses content → generates HTML
   - Auto-generates SEO meta tags
   - Sets published_at timestamp
   - Page is live on public website
```

## 11. SECURITY CONSIDERATIONS

- Content parser must sanitize all output (prevent XSS)
- Only safe HTML tags in parsed output
- File uploads follow existing security rules
- Section reordering validated (prevent injection)
- Template application requires admin role
- Content stored as plain text, HTML generated on publish

## 12. TESTING STRATEGY

### Content Parser Tests
- Plain text → paragraphs
- Dash-prefixed lines → bullet lists
- Number-prefixed lines → ordered lists
- Quote-prefixed lines → blockquotes
- Heading-prefixed lines → h3/h4
- Mixed content → correct nesting
- Empty input → empty output
- XSS attempts → sanitized output

### API Tests
- CRUD for sections
- Reorder sections
- Apply template
- Page with sections response format
- SEO fields auto-generated

### Frontend Tests
- Section renderer for each section type
- Layout variations
- SEO head component
- Page builder form
- Template application
