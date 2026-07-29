import { formatBytes, formatRate, formatEta, formatDate, formatPercent } from './format.js';

const STATUS_BADGE_CLASS = {
  0: 'bg-secondary',
  1: 'bg-info',
  2: 'bg-info',
  3: 'bg-info',
  4: 'bg-primary',
  5: 'bg-info',
  6: 'bg-success'
};

const POLL_INTERVAL_MS = 5000;

/**
 * Renders the torrent list, keeps it sorted according to the user's
 * preference and refreshes it through AJAX polling without rebuilding
 * unrelated rows.
 */
class TorrentList {
  constructor ({ api, sortPreference, container, emptyState, countLabel, sortFieldSelect, sortDirectionButton, errorBanner, onDetails, onRemove }) {
    this.api = api;
    this.sortPreference = sortPreference;
    this.container = container;
    this.emptyState = emptyState;
    this.countLabel = countLabel;
    this.sortFieldSelect = sortFieldSelect;
    this.sortDirectionButton = sortDirectionButton;
    this.errorBanner = errorBanner;
    this.onDetails = onDetails;
    this.onRemove = onRemove;

    this.rows = new Map();
    this.timer = null;
    this.sort = this.sortPreference.load();
  }

  init () {
    this.sortFieldSelect.value = this.sort.field;
    this.updateSortDirectionButton();

    this.sortFieldSelect.addEventListener('change', () => {
      this.sort.field = this.sortFieldSelect.value;
      this.sortPreference.save(this.sort);
      this.refresh();
    });

    this.sortDirectionButton.addEventListener('click', () => {
      this.sort.direction = this.sort.direction === 'asc' ? 'desc' : 'asc';
      this.sortPreference.save(this.sort);
      this.updateSortDirectionButton();
      this.refresh();
    });

    this.container.addEventListener('click', (event) => this.handleRowClick(event));

    document.addEventListener('visibilitychange', () => {
      if (document.hidden) {
        this.stopPolling();
      } else {
        this.refresh();
        this.startPolling();
      }
    });

    this.refresh();
    this.startPolling();
  }

  updateSortDirectionButton () {
    const ascending = this.sort.direction === 'asc';
    this.sortDirectionButton.innerHTML = ascending
      ? '<i class="fa-solid fa-arrow-up-wide-short" aria-hidden="true"></i> <span>Ascending</span>'
      : '<i class="fa-solid fa-arrow-down-wide-short" aria-hidden="true"></i> <span>Descending</span>';
  }

  startPolling () {
    this.stopPolling();
    this.timer = window.setInterval(() => this.refresh(), POLL_INTERVAL_MS);
  }

  stopPolling () {
    if (this.timer !== null) {
      window.clearInterval(this.timer);
      this.timer = null;
    }
  }

  async refresh () {
    try {
      const data = await this.api.getTorrents();
      this.showError(null);
      this.render(data.torrents);
    } catch (error) {
      this.showError(error.message);
    }
  }

  showError (message) {
    if (message) {
      this.errorBanner.textContent = message;
      this.errorBanner.classList.remove('d-none');
    } else {
      this.errorBanner.classList.add('d-none');
    }
  }

  render (torrents) {
    const sorted = [...torrents].sort((a, b) => this.compare(a, b));
    const seenIds = new Set();

    for (const torrent of sorted) {
      let row = this.rows.get(torrent.id);
      if (!row) {
        row = this.createRow(torrent.id);
        this.rows.set(torrent.id, row);
      }

      this.updateRow(row, torrent);
      this.container.appendChild(row);
      seenIds.add(torrent.id);
    }

    for (const [id, row] of this.rows) {
      if (!seenIds.has(id)) {
        row.remove();
        this.rows.delete(id);
      }
    }

    this.emptyState.classList.toggle('d-none', sorted.length > 0);
    this.countLabel.textContent = sorted.length === 1 ? '1 torrent' : `${sorted.length} torrents`;
  }

  compare (a, b) {
    const direction = this.sort.direction === 'asc' ? 1 : -1;
    const field = this.sort.field;

    if (field === 'name') {
      return direction * a.name.localeCompare(b.name, undefined, { sensitivity: 'base' });
    }

    if (a[field] < b[field]) {
      return -1 * direction;
    }

    if (a[field] > b[field]) {
      return 1 * direction;
    }

    return a.name.localeCompare(b.name, undefined, { sensitivity: 'base' });
  }

  createRow (id) {
    const template = document.getElementById('torrent-row-template');
    const fragment = template.content.cloneNode(true);
    const row = fragment.querySelector('.torrent-row');
    row.dataset.id = String(id);

    return row;
  }

  updateRow (row, torrent) {
    row.querySelector('[data-field="name"]').textContent = torrent.name;

    const badge = row.querySelector('[data-field="statusBadge"]');
    badge.textContent = torrent.statusLabel;
    badge.className = `badge torrent-status-badge ${STATUS_BADGE_CLASS[torrent.status] || 'bg-secondary'}`;

    const progressBar = row.querySelector('[data-field="progressBar"]');
    const percent = formatPercent(torrent.percentDone);
    progressBar.style.width = percent;
    progressBar.setAttribute('aria-valuenow', String(Math.round(torrent.percentDone * 100)));
    progressBar.setAttribute('aria-valuemin', '0');
    progressBar.setAttribute('aria-valuemax', '100');
    row.querySelector('[data-field="progressLabel"]').textContent = percent;

    row.querySelector('[data-field="size"]').textContent = formatBytes(torrent.totalSize);
    row.querySelector('[data-field="rateDownload"]').textContent = formatRate(torrent.rateDownload);
    row.querySelector('[data-field="rateUpload"]').textContent = formatRate(torrent.rateUpload);
    row.querySelector('[data-field="eta"]').textContent = `ETA ${formatEta(torrent.eta)}`;
    row.querySelector('[data-field="peers"]').textContent = `${torrent.seeders} seeders, ${torrent.leechers} leechers`;
    row.querySelector('[data-field="addedDate"]').textContent = formatDate(torrent.addedDate);

    const toggleButton = row.querySelector('.torrent-toggle-btn');
    const isStopped = torrent.status === 0;
    toggleButton.innerHTML = isStopped
      ? '<i class="fa-solid fa-play" aria-hidden="true"></i> <span>Resume</span>'
      : '<i class="fa-solid fa-pause" aria-hidden="true"></i> <span>Pause</span>';
    toggleButton.dataset.paused = isStopped ? '1' : '0';
  }

  async handleRowClick (event) {
    const target = event.target.closest('[data-action]');
    if (!target) {
      return;
    }

    const row = target.closest('.torrent-row');
    const id = Number(row.dataset.id);
    const action = target.dataset.action;

    if (action === 'details') {
      this.onDetails(id);

      return;
    }

    if (action === 'remove') {
      this.onRemove(id, row.querySelector('[data-field="name"]').textContent);

      return;
    }

    if (action === 'toggle') {
      try {
        if (target.dataset.paused === '1') {
          await this.api.resumeTorrent(id);
        } else {
          await this.api.pauseTorrent(id);
        }
        this.refresh();
      } catch (error) {
        this.showError(error.message);
      }
    }
  }
}

export default TorrentList;
