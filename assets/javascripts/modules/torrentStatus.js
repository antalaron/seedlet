/**
 * Maps a Transmission torrent's status (and error state) to a single
 * presentation used both by the torrent list badges/progress bars and the
 * torrent details modal, so the two views can never disagree about what a
 * given state looks like.
 *
 * Status codes come straight from the Transmission RPC "status" field:
 * 0 stopped, 1 queued to verify, 2 verifying, 3 queued to download,
 * 4 downloading, 5 queued to seed, 6 seeding.
 */
const PRESENTATION_BY_STATUS = {
  0: { key: 'stopped', badgeClass: 'bg-secondary', progressClass: 'bg-secondary', icon: 'fa-pause' },
  1: { key: 'checking', badgeClass: 'bg-warning', progressClass: 'bg-warning', icon: 'fa-magnifying-glass' },
  2: { key: 'checking', badgeClass: 'bg-warning', progressClass: 'bg-warning', icon: 'fa-magnifying-glass' },
  3: { key: 'checking', badgeClass: 'bg-warning', progressClass: 'bg-warning', icon: 'fa-clock' },
  4: { key: 'downloading', badgeClass: 'bg-success', progressClass: 'bg-success', icon: 'fa-arrow-down' },
  5: { key: 'checking', badgeClass: 'bg-warning', progressClass: 'bg-warning', icon: 'fa-clock' },
  6: { key: 'completed', badgeClass: 'bg-info', progressClass: 'bg-info', icon: 'fa-check' }
};

const ERROR_PRESENTATION = { key: 'error', badgeClass: 'bg-danger', progressClass: 'bg-danger', icon: 'fa-triangle-exclamation' };

const FALLBACK_PRESENTATION = { key: 'unknown', badgeClass: 'bg-secondary', progressClass: 'bg-secondary', icon: 'fa-question' };

/**
 * Returns the { key, badgeClass, progressClass, icon } presentation for a
 * torrent, based on its actual Transmission status/errorString rather than
 * its download percentage.
 */
export function getStatusPresentation (torrent) {
  if (torrent.errorString) {
    return ERROR_PRESENTATION;
  }

  return PRESENTATION_BY_STATUS[torrent.status] || FALLBACK_PRESENTATION;
}

/**
 * Every progress-bar/badge color class this module can hand out, so callers
 * can clear them all before applying the current one.
 */
export const STATUS_CLASSES = Array.from(new Set([
  ...Object.values(PRESENTATION_BY_STATUS).map((p) => p.badgeClass),
  ...Object.values(PRESENTATION_BY_STATUS).map((p) => p.progressClass),
  ERROR_PRESENTATION.badgeClass,
  ERROR_PRESENTATION.progressClass,
  FALLBACK_PRESENTATION.badgeClass,
  FALLBACK_PRESENTATION.progressClass
]));
