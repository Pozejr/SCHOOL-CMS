export default function Alert({ type = 'info', title = '', children, onClose }) {
  const styles = {
    info: { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-800', icon: 'text-blue-400' },
    success: { bg: 'bg-green-50', border: 'border-green-200', text: 'text-green-800', icon: 'text-green-400' },
    warning: { bg: 'bg-yellow-50', border: 'border-yellow-200', text: 'text-yellow-800', icon: 'text-yellow-400' },
    error: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-800', icon: 'text-red-400' },
  };

  const s = styles[type] || styles.info;

  return (
    <div className={`rounded-lg border p-4 ${s.bg} ${s.border}`}>
      <div className="flex">
        <div className="flex-1">
          {title && <h3 className={`text-sm font-medium ${s.text}`}>{title}</h3>}
          <div className={`text-sm ${s.text} ${title ? 'mt-1' : ''}`}>{children}</div>
        </div>
        {onClose && (
          <button onClick={onClose} className={`${s.icon} hover:opacity-75`}>
            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        )}
      </div>
    </div>
  );
}
