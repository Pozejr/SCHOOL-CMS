import { useState, useCallback } from 'react';

export default function usePagination(initialPage = 1, initialPerPage = 15) {
  const [page, setPage] = useState(initialPage);
  const [perPage, setPerPage] = useState(initialPerPage);
  const [total, setTotal] = useState(0);

  const totalPages = Math.ceil(total / perPage);

  const goToPage = useCallback((newPage) => {
    if (newPage >= 1 && newPage <= totalPages) {
      setPage(newPage);
    }
  }, [totalPages]);

  const nextPage = useCallback(() => {
    goToPage(page + 1);
  }, [page, goToPage]);

  const prevPage = useCallback(() => {
    goToPage(page - 1);
  }, [page, goToPage]);

  const updateTotal = useCallback((newTotal) => {
    setTotal(newTotal);
  }, []);

  const reset = useCallback(() => {
    setPage(initialPage);
    setTotal(0);
  }, [initialPage]);

  return {
    page,
    perPage,
    total,
    totalPages,
    goToPage,
    nextPage,
    prevPage,
    setPerPage,
    updateTotal,
    reset,
  };
}
