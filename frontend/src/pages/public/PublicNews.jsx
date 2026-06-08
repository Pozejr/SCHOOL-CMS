import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { newsService } from '../../services/newsService';
import Spinner from '../../components/common/Spinner';
import Pagination from '../../components/common/Pagination';
import { formatDate, truncateText, stripHtml, resolveImagePath } from '../../utils/helpers';

export default function PublicNews() {
  const [news, setNews] = useState([]);
  const [loading, setLoading] = useState(true);
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);

  useEffect(() => { loadNews(); }, [page]);

  const loadNews = async () => {
    setLoading(true);
    try {
      const result = await newsService.getPublished(page, 9);
      if (result.success) {
        setNews(result.data);
        setTotalPages(result.meta.total_pages);
      }
    } catch {}
    finally { setLoading(false); }
  };

  if (loading && !news.length) return <Spinner size="lg" className="py-20" />;

  return (
    <div>
      <section className="bg-primary-800 text-white py-16">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <h1 className="text-4xl font-bold">News & Updates</h1>
          <p className="mt-3 text-primary-200 text-lg">Stay updated with the latest from our school.</p>
        </div>
      </section>
      <section className="py-12">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {news.map((article) => (
              <Link key={article.id} to={`/news/${article.slug}`} className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden hover:shadow-md transition-shadow">
                {article.featured_image && <img src={resolveImagePath(article.featured_image)} alt={article.title} className="w-full h-48 object-cover" />}
                <div className="p-5">
                  <p className="text-xs text-gray-400 mb-2">{formatDate(article.published_at)}</p>
                  <h3 className="font-semibold text-gray-900 mb-2">{article.title}</h3>
                  <p className="text-sm text-gray-500">{truncateText(stripHtml(article.content), 120)}</p>
                </div>
              </Link>
            ))}
          </div>
          <Pagination currentPage={page} totalPages={totalPages} onPageChange={setPage} />
        </div>
      </section>
    </div>
  );
}
