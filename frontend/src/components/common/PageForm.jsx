import { useState, useEffect, useCallback } from 'react';
import { useNavigate } from 'react-router-dom';
import { useToast } from '../../contexts/ToastContext';
import { pageService } from '../../services/pageService';
import Button from '../../components/common/Button';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Spinner from '../../components/common/Spinner';
import ContentPreview from '../../components/common/ContentPreview';
import ImagePicker from '../../components/common/ImagePicker';

/**
 * PageForm — Shared Smart Page Builder
 *
 * Powers both Create Page and Edit Page screens.
 * Non-technical admins build pages by:
 *   1. Entering a title (becomes H1)
 *   2. Adding sections (each title becomes H2)
 *   3. Writing plain text content (auto-parsed to HTML on publish)
 *   4. Optionally adding images, choosing layout
 *   5. Publishing
 *
 * ZERO HTML knowledge required.
 *
 * Props:
 *   mode     — 'create' | 'edit'
 *   pageId   — (edit mode) the page ID to load
 */

const EMPTY_SECTION = () => ({
  id: 'new_' + Date.now(),
  section_type: 'text',
  title: '',
  content: '',
  image_url: '',
  image_caption: '',
  video_url: '',
  gallery_urls: [],
  layout: 'full',
  display_order: 0,
});

const EMPTY_PAGE = {
  title: '',
  slug: '',
  meta_description: '',
  featured_image: '',
  status: 'draft',
  sections: [],
};

export default function PageForm({ mode = 'create', pageId = null }) {
  const navigate = useNavigate();
  const toast = useToast();
  const isEdit = mode === 'edit';

  const [loading, setLoading] = useState(isEdit);
  const [saving, setSaving] = useState(false);
  const [templates, setTemplates] = useState([]);
  const [showTemplates, setShowTemplates] = useState(false);
  const [page, setPage] = useState({ ...EMPTY_PAGE });

  // ──────────────────────────────────────────────
  // Data loading
  // ──────────────────────────────────────────────
  useEffect(() => {
    loadTemplates();
    if (isEdit && pageId) loadPage();
  }, [pageId]);

  const loadTemplates = async () => {
    try {
      const result = await pageService.getTemplates();
      if (result.success) setTemplates(result.data);
    } catch {
      /* non-critical */
    }
  };

  const loadPage = async () => {
    setLoading(true);
    try {
      const result = await pageService.getById(pageId);
      if (!result.success) {
        toast.error('Page not found');
        navigate('/admin/pages');
        return;
      }
      const data = result.data;

      // Map API response to local state.
      // Sections come as raw structured data (no content_html).
      const sections = (data.sections || []).map((s, i) => ({
        id: s.id,                       // numeric DB id — critical for updates
        section_type: s.section_type || 'text',
        title: s.title || '',
        content: s.content || '',       // raw plain text, NOT html
        image_url: s.image_url || '',
        image_caption: s.image_caption || '',
        video_url: s.video_url || '',
        gallery_urls: Array.isArray(s.gallery_urls) ? s.gallery_urls : [],
        layout: s.layout || 'full',
        display_order: i,
      }));

      setPage({
        title: data.title || '',
        slug: data.slug || '',
        meta_description: data.meta_description || '',
        featured_image: data.featured_image || '',
        status: data.status || 'draft',
        sections,
      });
    } catch {
      toast.error('Failed to load page');
      navigate('/admin/pages');
    } finally {
      setLoading(false);
    }
  };

  // ──────────────────────────────────────────────
  // Page-level field changes
  // ──────────────────────────────────────────────
  const handlePageChange = (field, value) => {
    setPage((prev) => {
      const next = { ...prev, [field]: value };
      // Auto-generate slug from title (only in create mode, or if slug is empty)
      if (field === 'title') {
        const slug = value
          .toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/[\s-]+/g, '-')
          .replace(/^-|-$/g, '');
        next.slug = slug;
      }
      return next;
    });
  };

  // ──────────────────────────────────────────────
  // Section management
  // ──────────────────────────────────────────────
  const addSection = () => {
    setPage((prev) => ({
      ...prev,
      sections: [
        ...prev.sections,
        {
          ...EMPTY_SECTION(),
          id: 'new_' + Date.now(),
          display_order: prev.sections.length,
        },
      ],
    }));
  };

  const updateSection = (index, field, value) => {
    setPage((prev) => {
      const sections = [...prev.sections];
      sections[index] = { ...sections[index], [field]: value };
      return { ...prev, sections };
    });
  };

  const removeSection = (index) => {
    setPage((prev) => ({
      ...prev,
      sections: prev.sections.filter((_, i) => i !== index),
    }));
  };

  const moveSection = (index, direction) => {
    const newIndex = index + direction;
    if (newIndex < 0 || newIndex >= page.sections.length) return;
    setPage((prev) => {
      const sections = [...prev.sections];
      [sections[index], sections[newIndex]] = [sections[newIndex], sections[index]];
      return { ...prev, sections };
    });
  };

  // ──────────────────────────────────────────────
  // Templates
  // ──────────────────────────────────────────────
  const applyTemplate = async (templateSlug) => {
    const template = templates.find((t) => t.slug === templateSlug);
    if (!template) return;

    setPage((prev) => ({
      ...prev,
      sections: (template.sections || []).map((s, i) => ({
        id: 'new_' + Date.now() + '_' + i,
        section_type: s.section_type || 'text',
        title: s.title || '',
        content: '',
        image_url: '',
        image_caption: '',
        video_url: '',
        gallery_urls: [],
        layout: s.layout || 'full',
        display_order: i,
      })),
    }));
    setShowTemplates(false);
    toast.success(`Template "${template.name}" applied`);
  };

  const applyTemplateToExisting = async (templateSlug) => {
    if (!isEdit || !pageId) return;
    try {
      const result = await pageService.applyTemplate(pageId, templateSlug);
      if (result.success) {
        toast.success('Template applied — reloading sections');
        loadPage(); // reload to get new DB-sections with real IDs
      }
    } catch (err) {
      toast.error('Failed to apply template');
    }
  };

  // ──────────────────────────────────────────────
  // Save / Publish
  // ──────────────────────────────────────────────
  const handleSave = async (status) => {
    if (!page.title.trim()) {
      toast.error('Page title is required');
      return;
    }

    setSaving(true);
    try {
      // Build the payload — send structured data only, never HTML
      const payload = {
        title: page.title,
        slug: page.slug,
        meta_description: page.meta_description,
        featured_image: page.featured_image,
        status,
        sections: page.sections.map((s, i) => ({
          id: s.id,
          section_type: s.section_type,
          title: s.title,
          content: s.content,
          image_url: s.image_url || null,
          image_caption: s.image_caption,
          video_url: s.video_url || null,
          gallery_urls: s.gallery_urls,
          layout: s.layout,
          display_order: i,
        })),
      };

      let result;
      if (isEdit) {
        result = await pageService.update(pageId, payload);
      } else {
        result = await pageService.create(payload);
      }

      if (result.success) {
        toast.success(
          status === 'published'
            ? isEdit
              ? 'Page updated and published!'
              : 'Page published!'
            : isEdit
            ? 'Draft updated!'
            : 'Draft saved!'
        );
        navigate('/admin/pages');
      } else {
        const msg =
          result.error?.message ||
          result.errors?.[0]?.message ||
          'Failed to save page';
        toast.error(msg);
      }
    } catch (err) {
      toast.error(
        err.response?.data?.error?.message || 'Failed to save page'
      );
    } finally {
      setSaving(false);
    }
  };

  // ──────────────────────────────────────────────
  // Loading state
  // ──────────────────────────────────────────────
  if (loading) {
    return <Spinner size="lg" className="py-20" />;
  }

  // ──────────────────────────────────────────────
  // Render
  // ──────────────────────────────────────────────
  return (
    <div className="max-w-5xl mx-auto space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">
            {isEdit ? 'Edit Page' : 'Create Page'}
          </h1>
          <p className="text-gray-500 text-sm mt-1">
            {isEdit
              ? 'Update your page sections, content, and settings.'
              : 'Add a title, sections with plain text, and publish. No HTML needed.'}
          </p>
        </div>
        <Button
          variant="secondary"
          onClick={() => navigate('/admin/pages')}
        >
          Cancel
        </Button>
      </div>

      {/* ── Page Settings ── */}
      <div className="card space-y-4">
        <h3 className="text-lg font-semibold text-gray-900">Page Settings</h3>

        <Input
          label="Page Title (becomes the main heading)"
          name="title"
          value={page.title}
          onChange={(e) => handlePageChange('title', e.target.value)}
          placeholder="e.g. About Our School"
          required
        />

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <Input
            label="URL Slug"
            name="slug"
            value={page.slug}
            onChange={(e) => handlePageChange('slug', e.target.value)}
            placeholder="auto-generated-from-title"
          />
          <ImagePicker
            label="Featured Image"
            value={page.featured_image}
            onChange={(val) => handlePageChange('featured_image', val)}
            placeholder="uploads/images/... or paste any URL"
          />
        </div>

        <div>
          <label className="label">
            Meta Description (for search engines)
          </label>
          <textarea
            name="meta_description"
            value={page.meta_description}
            onChange={(e) =>
              handlePageChange('meta_description', e.target.value)
            }
            rows={2}
            className="input"
            placeholder="Brief description for Google search results (auto-generated if empty)"
          />
        </div>

        {isEdit && page.status && (
          <div className="flex items-center gap-2 text-sm">
            <span className="text-gray-500">Current status:</span>
            <span
              className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                page.status === 'published'
                  ? 'bg-green-100 text-green-800'
                  : 'bg-yellow-100 text-yellow-800'
              }`}
            >
              {page.status === 'published' ? 'Published' : 'Draft'}
            </span>
          </div>
        )}
      </div>

      {/* ── Sections ── */}
      <div className="space-y-4">
        <div className="flex items-center justify-between">
          <h3 className="text-lg font-semibold text-gray-900">
            Page Sections
          </h3>
          <div className="flex items-center gap-2">
            <Button
              variant="outline"
              size="sm"
              onClick={() => setShowTemplates(!showTemplates)}
            >
              📋 Templates
            </Button>
            <Button size="sm" onClick={addSection}>
              + Add Section
            </Button>
          </div>
        </div>

        {/* Template picker */}
        {showTemplates && templates.length > 0 && (
          <div className="card">
            <p className="text-sm text-gray-500 mb-3">
              Pick a template to pre-populate sections. You can then fill in
              your content.
            </p>
            <div className="grid grid-cols-2 md:grid-cols-3 gap-3">
              {templates.map((t) => (
                <button
                  key={t.slug}
                  onClick={() =>
                    isEdit
                      ? applyTemplateToExisting(t.slug)
                      : applyTemplate(t.slug)
                  }
                  className="text-left p-3 border border-gray-200 rounded-lg hover:bg-primary-50 hover:border-primary-300 transition-colors"
                >
                  <p className="font-medium text-gray-900 text-sm">
                    {t.name}
                  </p>
                  <p className="text-xs text-gray-500 mt-1">
                    {t.sections?.length || 0} sections
                  </p>
                </button>
              ))}
            </div>
          </div>
        )}

        {/* Section list */}
        {page.sections.length === 0 ? (
          <div className="card text-center py-8">
            <p className="text-gray-500 mb-4">
              No sections yet. Add your first section or apply a template.
            </p>
            <div className="flex justify-center gap-3">
              <Button onClick={addSection}>+ Add Blank Section</Button>
            </div>
          </div>
        ) : (
          page.sections.map((section, index) => (
            <div
              key={section.id || index}
              className="card border-l-4 border-l-primary-400"
            >
              {/* Section header */}
              <div className="flex items-center justify-between mb-4">
                <span className="text-sm font-semibold text-primary-700">
                  Section {index + 1}
                  {section.title ? ` — ${section.title}` : ''}
                </span>
                <div className="flex items-center gap-1">
                  <button
                    onClick={() => moveSection(index, -1)}
                    disabled={index === 0}
                    className="p-1 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30"
                    title="Move up"
                  >
                    ↑
                  </button>
                  <button
                    onClick={() => moveSection(index, 1)}
                    disabled={index === page.sections.length - 1}
                    className="p-1 rounded text-gray-400 hover:text-gray-600 disabled:opacity-30"
                    title="Move down"
                  >
                    ↓
                  </button>
                  <button
                    onClick={() => removeSection(index)}
                    className="p-1 rounded text-red-400 hover:text-red-600 ml-2"
                    title="Remove section"
                  >
                    ✕
                  </button>
                </div>
              </div>

              <div className="space-y-4">
                {/* Title + Layout */}
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div className="md:col-span-2">
                    <Input
                      label="Section Title (becomes sub-heading)"
                      value={section.title}
                      onChange={(e) =>
                        updateSection(index, 'title', e.target.value)
                      }
                      placeholder="e.g. Our History, Vision, Core Values"
                    />
                  </div>
                  <Select
                    label="Layout"
                    value={section.layout}
                    onChange={(e) =>
                      updateSection(index, 'layout', e.target.value)
                    }
                    options={[
                      { value: 'full', label: 'Full Width' },
                      { value: 'centered', label: 'Centered' },
                      { value: 'left', label: 'Image Left' },
                      { value: 'right', label: 'Image Right' },
                    ]}
                  />
                </div>

                {/* Smart content editor with live preview */}
                <div>
                  <label className="label">
                    Content
                    <span className="ml-2 text-xs font-normal text-gray-400">
                      Use - for bullets, 1. for numbers, &gt; for quotes
                    </span>
                  </label>
                  <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    <textarea
                      value={section.content}
                      onChange={(e) =>
                        updateSection(index, 'content', e.target.value)
                      }
                      rows={8}
                      className="input font-mono text-sm"
                      placeholder={
                        'Write your content here.\n\nSimple rules:\n- Start with dash for bullet lists\n1. Start with number for numbered lists\n> Start with > for quotes\n# Start with # for sub-headings'
                      }
                    />
                    <div className="border border-gray-200 rounded-lg p-4 bg-gray-50">
                      <p className="text-xs font-medium text-gray-400 mb-2">
                        Preview
                      </p>
                      <ContentPreview content={section.content} />
                    </div>
                  </div>
                </div>

                {/* Media fields */}
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <ImagePicker
                    label="Section Image"
                    value={section.image_url}
                    onChange={(val) =>
                      updateSection(index, 'image_url', val)
                    }
                    placeholder="uploads/images/... or paste any URL"
                  />
                  <Input
                    label="Video URL (YouTube/Vimeo)"
                    value={section.video_url}
                    onChange={(e) =>
                      updateSection(index, 'video_url', e.target.value)
                    }
                    placeholder="https://youtube.com/watch?v=..."
                  />
                </div>

                {/* Image caption */}
                <Input
                  label="Image Caption"
                  value={section.image_caption}
                  onChange={(e) =>
                    updateSection(index, 'image_caption', e.target.value)
                  }
                  placeholder="Describe the image (optional)"
                />
              </div>
            </div>
          ))
        )}
      </div>

      {/* ── Formatting Help ── */}
      <details className="card">
        <summary className="cursor-pointer text-sm font-medium text-gray-700">
          📝 Formatting Help
        </summary>
        <div className="mt-3 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 text-sm">
          <div className="p-3 bg-gray-50 rounded-lg">
            <p className="font-medium text-gray-900">Paragraphs</p>
            <p className="text-gray-500">Just type normally. Blank lines create new paragraphs.</p>
          </div>
          <div className="p-3 bg-gray-50 rounded-lg">
            <p className="font-medium text-gray-900">Bullet List</p>
            <code className="text-xs">- First item</code><br/>
            <code className="text-xs">- Second item</code>
          </div>
          <div className="p-3 bg-gray-50 rounded-lg">
            <p className="font-medium text-gray-900">Numbered List</p>
            <code className="text-xs">1. First step</code><br/>
            <code className="text-xs">2. Second step</code>
          </div>
          <div className="p-3 bg-gray-50 rounded-lg">
            <p className="font-medium text-gray-900">Quote</p>
            <code className="text-xs">&gt; Famous quote here</code>
          </div>
          <div className="p-3 bg-gray-50 rounded-lg">
            <p className="font-medium text-gray-900">Sub-heading</p>
            <code className="text-xs"># Sub-heading text</code>
          </div>
          <div className="p-3 bg-gray-50 rounded-lg">
            <p className="font-medium text-gray-900">Text Formatting</p>
            <code className="text-xs">**bold** *italic* `code`</code>
          </div>
        </div>
      </details>

      {/* ── Actions ── */}
      <div className="flex items-center justify-end gap-3 pt-4 border-t">
        <Button
          variant="secondary"
          onClick={() => handleSave('draft')}
          loading={saving}
        >
          {isEdit ? 'Save Draft' : 'Save Draft'}
        </Button>
        <Button
          onClick={() => handleSave('published')}
          loading={saving}
        >
          {isEdit ? 'Update & Publish' : 'Publish Page'}
        </Button>
      </div>
    </div>
  );
}
