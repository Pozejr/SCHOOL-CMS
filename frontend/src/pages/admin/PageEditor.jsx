import { useParams } from 'react-router-dom';
import PageForm from '../../components/common/PageForm';

/**
 * Edit Page — thin wrapper around the shared PageForm.
 *
 * Loads the page ID from the URL, then renders the exact same
 * builder interface used for creating pages — pre-populated with
 * the page's sections and settings.
 *
 * The administrator sees the same interface they used to create
 * the page. No HTML editing. No different layout.
 */
export default function PageEditor() {
  const { id } = useParams();

  return <PageForm mode="edit" pageId={id} />;
}
