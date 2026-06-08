import PageForm from '../../components/common/PageForm';

/**
 * Create Page — thin wrapper around the shared PageForm.
 * All builder logic lives in PageForm.jsx.
 */
export default function PageBuilder() {
  return <PageForm mode="create" />;
}
