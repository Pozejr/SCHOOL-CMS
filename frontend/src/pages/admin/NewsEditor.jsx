import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { newsService } from '../../services/newsService';
import { useToast } from '../../contexts/ToastContext';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Button from '../../components/common/Button';
import Spinner from '../../components/common/Spinner';
import ImagePicker from '../../components/common/ImagePicker';

export default function NewsEditor() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const isEditing = !!id;

  const [loading, setLoading] = useState(isEditing);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({
    title: '',
    content: '',
    excerpt: '',
    status: 'draft',
    featured_image: '',
  });

  useEffect(() => { if (isEditing) loadArticle(); }, [id]);

  const loadArticle = async () => {
    try {
      const result = await newsService.getById(id);
      if (result.success) {
        const a = result.data;
        setForm({ title: a.title || '', content: a.content || '', excerpt: a.excerpt || '', status: a.status || 'draft', featured_image: a.featured_image || '' });
      }
    } catch { toast.error('Failed to load article'); navigate('/admin/news'); }
    finally { setLoading(false); }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.title.trim()) { toast.error('Title is required'); return; }
    setSaving(true);
    try {
      const result = isEditing ? await newsService.update(id, form) : await newsService.create(form);
      if (result.success) {
        toast.success(isEditing ? 'Article updated' : 'Article created');
        navigate('/admin/news');
      }
    } catch (err) { toast.error(err.response?.data?.error?.message || 'Failed to save'); }
    finally { setSaving(false); }
  };

  if (loading) return <Spinner size="lg" className="py-20" />;

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{isEditing ? 'Edit Article' : 'New Article'}</h1>
          <p className="text-gray-500 text-sm mt-1">{isEditing ? 'Update news article.' : 'Create a new news article.'}</p>
        </div>
        <Button variant="secondary" onClick={() => navigate('/admin/news')}>Cancel</Button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="card space-y-5">
          <Input label="Title" name="title" value={form.title} onChange={handleChange} placeholder="Article title" required />
          <div>
            <label className="label">Content</label>
            <textarea name="content" value={form.content} onChange={handleChange} rows={15} className="input min-h-[300px]" placeholder="Article content (HTML supported)" />
          </div>
          <Input label="Excerpt" name="excerpt" value={form.excerpt} onChange={handleChange} placeholder="Brief summary" />
        </div>

        <div className="card space-y-5">
          <Select label="Status" name="status" value={form.status} onChange={handleChange} options={[{ value: 'draft', label: 'Draft' }, { value: 'published', label: 'Published' }]} />
          <ImagePicker
            label="Featured Image"
            value={form.featured_image}
            onChange={(val) => setForm((prev) => ({ ...prev, featured_image: val }))}
            placeholder="uploads/images/... or paste any URL"
          />
        </div>

        <div className="flex items-center justify-end gap-3">
          <Button type="button" variant="secondary" onClick={() => { setForm((p) => ({ ...p, status: 'draft' })); }}>Save Draft</Button>
          <Button type="submit" loading={saving}>{isEditing ? 'Update' : 'Create'}</Button>
        </div>
      </form>
    </div>
  );
}
