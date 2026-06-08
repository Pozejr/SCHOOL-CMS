import { useState, useEffect } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { eventService } from '../../services/eventService';
import { useToast } from '../../contexts/ToastContext';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Button from '../../components/common/Button';
import Spinner from '../../components/common/Spinner';
import ImagePicker from '../../components/common/ImagePicker';

export default function EventEditor() {
  const { id } = useParams();
  const navigate = useNavigate();
  const toast = useToast();
  const isEditing = !!id;

  const [loading, setLoading] = useState(isEditing);
  const [saving, setSaving] = useState(false);
  const [form, setForm] = useState({
    title: '',
    description: '',
    venue: '',
    event_date: '',
    end_date: '',
    status: 'draft',
    featured_image: '',
  });

  useEffect(() => { if (isEditing) loadEvent(); }, [id]);

  const loadEvent = async () => {
    try {
      const result = await eventService.getById(id);
      if (result.success) {
        const ev = result.data;
        setForm({
          title: ev.title || '',
          description: ev.description || '',
          venue: ev.venue || '',
          event_date: ev.event_date?.slice(0, 16) || '',
          end_date: ev.end_date?.slice(0, 16) || '',
          status: ev.status || 'draft',
          featured_image: ev.featured_image || '',
        });
      }
    } catch { toast.error('Failed to load event'); navigate('/admin/events'); }
    finally { setLoading(false); }
  };

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!form.title.trim()) { toast.error('Title is required'); return; }
    if (!form.event_date) { toast.error('Event date is required'); return; }
    setSaving(true);
    try {
      const result = isEditing ? await eventService.update(id, form) : await eventService.create(form);
      if (result.success) {
        toast.success(isEditing ? 'Event updated' : 'Event created');
        navigate('/admin/events');
      }
    } catch (err) { toast.error(err.response?.data?.error?.message || 'Failed to save'); }
    finally { setSaving(false); }
  };

  if (loading) return <Spinner size="lg" className="py-20" />;

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">{isEditing ? 'Edit Event' : 'New Event'}</h1>
          <p className="text-gray-500 text-sm mt-1">{isEditing ? 'Update event details.' : 'Create a new event.'}</p>
        </div>
        <Button variant="secondary" onClick={() => navigate('/admin/events')}>Cancel</Button>
      </div>

      <form onSubmit={handleSubmit} className="space-y-6">
        <div className="card space-y-5">
          <Input label="Title" name="title" value={form.title} onChange={handleChange} placeholder="Event title" required />
          <div>
            <label className="label">Description</label>
            <textarea name="description" value={form.description} onChange={handleChange} rows={10} className="input min-h-[200px]" placeholder="Event description" />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <Input label="Event Date" name="event_date" type="datetime-local" value={form.event_date} onChange={handleChange} required />
            <Input label="End Date" name="end_date" type="datetime-local" value={form.end_date} onChange={handleChange} />
          </div>
          <Input label="Venue" name="venue" value={form.venue} onChange={handleChange} placeholder="Event location" />
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
          <Button type="submit" loading={saving}>{isEditing ? 'Update Event' : 'Create Event'}</Button>
        </div>
      </form>
    </div>
  );
}
