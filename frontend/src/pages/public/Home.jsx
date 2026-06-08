import { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { pageService } from '../../services/pageService';
import { newsService } from '../../services/newsService';
import { eventService } from '../../services/eventService';
import SectionRenderer from '../../components/common/SectionRenderer';
import Spinner from '../../components/common/Spinner';
import { formatDate, truncateText, stripHtml, resolveImagePath } from '../../utils/helpers';

export default function Home() {
  const [homeContent, setHomeContent] = useState(null);
  const [latestNews, setLatestNews] = useState([]);
  const [upcomingEvents, setUpcomingEvents] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    Promise.all([
      pageService.getBySlug('home').catch(() => null),
      newsService.getPublished(1, 3).catch(() => null),
      eventService.getPublished(1, 3).catch(() => null),
    ]).then(([pageRes, newsRes, eventsRes]) => {
      if (pageRes?.success) setHomeContent(pageRes.data);
      if (newsRes?.success) setLatestNews(newsRes.data);
      if (eventsRes?.success) setUpcomingEvents(eventsRes.data);
    }).finally(() => setLoading(false));
  }, []);

  if (loading) return <Spinner size="lg" className="py-20" />;

  const sections = homeContent?.sections || [];
  const hasSections = sections.length > 0;

  return (
    <div>
      {/* Hero Section */}
      <section className="bg-gradient-to-br from-primary-800 via-primary-700 to-primary-900 text-white">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20 lg:py-28">
          <div className="max-w-3xl">
            <h1 className="text-4xl lg:text-6xl font-bold mb-6">
              {homeContent?.title || 'Welcome to Our School'}
            </h1>
            <p className="text-lg lg:text-xl text-primary-100 mb-8 leading-relaxed">
              {homeContent?.seo?.meta_description || 'Excellence in education. Building tomorrow\'s leaders today.'}
            </p>
            <div className="flex flex-wrap gap-4">
              <Link to="/admissions" className="px-6 py-3 bg-white text-primary-700 rounded-lg font-semibold hover:bg-primary-50 transition-colors">
                Apply Now
              </Link>
              <Link to="/about" className="px-6 py-3 border-2 border-white/30 text-white rounded-lg font-semibold hover:bg-white/10 transition-colors">
                Learn More
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* Smart Page Sections */}
      {hasSections && (
        <section className="py-16 space-y-16 bg-white">
          {sections.filter(s => s.title && s.content_html).map((section) => (
            <div key={section.id} className="px-4 sm:px-6 lg:px-8">
              <SectionRenderer section={section} />
            </div>
          ))}
        </section>
      )}

      {/* Stats */}
      <section className="py-12 bg-gray-50 border-y">
        <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
          <div className="grid grid-cols-2 md:grid-cols-4 gap-8">
            {[
              { number: '25+', label: 'Years of Excellence' },
              { number: '1,500+', label: 'Students' },
              { number: '100+', label: 'Qualified Teachers' },
              { number: '98%', label: 'Pass Rate' },
            ].map((stat) => (
              <div key={stat.label} className="text-center">
                <p className="text-3xl font-bold text-primary-700">{stat.number}</p>
                <p className="text-sm text-gray-500 mt-1">{stat.label}</p>
              </div>
            ))}
          </div>
        </div>
      </section>

      {/* Latest News */}
      {latestNews.length > 0 && (
        <section className="py-16 bg-white">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center justify-between mb-8">
              <h2 className="text-2xl font-bold text-gray-900">Latest News</h2>
              <Link to="/news" className="text-primary-600 hover:text-primary-800 text-sm font-medium">View All →</Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {latestNews.map((article) => (
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
          </div>
        </section>
      )}

      {/* Upcoming Events */}
      {upcomingEvents.length > 0 && (
        <section className="py-16 bg-gray-50">
          <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div className="flex items-center justify-between mb-8">
              <h2 className="text-2xl font-bold text-gray-900">Upcoming Events</h2>
              <Link to="/events" className="text-primary-600 hover:text-primary-800 text-sm font-medium">View All →</Link>
            </div>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              {upcomingEvents.map((event) => (
                <div key={event.id} className="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
                  <div className="flex items-start gap-4">
                    <div className="flex-shrink-0 w-14 h-14 bg-primary-50 rounded-lg flex flex-col items-center justify-center">
                      <span className="text-xs text-primary-600 font-medium">{new Date(event.event_date).toLocaleDateString('en-US', { month: 'short' })}</span>
                      <span className="text-lg font-bold text-primary-700">{new Date(event.event_date).getDate()}</span>
                    </div>
                    <div>
                      <h3 className="font-semibold text-gray-900">{event.title}</h3>
                      <p className="text-sm text-gray-500 mt-1">{event.venue}</p>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </section>
      )}

      {/* CTA */}
      <section className="py-16 bg-primary-700 text-white">
        <div className="max-w-4xl mx-auto text-center px-4">
          <h2 className="text-3xl font-bold mb-4">Ready to Join Our Community?</h2>
          <p className="text-primary-100 mb-8 text-lg">Discover the possibilities that await at our school.</p>
          <div className="flex flex-wrap justify-center gap-4">
            <Link to="/admissions" className="px-8 py-3 bg-white text-primary-700 rounded-lg font-semibold hover:bg-primary-50 transition-colors">
              Start Application
            </Link>
            <Link to="/contact" className="px-8 py-3 border-2 border-white/30 rounded-lg font-semibold hover:bg-white/10 transition-colors">
              Contact Us
            </Link>
          </div>
        </div>
      </section>
    </div>
  );
}
