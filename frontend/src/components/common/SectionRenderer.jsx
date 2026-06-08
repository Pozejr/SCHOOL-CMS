import React from 'react';
import { resolveImagePath } from '../../utils/helpers';

/**
 * Smart Section Renderer
 * 
 * Takes a section object from the API and renders it
 * with automatic typography, layout, and media placement.
 * 
 * The admin NEVER writes HTML — the backend ContentParser
 * converts plain text to semantic HTML. This component
 * simply renders that HTML with proper styling.
 */

export default function SectionRenderer({ section }) {
  const { section_type, title, content_html, image_url, image_caption, video_url, gallery_urls, layout } = section;

  const layoutClass = {
    full: 'max-w-4xl mx-auto',
    left: 'max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-start',
    right: 'max-w-5xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-8 items-start',
    centered: 'max-w-3xl mx-auto text-center',
  }[layout] || 'max-w-4xl mx-auto';

  const renderContent = () => (
    <div>
      {/* Section title auto-becomes H2 */}
      {title && (
        <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-4">{title}</h2>
      )}
      {/* Parsed HTML content (already safe semantic HTML from backend) */}
      {content_html && (
        <div className="prose-content text-gray-700 leading-relaxed" dangerouslySetInnerHTML={{ __html: content_html }} />
      )}
    </div>
  );

  const renderImage = () => {
    if (!image_url) return null;
    const imgSrc = resolveImagePath(image_url);
    return (
      <figure className="flex-shrink-0">
        <img
          src={imgSrc}
          alt={image_caption || title || 'Section image'}
          className="w-full rounded-xl shadow-sm object-cover"
          loading="lazy"
        />
        {image_caption && (
          <figcaption className="text-sm text-gray-500 mt-2 text-center">{image_caption}</figcaption>
        )}
      </figure>
    );
  };

  const renderVideo = () => {
    if (!video_url) return null;
    const embedUrl = getEmbedUrl(video_url);
    return (
      <div className="aspect-video rounded-xl overflow-hidden shadow-sm">
        <iframe
          src={embedUrl}
          title={title || 'Video'}
          className="w-full h-full"
          allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
          allowFullScreen
        />
      </div>
    );
  };

  // Render based on layout
  if (section_type === 'gallery' && gallery_urls?.length > 0) {
    return (
      <section className={layoutClass}>
        {title && <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6">{title}</h2>}
        <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
          {gallery_urls.map((url, i) => (
            <img key={i} src={resolveImagePath(url)} alt={`Gallery image ${i + 1}`} className="w-full h-40 object-cover rounded-lg shadow-sm" loading="lazy" />
          ))}
        </div>
      </section>
    );
  }

  if (section_type === 'video' && video_url) {
    return (
      <section className={layoutClass}>
        {title && <h2 className="text-2xl md:text-3xl font-bold text-gray-900 mb-6">{title}</h2>}
        {renderVideo()}
      </section>
    );
  }

  // Text section with layout variants
  if (layout === 'left' && image_url) {
    return (
      <section className={layoutClass}>
        <div className="order-2 md:order-1">{renderImage()}</div>
        <div className="order-1 md:order-2">{renderContent()}</div>
      </section>
    );
  }

  if (layout === 'right' && image_url) {
    return (
      <section className={layoutClass}>
        <div>{renderContent()}</div>
        <div>{renderImage()}</div>
      </section>
    );
  }

  // Default: full or centered
  return (
    <section className={layoutClass}>
      {renderContent()}
      {image_url && <div className="mt-6">{renderImage()}</div>}
      {video_url && <div className="mt-6">{renderVideo()}</div>}
    </section>
  );
}

function getEmbedUrl(url) {
  if (url.includes('youtube.com/watch')) {
    const id = new URL(url).searchParams.get('v');
    return `https://www.youtube.com/embed/${id}`;
  }
  if (url.includes('youtu.be/')) {
    const id = url.split('youtu.be/')[1]?.split('?')[0];
    return `https://www.youtube.com/embed/${id}`;
  }
  if (url.includes('vimeo.com/')) {
    const id = url.split('vimeo.com/')[1]?.split('?')[0];
    return `https://player.vimeo.com/video/${id}`;
  }
  return url;
}
