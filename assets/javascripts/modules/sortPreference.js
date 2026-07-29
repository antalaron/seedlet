const STORAGE_KEY = 'seedlet:sort';
const DEFAULT_SORT = { field: 'name', direction: 'asc' };

/**
 * Persists the user's chosen torrent list sorting in localStorage, so it
 * survives page reloads. Falls back gracefully when localStorage is
 * unavailable (e.g. private browsing) or contains unexpected data.
 */
class SortPreference {
  load () {
    try {
      const raw = window.localStorage.getItem(STORAGE_KEY);
      if (!raw) {
        return { ...DEFAULT_SORT };
      }

      const parsed = JSON.parse(raw);
      if (typeof parsed.field !== 'string' || (parsed.direction !== 'asc' && parsed.direction !== 'desc')) {
        return { ...DEFAULT_SORT };
      }

      return parsed;
    } catch {
      return { ...DEFAULT_SORT };
    }
  }

  save (sort) {
    try {
      window.localStorage.setItem(STORAGE_KEY, JSON.stringify(sort));
    } catch {
      // Ignore storage failures (e.g. quota exceeded, private browsing).
    }
  }
}

export default SortPreference;
