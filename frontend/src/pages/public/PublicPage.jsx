import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { pageService } from '../../services/pageService';
import Spinner from '../../components/common/Spinner';
import SectionRenderer from '../../components/common/SectionRenderer';
import { resolveImagePath } from '../../utils/helpers';

/**
 * Smart Public Page Renderer
 * 
 * Fetches a page by slug with its sections and SEO data.
 * Renders H1 from title, sections with automatic H2s,
 * parsed content, images, galleries, and videos.
 * 
 * The entire page is generated from JSON — zero manual HTML.
 */
export default function PublicPage({ slug: slugProp }) {
  const { slug } = useParams();
  const pageSlug = slugProp || slug;
  const [page, setPage] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadPage();
  }, [pageSlug]);

  const loadPage = async () => {
    setLoading(true);
    try {
      const result = await pageService.getBySlug(pageSlug);
      if (result.success) {
        setPage(result.data);
        // Update document title
        if (result.data?.seo?.meta_title) {
          document.title = result.data.seo.meta_title;
        }
        // Update meta description
        updateMetaTag('description', result.data?.seo?.meta_description);
        updateMetaProperty('og:title', result.data?.seo?.og_title);
        updateMetaProperty('og:description', result.data?.seo?.og_description);
        updateMetaProperty('og:image', result.data?.seo?.og_image);
      }
    } catch {}
    finally { setLoading(false); }
  };

  if (loading) return <Spinner size="lg" className="py-20" />;

  if (!page) return (
    <div className="py-20 text-center">
      <h1 className="text-2xl font-bold text-gray-900">Page Not Found</h1>
      <Link to="/" className="text-primary-600 mt-4 inline-block">← Go Home</Link>
    </div>
  );

  const seo = page.seo || {};
  const sections = page.sections || [];

  return (
    <div>
      {/* Hero: Page title auto-becomes H1 */}
      <section className="bg-gradient-to-br from-primary-800 to-primary-900 text-white py-16 md:py-20">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-4xl md:text-5xl font-bold mb-4">{page.title}</h1>
          {seo.meta_description && (
            <p className="text-lg text-primary-200 max-w-2xl">{seo.meta_description}</p>
          )}
        </div>
      </section>

      {/* Featured Image */}
      {page.featured_image && (
        <section className="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 -mt-8">
          <img
            src={resolveImagePath(page.featured_image)}
            alt={page.title}
            className="w-full h-64 md:h-96 object-cover rounded-2xl shadow-xl"
          />
        </section>
      )}

      {/* Sections */}
      {sections.length > 0 ? (
        <section className="py-12 md:py-16 space-y-16">
          {sections.map((section) => (
            <div key={section.id} className="px-4 sm:px-6 lg:px-8">
              <SectionRenderer section={section} />
            </div>
          ))}
        </section>
      ) : (
        /* Legacy fallback: if page has old-style content field */
        page.content && (
          <section className="py-12 max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="prose-content" dangerouslySetInnerHTML={{ __html: page.content }} />
          </section>
        )
      )}

      {/* Structured Data */}
      {seo.structured_data && (
        <script
          type="application/ld+json"
          dangerouslySetInnerHTML={{ __html: JSON.stringify(seo.structured_data) }}
        />
      )}
    </div>
  );
}

function updateMetaTag(name, content) {
  if (!content) return;
  let el = document.querySelector(`meta[name="${name}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute('name', name);
    document.head.appendChild(el);
  }
  el.setAttribute('content', content);
}

function updateMetaProperty(property, content) {
  if (!content) return;
  let el = document.querySelector(`meta[property="${property}"]`);
  if (!el) {
    el = document.createElement('meta');
    el.setAttribute('property', property);
    document.head.appendChild(el);
  }
  el.setAttribute('content', content);
}
