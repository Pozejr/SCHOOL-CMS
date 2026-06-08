# School CMS — User Manual

## 1. Website Navigation

The public School CMS website is accessible to all visitors without requiring a login. The main navigation menu is located at the top of every page and provides links to all major sections.

### Navigation Menu
| Menu Item | URL | Description |
|-----------|-----|-------------|
| Home | `/` | School homepage with featured content |
| About | `/about` | About the school, history, mission |
| Academics | `/academics` | Academic programs and curriculum |
| Admissions | `/admissions` | Admissions process and requirements |
| News | `/news` | Latest news articles and announcements |
| Events | `/events` | Upcoming school events |
| Contact | `/contact` | Contact information and form |

### Mobile Navigation
On mobile devices, the navigation collapses into a hamburger menu (☰). Tap the icon to expand the menu.

## 2. Public Pages

### 2.1 Home Page (`/`)
The homepage displays:
- School branding and tagline
- Featured content sections
- Links to key areas of the website

### 2.2 About Page (`/about`)
Contains information about the school:
- History and background
- Vision and mission statements
- Core values
- Message from the principal

### 2.3 Academics Page (`/academics`)
Describes the school's academic offerings:
- Curriculum overview
- Academic levels (Early Years, Primary, Secondary)
- Extracurricular activities
- Academic achievements

### 2.4 Admissions Page (`/admissions`)
Provides admissions information:
- Why choose the school
- Admission process steps
- Requirements
- Fee structure
- Frequently asked questions

### 2.5 Contact Page (`/contact`)
Contact information:
- Physical address
- Phone number
- Email address
- Office hours
- Contact form (if enabled)

### 2.6 Dynamic Pages (`/page/:slug`)
Additional pages created through the CMS are accessible via their slug:
- `/page/about-us` — About Us page
- `/page/academics` — Academics page
- `/page/admissions` — Admissions page
- `/page/contact` — Contact page

## 3. News / Blog

### 3.1 News Listing (`/news`)
Browse all published news articles:
- Articles displayed in reverse chronological order (newest first)
- Each article shows: Title, Featured Image, Excerpt, Published Date
- Pagination at the bottom for older articles

### 3.2 Reading an Article (`/news/:slug`)
Click any article to view the full content:
- Full article title and content
- Featured image
- Publication date
- Author information

## 4. Events

### 4.1 Events Listing (`/events`)
View all upcoming and published events:
- Events displayed chronologically
- Each event shows: Title, Date, Venue, Description excerpt
- Featured images for visual appeal

### 4.2 Event Details
Click an event to see full details:
- Complete description
- Venue information
- Start and end dates/times
- Featured image

## 5. Search Functionality

The website supports content discovery through:
- **Navigation menu** — Direct access to main content sections
- **News listing** — Browse articles by date
- **Events listing** — Browse events chronologically
- **Dynamic pages** — Access any CMS page via its URL slug

## 6. Contact Forms

The Contact page (`/contact`) provides a way to reach the school. The contact section may include:
- Contact form with name, email, and message fields
- Office phone number
- Physical address
- Email address

## 7. Downloads

Documents uploaded through the admin File Manager are organized by type:
- **PDF documents** — Brochures, forms, prospectuses
- **Images** — Gallery images, school photos

Files can be linked from page content sections and are accessible at:
`http://your-domain.com/uploads/documents/filename.pdf`

## 8. Gallery

Gallery sections within pages display collections of images:
- Images are displayed in grid layout
- Each image may have a caption
- Images can be viewed full-size by clicking

Gallery images are managed through the admin Media Library and added to page sections as gallery URLs.

## 9. Accessibility

The website is designed to be accessible:
- Responsive design works on desktop, tablet, and mobile
- Semantic HTML structure
- Keyboard-navigable forms
- Clear visual hierarchy

## 10. Browser Compatibility

| Browser | Supported Versions |
|---------|-------------------|
| Chrome | 90+ |
| Firefox | 90+ |
| Safari | 14+ |
| Edge | 90+ |
| Mobile Safari | iOS 14+ |
| Mobile Chrome | Android 10+ |
