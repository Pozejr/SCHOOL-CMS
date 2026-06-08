import { useState, useEffect } from 'react';
import { useParams, Link } from 'react-router-dom';
import { newsService } from '../../services/newsService';
import Spinner from '../../components/common/Spinner';
import { formatDate, resolveImagePath } from '../../utils/helpers';

export default function PublicNewsArticle() {
  const { slug } = useParams();
  const [article, setArticle] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    newsService.getBySlug(slug)
      .then((res) => { if (res.success) setArticle(res.data); })
      .catch(() => {})
      .finally(() => setLoading(false));
  }, [slug]);

  if (loading) return <Spinner size="lg" className="py-20" />;
  if (!article) return (
    <div className="py-20 text-center">
      <h1 className="text-2xl font-bold text-gray-900">Article Not Found</h1>
      <Link to="/news" className="text-primary-600 mt-4 inline-block">← Back to News</Link>
    </div>
  );

  return (
    <div>
      <article className="py-12">
        <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
          <Link to="/news" className="text-primary-600 hover:text-primary-800 text-sm mb-6 inline-flex items-center gap-1">
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" /></svg>
            Back to News
          </Link>
          <h1 className="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">{article.title}</h1>
          <div className="flex items-center gap-4 text-sm text-gray-500 mb-8">
            <span>{formatDate(article.published_at)}</span>
            {article.creator_first_name && <span>By {article.creator_first_name} {article.creator_last_name}</span>}
          </div>
          {article.featured_image && (
            <img src={resolveImagePath(article.featured_image)} alt={article.title} className="w-full max-h-96 object-cover rounded-xl mb-8" />
          )}
          <div className="prose-content" dangerouslySetInnerHTML={{ __html: article.content }} />
        </div>
      </article>
    </div>
  );
}
