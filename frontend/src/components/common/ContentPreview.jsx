import { useMemo } from 'react';

/**
 * ContentPreview — client-side preview of plain text → HTML
 * 
 * Mirrors the backend ContentParser for instant preview in the admin.
 * Not for public rendering (backend parser is the source of truth).
 */
export default function ContentPreview({ content }) {
  const html = useMemo(() => parseContent(content || ''), [content]);

  if (!content?.trim()) {
    return <p className="text-gray-300 text-sm italic">Start typing to see preview...</p>;
  }

  return (
    <div className="text-sm text-gray-700 prose-content-preview">
      <div dangerouslySetInnerHTML={{ __html: html }} />
    </div>
  );
}

function parseContent(text) {
  const lines = text.replace(/\r\n/g, '\n').replace(/\r/g, '\n').split('\n');
  const blocks = [];
  let currentParagraph = [];
  let currentList = null;
  let listType = null;

  const flushParagraph = () => {
    if (currentParagraph.length > 0) {
      blocks.push(`<p>${currentParagraph.join(' ')}</p>`);
      currentParagraph = [];
    }
  };

  const flushList = () => {
    if (currentList) {
      const tag = listType;
      blocks.push(`<${tag}>${currentList.join('')}</${tag}>`);
      currentList = null;
      listType = null;
    }
  };

  for (const rawLine of lines) {
    const line = rawLine.trim();

    if (!line) {
      flushList();
      flushParagraph();
      continue;
    }

    if (line.match(/^---+$/)) {
      flushList();
      flushParagraph();
      blocks.push('<hr>');
      continue;
    }

    if (line.startsWith('## ')) {
      flushList();
      flushParagraph();
      blocks.push(`<h4>${esc(line.slice(3))}</h4>`);
      continue;
    }

    if (line.startsWith('# ')) {
      flushList();
      flushParagraph();
      blocks.push(`<h3>${esc(line.slice(2))}</h3>`);
      continue;
    }

    const bulletMatch = line.match(/^[-*]\s+(.+)$/);
    if (bulletMatch) {
      flushParagraph();
      if (listType !== 'ul') { flushList(); listType = 'ul'; currentList = []; }
      currentList.push(`<li>${esc(bulletMatch[1])}</li>`);
      continue;
    }

    const numMatch = line.match(/^\d+\.\s+(.+)$/);
    if (numMatch) {
      flushParagraph();
      if (listType !== 'ol') { flushList(); listType = 'ol'; currentList = []; }
      currentList.push(`<li>${esc(numMatch[1])}</li>`);
      continue;
    }

    if (line.startsWith('> ')) {
      flushList();
      flushParagraph();
      blocks.push(`<blockquote><p>${esc(line.slice(2))}</p></blockquote>`);
      continue;
    }

    flushList();
    currentParagraph.push(esc(line));
  }

  flushList();
  flushParagraph();

  return blocks.join('');
}

function esc(s) {
  return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
