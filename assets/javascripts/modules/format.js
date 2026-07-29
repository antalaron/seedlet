/**
 * Pure formatting helpers shared by the UI modules.
 */

const SIZE_UNITS = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];

/**
 * Formats a byte count as a human readable string, e.g. "12.3 MB".
 */
export function formatBytes (bytes) {
  if (!Number.isFinite(bytes) || bytes <= 0) {
    return '0 B';
  }

  const exponent = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), SIZE_UNITS.length - 1);
  const value = bytes / Math.pow(1024, exponent);

  return `${exponent === 0 ? value : value.toFixed(1)} ${SIZE_UNITS[exponent]}`;
}

/**
 * Formats a rate in bytes/second, e.g. "1.2 MB/s".
 */
export function formatRate (bytesPerSecond) {
  return `${formatBytes(bytesPerSecond)}/s`;
}

/**
 * Formats a Transmission ETA (seconds, or -1/-2 for unknown/not-applicable).
 */
export function formatEta (seconds) {
  if (seconds < 0) {
    return '∞';
  }

  if (seconds < 60) {
    return `${seconds}s`;
  }

  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) {
    return `${minutes}m`;
  }

  const hours = Math.floor(minutes / 60);
  if (hours < 24) {
    return `${hours}h ${minutes % 60}m`;
  }

  const days = Math.floor(hours / 24);

  return `${days}d ${hours % 24}h`;
}

/**
 * Formats an ISO 8601 date string as a short, locale-aware date/time.
 */
export function formatDate (isoDate) {
  if (!isoDate) {
    return '—';
  }

  const date = new Date(isoDate);
  if (Number.isNaN(date.getTime())) {
    return '—';
  }

  return date.toLocaleString();
}

/**
 * Formats a progress ratio (0..1) as a whole percentage.
 */
export function formatPercent (ratio) {
  return `${Math.round(ratio * 100)}%`;
}
