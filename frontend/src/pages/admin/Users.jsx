import { useState, useEffect } from 'react';
import { userService } from '../../services/userService';
import { useAuth } from '../../contexts/AuthContext';
import { useToast } from '../../contexts/ToastContext';
import Button from '../../components/common/Button';
import Input from '../../components/common/Input';
import Select from '../../components/common/Select';
import Modal from '../../components/common/Modal';
import StatusBadge from '../../components/common/StatusBadge';
import Pagination from '../../components/common/Pagination';
import Spinner from '../../components/common/Spinner';
import ConfirmDialog from '../../components/common/ConfirmDialog';
import { formatDate } from '../../utils/helpers';
import { ROLE_LABELS } from '../../constants/roles';

export default function Users() {
  const { user: currentUser } = useAuth();
  const toast = useToast();
  const [users, setUsers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [total, setTotal] = useState(0);
  const [showModal, setShowModal] = useState(false);
  const [editingUser, setEditingUser] = useState(null);
  const [saving, setSaving] = useState(false);
  const [deleteId, setDeleteId] = useState(null);
  const [deleteLoading, setDeleteLoading] = useState(false);
  const [form, setForm] = useState({ username: '', email: '', password: '', first_name: '', last_name: '', role: 'editor', status: 'active' });

  useEffect(() => { loadUsers(); }, [page]);

  const loadUsers = async () => {
    setLoading(true);
    try {
      const result = await userService.getAll(page);
      if (result.success) {
        setUsers(result.data);
        setTotal(result.meta.total);
        setTotalPages(result.meta.total_pages);
      }
    } catch { toast.error('Failed to load users'); }
    finally { setLoading(false); }
  };

  const openCreate = () => {
    setEditingUser(null);
    setForm({ username: '', email: '', password: '', first_name: '', last_name: '', role: 'editor', status: 'active' });
    setShowModal(true);
  };

  const openEdit = (user) => {
    setEditingUser(user);
    setForm({ username: user.username, email: user.email, password: '', first_name: user.first_name, last_name: user.last_name, role: user.role, status: user.status });
    setShowModal(true);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSaving(true);
    try {
      const result = editingUser
        ? await userService.update(editingUser.id, form)
        : await userService.create(form);
      if (result.success) {
        toast.success(editingUser ? 'User updated' : 'User created');
        setShowModal(false);
        loadUsers();
      }
    } catch (err) { toast.error(err.response?.data?.error?.message || 'Failed to save user'); }
    finally { setSaving(false); }
  };

  const handleDelete = async () => {
    setDeleteLoading(true);
    try {
      await userService.delete(deleteId);
      toast.success('User deleted');
      loadUsers();
    } catch (err) { toast.error(err.response?.data?.error?.message || 'Failed to delete'); }
    finally { setDeleteLoading(false); setDeleteId(null); }
  };

  if (loading && !users.length) return <Spinner size="lg" className="py-20" />;

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Users</h1>
          <p className="text-gray-500 text-sm mt-1">{total} users</p>
        </div>
        <Button onClick={openCreate}>
          <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" /></svg>
          New User
        </Button>
      </div>

      <div className="card p-0 overflow-hidden">
        <div className="overflow-x-auto">
          <table className="min-w-full divide-y divide-gray-200">
            <thead>
              <tr className="bg-gray-50">
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Username</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-gray-200">
              {users.map((u) => (
                <tr key={u.id} className="hover:bg-gray-50">
                  <td className="px-6 py-4 text-sm font-medium text-gray-900">{u.first_name} {u.last_name}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{u.username}</td>
                  <td className="px-6 py-4 text-sm text-gray-500">{u.email}</td>
                  <td className="px-6 py-4 text-sm"><span className="badge-info">{ROLE_LABELS[u.role] || u.role}</span></td>
                  <td className="px-6 py-4"><StatusBadge status={u.status} /></td>
                  <td className="px-6 py-4 text-right">
                    <div className="flex items-center justify-end gap-2">
                      <button onClick={() => openEdit(u)} className="text-sm text-gray-400 hover:text-primary-600">Edit</button>
                      {u.id !== currentUser?.id && (
                        <button onClick={() => setDeleteId(u.id)} className="text-sm text-gray-400 hover:text-red-600">Delete</button>
                      )}
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>

      <Pagination currentPage={page} totalPages={totalPages} onPageChange={setPage} />

      {/* Create/Edit Modal */}
      <Modal isOpen={showModal} onClose={() => setShowModal(false)} title={editingUser ? 'Edit User' : 'New User'} size="md" footer={
        <div className="flex justify-end gap-3">
          <Button variant="secondary" onClick={() => setShowModal(false)}>Cancel</Button>
          <Button loading={saving} onClick={handleSubmit}>{editingUser ? 'Update' : 'Create'}</Button>
        </div>
      }>
        <div className="space-y-4">
          <div className="grid grid-cols-2 gap-4">
            <Input label="First Name" name="first_name" value={form.first_name} onChange={(e) => setForm(p => ({ ...p, first_name: e.target.value }))} required />
            <Input label="Last Name" name="last_name" value={form.last_name} onChange={(e) => setForm(p => ({ ...p, last_name: e.target.value }))} required />
          </div>
          <Input label="Username" name="username" value={form.username} onChange={(e) => setForm(p => ({ ...p, username: e.target.value }))} required />
          <Input label="Email" name="email" type="email" value={form.email} onChange={(e) => setForm(p => ({ ...p, email: e.target.value }))} required />
          <Input label={editingUser ? 'New Password (leave blank to keep)' : 'Password'} name="password" type="password" value={form.password} onChange={(e) => setForm(p => ({ ...p, password: e.target.value }))} required={!editingUser} />
          <div className="grid grid-cols-2 gap-4">
            <Select label="Role" name="role" value={form.role} onChange={(e) => setForm(p => ({ ...p, role: e.target.value }))} options={[{ value: 'super_admin', label: 'Super Admin' }, { value: 'admin', label: 'Admin' }, { value: 'editor', label: 'Editor' }]} />
            <Select label="Status" name="status" value={form.status} onChange={(e) => setForm(p => ({ ...p, status: e.target.value }))} options={[{ value: 'active', label: 'Active' }, { value: 'inactive', label: 'Inactive' }]} />
          </div>
        </div>
      </Modal>

      <ConfirmDialog isOpen={!!deleteId} onClose={() => setDeleteId(null)} onConfirm={handleDelete} title="Delete User" message="Are you sure you want to delete this user?" loading={deleteLoading} />
    </div>
  );
}
